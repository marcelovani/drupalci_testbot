<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\Testing;


use DrupalCI\Build\BuildInterface;
use DrupalCI\Build\Environment\Environment;
use DrupalCI\Console\Output;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\PluginBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * @PluginID("simpletest")
 */
class Simpletest extends PluginBase implements BuildStepInterface, BuildTaskInterface, Injectable  {

  /* @var  \DrupalCI\Build\Environment\DatabaseInterface */
  protected $system_database;

  /* @var  \DrupalCI\Build\Environment\DatabaseInterface */
  protected $results_database;

  /**
   * The current container environment
   *
   * @var  \DrupalCI\Build\Environment\EnvironmentInterface
   */
  protected $environment;

  protected $runscript = '/core/scripts/run-tests.sh';

  public function inject(Container $container) {
    parent::inject($container);
    $this->system_database = $container['db.system'];
    $this->results_database = $container['db.results'];
    $this->environment = $container['environment'];

  }

  /**
   * @inheritDoc
   */
  public function configure() {
    // Override any Environment Variables
    if (isset($_ENV['DCI_PHPInterpreter'])) {
      $this->configuration['php'] = $_ENV['DCI_PHPInterpreter'];
    }
    if (isset($_ENV['DCI_Concurrency'])) {
      $this->configuration['concurrency'] = $_ENV['DCI_Concurrency'];
    }
    if (isset($_ENV['DCI_RTTypes'])) {
      $this->configuration['types'] = $_ENV['DCI_RTTypes'];
    }
    if (isset($_ENV['DCI_RTUrl'])) {
      $this->configuration['types'] = $_ENV['DCI_RTUrl'];
    }
    if (isset($_ENV['DCI_RTColor'])) {
      $this->configuration['color'] = $_ENV['DCI_RTColor'];
    }
    if (isset($_ENV['DCI_RTTestGroups'])) {
      $this->configuration['testgroups'] = $this->parseTestGroups($_ENV['DCI_RTTestGroups']);
    }
    if (isset($_ENV['DCI_TestItem'])) {
      $this->configuration['testgroups'] = $this->parseTestGroups($_ENV['DCI_TestItem']);
    }
    if (isset($_ENV['DCI_TestGroups'])) {
      $this->configuration['testgroups'] = $this->parseTestGroups($_ENV['DCI_TestGroups']);
    }
    if (isset($_ENV['DCI_RTDieOnFail'])) {
      $this->configuration['die-on-fail'] = $_ENV['DCI_RTDieOnFail'];
    }
    if (isset($_ENV['DCI_RTKeepResults'])) {
      $this->configuration['keep-results'] = $_ENV['DCI_RTKeepResults'];
    }
    if (isset($_ENV['DCI_RTKeepResultsTable'])) {
      $this->configuration['keep-results-table'] = $_ENV['DCI_RTKeepResultsTable'];
    }
    if (isset($_ENV['DCI_RTVerbose'])) {
      $this->configuration['verbose'] = $_ENV['DCI_RTVerbose'];
    }



  }

  /**
   * @inheritDoc
   */
  public function declareArtifacts() {

    $this->addArtifact("xmlresults", $this->build->getXmlDirectory() . "/testresults.xml");
    $this->addContainerArtifact("apache_error", "/var/log/apache2/error.log");
    $this->addContainerArtifact("phantomjs_err", "/var/log/supervisor/phatomjs.err.log");
    $this->addContainerArtifact("phantomjs_out", "/var/log/supervisor/phatomjs.out.log");
    $this->addContainerArtifact("phpunit_xml", $this->environment->getExecContainerSourceDir() . '/sites/default/files/simpletest');
    $this->addContainerArtifact("testgroups", $this->environment->getContainerArtifactDir() . "/testgroups.txt");
    $this->addContainerArtifact("sqlite", $this->environment->getContainerArtifactDir() . "/simpletest.sqlite");
  }

  /**
   * @inheritDoc
   */
  public function run() {

    $this->setupSimpletestDB($this->build);
    $status = $this->generateTestGroups();
    if ($status > 0) {
      return $status;
    }
    $command = ["cd " . $this->environment->getExecContainerSourceDir() . " && sudo -u www-data php " . $this->environment->getExecContainerSourceDir() . $this->runscript];
    $this->configuration['dburl'] = $this->system_database->getUrl();
    $command[] = $this->getRunTestsFlagValues($this->configuration);
    $command[] = $this->getRunTestsValues($this->configuration);
    $command[] = $this->configuration['testgroups'];

    $command_line = implode(' ', $command);

    $result = $this->environment->executeCommands($command_line);

    $this->generateJunitXml($this->build);
    // Last thing. JunitFormat the output.

  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'testgroups' => '--all',
      'concurrency' => 4,
      'types' => 'Simpletest,PHPUnit-Unit,PHPUnit-Kernel,PHPUnit-Functional',
      'url' => 'http://localhost/checkout',
      'php' => '/opt/phpenv/shims/php',
      'color' => TRUE,
      'die-on-fail' => FALSE,
      'keep-results' => TRUE,
      'keep-results-table' => FALSE,
      'verbose' => FALSE,
    ];
  }

  protected function parseTestGroups($testitem) {
    // Special case for 'all'
    if (strtolower($testitem) === 'all') {
      return "--all";
    }

    // Split the string components
    $components = explode(':', $testitem);
    if (!in_array($components[0], array('module', 'class', 'file', 'directory'))) {
      // Invalid entry.
      return $testitem;
    }

    $testgroups = "--" . $components[0] . " " . $components[1];

    return $testgroups;
  }

  protected function setupSimpletestDB(BuildInterface $build) {


    // This is a rare instance where we're meddling with config after the object
    // is underway. Perhaps theres a better way?
    $this->configuration['sqlite'] = $this->getArtifact('sqlite')->getPath();
    $dbfile = $this->build->getArtifactDirectory() . '/simpletest.sqlite';
    $this->results_database->setDBFile($dbfile);
    $this->results_database->setDbType('sqlite');
  }

  protected function generateTestGroups() {
    $cmd = "sudo -u www-data php " . $this->environment->getExecContainerSourceDir() . $this->runscript . " --list --php " . $this->configuration['php'] . " > " . $this->getArtifact('testgroups')->getPath();
    $status = $this->environment->executeCommands($cmd);
    return $status;
  }
  /**
   * @param $test_list
   *
   * @return array
   */
  protected function parseGroups($test_list): array {
    // Set an initial default group, in case leading tests are found with no group.
    $group = 'nogroup';
    $test_groups = [];

    foreach ($test_list as $output_line) {
      if (substr($output_line, 0, 3) == ' - ') {
        // This is a class
        $class = substr($output_line, 3);
        $test_groups[$class] = $group;
      }
      else {
        // This is a group
        $group = ucwords($output_line);
      }
    }
    return $test_groups;
  }


  /**
   * Turn run-test.sh flag values into their command-line equivalents.
   *
   * @param type $config
   *   This plugin's config, from run().
   *
   * @return string
   *   The assembled command line fragment.
   */
  protected function getRunTestsFlagValues($config) {
    $command = [];
    $flags = [
      'color',
      'die-on-fail',
      'keep-results',
      'keep-results-table',
      'verbose',
    ];
    foreach($config as $key => $value) {
      if (in_array($key, $flags)) {
        if ($value) {
          $command[] = "--$key";
        }
      }
    }
    return implode(' ', $command);
  }

  /**
   * Turn run-test.sh values into their command-line equivalents.
   *
   * @param type $config
   *   This plugin's config, from run().
   *
   * @return string
   *   The assembled command line fragment.
   */
  protected function getRunTestsValues($config) {
    $command = [];
    $args = [
      'concurrency',
      'dburl',
      'sqlite',
      'types',
      'url',
      'xml',
      'php',
    ];
    foreach ($config as $key => $value) {
      if (in_array($key, $args)) {
        if ($value) {
          $command[] = "--$key \"$value\"";
        }
      }
    }
    return implode(' ', $command);
  }
  /**
   * {@inheritdoc}
   */
  public function generateJunitXml(BuildInterface $build) {

    // Load the list of tests from the testgroups.txt build artifact
    // This gets generated in the containers, into the container artifact dir
    // we cant use $this->getArtifact('testgroups')->getPath() because we need
    // the Host path.
    $test_listfile = $this->build->getArtifactDirectory() . '/testgroups.txt';
    $test_list = file($test_listfile, FILE_IGNORE_NEW_LINES);
    $test_list = array_slice($test_list, 4);

    $test_groups = $this->parseGroups($test_list);

    // @TODO fix this api. This seems a little obtuse.
    $db = $this->results_database->connect($this->results_database->getDbname());

    // query for simpletest results
    $results_map = array(
      'pass' => 'Pass',
      'fail' => 'Fail',
      'exception' => 'Exception',
      'debug' => 'Debug',
    );

    $q_result = $db->query('SELECT * FROM simpletest ORDER BY test_id, test_class, message_id;');

    $classes = [];

    while ($result = $q_result->fetch(\PDO::FETCH_ASSOC)) {
      if (isset($results_map[$result['status']])) {
        // Set the group from the lookup table
        $test_group = $test_groups[$result['test_class']];

        // Set the test class
        if (isset($result['test_class'])) {
          $test_class = $result['test_class'];
        }
        // Jenkins likes to see the groups and classnames together. -
        // This might need to be re-addressed when we look at the tests.
        $classname = $test_groups[$test_class] . '.' . $test_class;

        // Cleanup the class, and the parens from the test method name
        $test_method = substr($result['function'], strpos($result['function'], '>') + 1);
        $test_method = substr($test_method, 0, strlen($test_method) - 2);

        //$classes[$test_group][$test_class][$test_method]['classname'] = $classname;
        $length = strlen($this->environment->getExecContainerSourceDir());
        $result['file'] = substr($result['file'],$length+1); // Trim off source dir
        $classes[$test_group][$test_class][$test_method][] = array(
          'status' => $result['status'],
          'type' => $result['message_group'],
          'message' => strip_tags(htmlspecialchars_decode($result['message'],ENT_QUOTES)),
          'line' => $result['line'],
          'file' => $result['file'],
        );
      }
    }
    $this->_build_xml($classes);
  }

  private function _build_xml($test_result_data) {
    // Maps statuses to their xml element for each testcase.
    $element_map = array(
      'pass' => 'system-out',
      'fail' => 'failure',
      'exception' => 'error',
      'debug' => 'system-err',
    );
    // Create an xml file per group?

    $test_group_id = 0;
    $doc = new \DOMDocument('1.0');
    $test_suites = $doc->createElement('testsuites');

    // TODO: get test name data from the build.
    $test_suites->setAttribute('name', "TODO SET");
    $test_suites->setAttribute('time', "TODO SET");
    $total_failures = 0;
    $total_tests = 0;
    $total_exceptions = 0;

    // Go through the groups, and create a testsuite for each.
    foreach ($test_result_data as $groupname => $group_classes) {
      $group_failures = 0;
      $group_tests = 0;
      $group_exceptions = 0;
      $test_suite = $doc->createElement('testsuite');
      $test_suite->setAttribute('id', $test_group_id);
      $test_suite->setAttribute('name', $groupname);
      // While more pure, we should probably inject a date/time service.
      // For now we do not need the timestamp on group data.
      //  $test_suite->setAttribute('timestamp', date('c'));
      $test_suite->setAttribute('hostname', "TODO: Set Hostname");
      $test_suite->setAttribute('package', $groupname);
      // TODO: time test runs. $test_group->setAttribute('time', $test_group_id);
      // TODO: add in the properties of the build into the test run.

      // Loop through the classes in each group
      foreach ($group_classes as $class_name => $class_methods) {
        foreach ($class_methods as $test_method => $method_results) {
          $test_case = $doc->createElement('testcase');
          $test_case->setAttribute('classname', $groupname . "." . $class_name);
          $test_case->setAttribute('name', $test_method);
          $test_case_status = 'pass';
          $test_case_assertions = 0;
          $test_case_exceptions = 0;
          $test_case_failures = 0;
          $test_output = '';
          $fail_output = '';
          $exception_output = '';
          foreach ($method_results as $assertion) {
            $assertion_result = $assertion['status'] . ": [" . $assertion['type'] . "] Line " . $assertion['line'] . " of " . $assertion['file'] . ":\n" . $assertion['message'] . "\n\n";
            $assertion_result = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '�', $assertion_result);

            // Keep track of overall assersions counts
            if (!isset($assertion_counter[$assertion['status']])) {
              $assertion_counter[$assertion['status']] = 0;
            }
            $assertion_counter[$assertion['status']]++;
            if ($assertion['status'] == 'exception') {
              $test_case_exceptions++;
              $group_exceptions++;
              $total_exceptions++;
              $test_case_status = 'failed';
              $exception_output .= $assertion_result;
            } else if ($assertion['status'] == 'fail'){
              $test_case_failures++;
              $group_failures++;
              $total_failures++;
              $test_case_status = 'failed';
              $fail_output .= $assertion_result;
            }
            elseif (($assertion['status'] == 'debug')) {
              $test_output .= $assertion_result;
            }

            $test_case_assertions++;
            $group_tests++;
            $total_tests++;

          }
          if ($test_case_failures > 0) {
            $element = $doc->createElement("failure");
            $element->setAttribute('message', $fail_output);
            $element->setAttribute('type', "fail");
            $test_case->appendChild($element);
          }

          if ($test_case_exceptions > 0 ) {
            $element = $doc->createElement("error");
            $element->setAttribute('message', $exception_output);
            $element->setAttribute('type', "exception");
            $test_case->appendChild($element);
          }
          $std_out = $doc->createElement('system-out');
          $output = $doc->createCDATASection($test_output);
          $std_out->appendChild($output);
          $test_case->appendChild($std_out);

          // TODO: Errors and Failures need to be set per test Case.
          $test_case->setAttribute('status', $test_case_status);
          $test_case->setAttribute('assertions', $test_case_assertions);
          // $test_case->setAttribute('time', "TODO: track time");

          $test_suite->appendChild($test_case);

        }
      }

      // Should this count the tests as part of the loop, or just array_count?
      $test_suite->setAttribute('tests', $group_tests);
      $test_suite->setAttribute('failures', $group_failures);
      $test_suite->setAttribute('errors', $group_exceptions);
      /* TODO: Someday simpletest will disable or skip tests based on environment
      $test_group->setAttribute('disabled', $test_group_id);
      $test_group->setAttribute('skipped', $test_group_id);
      */
      $test_suites->appendChild($test_suite);
      $test_group_id++;
    }
    $test_suites->setAttribute('tests', $total_tests);
    $test_suites->setAttribute('failures', $total_failures);
    // $test_suites->setAttribute('disabled', "TODO SET");
    $test_suites->setAttribute('errors', $total_exceptions);
    $doc->appendChild($test_suites);

    file_put_contents($this->getArtifact('xmlresults')->getPath(), $doc->saveXML());
    $this->io->writeln("<info>Reformatted test results written to <options=bold>" . $this->getArtifact('xmlresults')->getPath() . '</options=bold></info>');
  }


}
