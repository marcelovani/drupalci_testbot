<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\Testing;

use DrupalCI\Build\Artifact\Junit\JunitXmlBuilder;
use DrupalCI\Build\BuildInterface;
use DrupalCI\Build\Environment\Environment;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * @PluginID("simpletest")
 */
class Simpletest extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface, Injectable {

  /* @var  \DrupalCI\Build\Environment\DatabaseInterface */
  protected $system_database;

  /**
   * @var \DrupalCI\Build\Environment\DatabaseInterface
   */
  protected $results_database;

  /**
   * The current container environment
   *
   * @var  \DrupalCI\Build\Environment\EnvironmentInterface
   */
  protected $environment;

  /* @var \DrupalCI\Build\Codebase\CodebaseInterface */
  protected $codebase;

  /**
   * The path to run-tests.sh.
   *
   * @var string
   */
  protected $runscript = '/core/scripts/run-tests.sh';

  /**
   * Junit XML builder service.
   *
   * @var \DrupalCI\Build\Artifact\Junit\JunitXmlBuilder
   */
  protected $junitXmlBuilder;

  public function inject(Container $container) {
    parent::inject($container);
    $this->system_database = $container['db.system'];
    $this->results_database = $container['db.results'];
    $this->environment = $container['environment'];
    $this->codebase = $container['codebase'];
    $this->junitXmlBuilder = $container['junit_xml_builder'];
  }

  /**
   * @inheritDoc
   */
  public function configure() {
    // Override any Environment Variables
    if (FALSE !== getenv('DCI_Concurrency')) {
      $this->configuration['concurrency'] = getenv('DCI_Concurrency');
    }
    if (FALSE !== getenv('DCI_RTTypes')) {
      $this->configuration['types'] = getenv('DCI_RTTypes');
    }
    if (FALSE !== getenv('DCI_RTUrl')) {
      $this->configuration['types'] = getenv('DCI_RTUrl');
    }
    if (FALSE !== getenv('DCI_RTColor')) {
      $this->configuration['color'] = getenv('DCI_RTColor');
    }
    if (FALSE !== getenv('DCI_TestItem')) {
      $this->configuration['testgroups'] = $this->parseTestItems(getenv('DCI_TestItem'));
    }
    if (FALSE !== getenv('DCI_RTDieOnFail')) {
      $this->configuration['die-on-fail'] = getenv('DCI_RTDieOnFail');
    }
    if (FALSE !== getenv('DCI_RTKeepResults')) {
      $this->configuration['keep-results'] = getenv('DCI_RTKeepResults');
    }
    if (FALSE !== getenv('DCI_RTKeepResultsTable')) {
      $this->configuration['keep-results-table'] = getenv('DCI_RTKeepResultsTable');
    }
    if (FALSE !== getenv('DCI_RTVerbose')) {
      $this->configuration['verbose'] = getenv('DCI_RTVerbose');
    }
  }

  /**
   * @inheritDoc
   */
  public function run() {
    $this->prepareFilesystem();

    $this->setupSimpletestDB($this->build);
    $status = $this->generateTestGroups();
    if ($status > 0) {
      return $status;
    }
    $command = ["cd " . $this->environment->getExecContainerSourceDir() . " && sudo -u www-data php " . $this->environment->getExecContainerSourceDir() . $this->runscript];
    $this->configuration['dburl'] = $this->system_database->getUrl();
    $command[] = $this->getRunTestsFlagValues($this->configuration);
    $command[] = $this->getRunTestsValues($this->configuration);

    if (isset($this->configuration['extension_test']) && ($this->configuration['extension_test'])) {
      $command[] = "--directory " . $this->codebase->getTrueExtensionSubDirectory();
    }
    else {
      $command[] = $this->configuration['testgroups'];
    }
    $command_line = implode(' ', $command);

    $result = $this->environment->executeCommands($command_line);

    // Look at the output for no valid tests, and set that to an acceptable signal.
    if (strpos($result->getOutput(), 'ERROR: No valid tests were specified.') !== FALSE) {
      $result->setSignal(0);
    }
    // Last thing. JunitFormat the output.
    $this->generateJunitXml();

    //Save some artifacts for the build

    $this->saveContainerArtifact('/var/log/apache2/error.log','apache-error.log');
    $this->saveContainerArtifact('/var/log/apache2/test.apache.error.log','test.apache.error.log');
    $this->saveContainerArtifact('/var/log/supervisor/phantomjs.err.log','phantomjs.err.log');
    $this->saveContainerArtifact('/var/log/supervisor/phantomjs.out.log','phantomjs.out.log');
    $this->saveContainerArtifact($this->environment->getExecContainerSourceDir() . '/sites/default/files/simpletest','phpunit-xml');

    $this->saveStringArtifact('simpletestoutput.txt', $result->getOutput());
    $this->saveStringArtifact('simpletesterror.txt', $result->getError());

    // TODO: Jenkins fails the build if it sees a 1 in a shell script execution.
    // So we return a 0 here instead.
    //return $result->getSignal();
    return 0;
  }

  /**
   * @inheritDoc
   */
  public function complete($childStatus) {

    $gdbcommands = ['source /usr/src/php/.gdbinit','bt','zbacktrace','q', ];
    $gdb_command_file = $this->pluginWorkDir . '/debugscript.gdb';
    file_put_contents($gdb_command_file, implode("\n", $gdbcommands));
    $phpcoredumps = glob('/var/lib/drupalci/coredumps/core.php*');
    $container_command_file = $this->environment->getContainerWorkDir() . '/' . $this->pluginDir . '/debugscript.gdb';
    foreach ($phpcoredumps as $core_file) {
      $command = "gdb -exec=/usr/local/bin/php -symbols=/usr/local/bin/php -core=$core_file -command=$container_command_file 2>&1";
      $response = $this->environment->executeCommands($command);
      $this->saveStringArtifact(basename($core_file) . ".debug", $response->getOutput());
      if (FALSE === (getenv('DCI_Debug'))) {
        $cmd = "sudo rm -rf $core_file";
        $this->exec($cmd, $cmdoutput, $result);
      }
    }

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
      'color' => TRUE,
      'die-on-fail' => FALSE,
      'keep-results' => TRUE,
      'keep-results-table' => FALSE,
      'verbose' => FALSE,
      // testing modules or themes?
      'extension_test' => FALSE,
    ];
  }

  protected function parseTestItems($testitem) {
    // Special case for 'all'
    if (strtolower($testitem) === 'all') {
      return '--all';
    }

    // Split the string components
    $components = explode(':', $testitem);
    if (!in_array($components[0], array('module', 'class', 'file', 'directory'))) {
      // Invalid entry.
      return $testitem;
    }

    $testgroups = '--' . $components[0] . ' ' . $components[1];
    // Perhaps this crude hack could go somewhere else.
    // If this is a directory testItem, flag it as an extension test.
    if ($components[0] == 'directory') {
      $this->configuration['extension_test'] = TRUE;
    }
    return $testgroups;
  }

  /**
   * Prepare the filesystem for a run-tests.sh run.
   *
   */
  protected function prepareFilesystem() {
    $sourcedir = $this->environment->getExecContainerSourceDir();
    $setup_commands = [
      'mkdir -p ' . $sourcedir . '/sites/simpletest/xml',
      'ln -s ' . $sourcedir . ' ' . $sourcedir . '/checkout',
      'chown -fR www-data:www-data ' . $sourcedir . '/sites',
      'chmod 0777 ' . $this->environment->getContainerArtifactDir(),
      'chmod 0777 /tmp',
    ];
    $result = $this->environment->executeCommands($setup_commands);
    $return = $result->getSignal();
    if ($return !== 0) {
      // Directory setup failed threw an error.
      $this->terminateBuild("Prepare Simpletest filesystem failed", "Setting up the filesystem failed:  Error Code: $return");
    }
    return $return;
  }

  protected function setupSimpletestDB(BuildInterface $build) {

    // This is a rare instance where we're meddling with config after the object
    // is underway. Perhaps theres a better way?
    $sqlite_db_filename = 'simpletest.sqlite';
    $this->configuration['sqlite'] = $this->environment->getContainerWorkDir() . '/' . $this->pluginDir .  '/' . $sqlite_db_filename;
    $dbfile = $this->pluginWorkDir . '/' . $sqlite_db_filename;
    $this->results_database->setDBFile($dbfile);
    $this->results_database->setDbType('sqlite');
    $this->saveContainerArtifact($this->configuration['sqlite'], $sqlite_db_filename);
  }

  protected function generateTestGroups() {
    $testgroups_file = $this->environment->getContainerWorkDir() . '/' . $this->pluginDir. '/testgroups.txt';
    $cmd = 'sudo -u www-data php ' . $this->environment->getExecContainerSourceDir() . $this->runscript . ' --list > ' . $testgroups_file;
    $result = $this->environment->executeCommands($cmd);
    if ($result->getSignal() !== 0) {
      $this->terminateBuild('Unable to generate test groups',$result->getError());
    }
    $host_testgroups = $this->pluginWorkDir . '/testgroups.txt';
    $this->saveContainerArtifact($testgroups_file,'testgroups.txt');
    return $result->getSignal();
  }

  /**
   * Parse groups into useable form.
   *
   * @param string[] $test_list
   *   The output of run-tests.sh --list, parsed to an array.
   *
   * @return string[]
   *   Array of groups, keyed by class name.
   */
  protected function parseGroups($test_list) {
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
   * @param array $config
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
    foreach ($config as $key => $value) {
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
   * @param array $config
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
  public function generateJunitXml() {
    // Load the list of tests from the testgroups.txt build artifact
    // This gets generated in the containers, into the container artifact dir
    $test_listfile = $this->pluginWorkDir . '/testgroups.txt';
    $test_list = file($test_listfile, FILE_IGNORE_NEW_LINES);
    $test_list = array_slice($test_list, 4);

    $test_groups = $this->parseGroups($test_list);

    $doc = $this->junitXmlBuilder->generate($test_groups);

    $label = '';
    if (isset($this->pluginLabel)) {
      $label = $this->pluginLabel . ".";
    }
    $xml_output_file = $this->build->getXmlDirectory() . "/" . $label . "testresults.xml";
    file_put_contents($xml_output_file, $doc->saveXML());
    $this->io->writeln("<info>Reformatted test results written to <options=bold>" . $xml_output_file . "</></info>");
    $this->build->addArtifact($xml_output_file, 'xml/' . $label . "testresults.xml");
  }

}
