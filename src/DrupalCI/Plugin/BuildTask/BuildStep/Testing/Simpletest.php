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
      $this->configuration['url'] = getenv('DCI_RTUrl');
    }
    if (FALSE !== getenv('DCI_RTColor')) {
      $this->configuration['color'] = getenv('DCI_RTColor');
    }
    if (FALSE !== getenv('DCI_TestGroups')) {
      $this->configuration['testgroups'] = getenv('DCI_TestGroups');
    }
    if ((FALSE !== getenv('DCI_ProjectType')) && (getenv('DCI_ProjectType') != 'core')) {
      $this->configuration['extension_test'] = TRUE;
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
    if (FALSE !== getenv('DCI_RTSuppressDeprecations')) {
      $this->configuration['suppress-deprecations'] = getenv('DCI_RTSuppressDeprecations');
    }
  }

  /**
   * @inheritDoc
   */
  public function run() {
    $this->prepareFilesystem();
    $this->setupSimpletestDB($this->build);
    // generateTestGroups will terminate the build if there's an error.
    $this->generateTestGroups();


    $result = $this->environment->executeCommands($this->getRunTestsCommand());

    // Allow a pass if no tests are found. This allows DrupalCI to be used for
    // lint/coding standards only.
    // @todo Change this when projects can have their own build file in
    //   https://www.drupal.org/project/drupalci_testbot/issues/2901677
    if (strpos($result->getOutput(), 'ERROR: No valid tests were specified.') !== FALSE) {
      $result->setSignal(0);
    }

    // Last thing. JunitFormat the output.
    $this->generateJunitXml();

    // Save some artifacts for the build
    $this->saveContainerArtifact('/var/log/apache2/error.log','apache-error.log');
    $this->saveContainerArtifact('/var/log/apache2/test.apache.error.log','test.apache.error.log');
    //    $this->saveContainerArtifact('/var/log/supervisor/phantomjs.err.log','phantomjs.err.log');
    //    $this->saveContainerArtifact('/var/log/supervisor/phantomjs.out.log','phantomjs.out.log');
    $this->saveContainerArtifact($this->environment->getExecContainerSourceDir() . '/sites/default/files/simpletest','phpunit-xml');
    $this->saveContainerArtifact($this->environment->getExecContainerSourceDir() . '/sites/simpletest/browser_output','simpletest_html');

    $this->saveStringArtifact('simpletestoutput.txt', $result->getOutput());
    $this->saveStringArtifact('simpletesterror.txt', $result->getError());

    // Run-tests.sh can return 0, 1, or 2. If the container exec() does not
    // return any of those values, it's a PHP fatal.
    // Jenkins will fail the build if it receives a 1, but we don't want it to
    // do that. D.O has the responsibility for displaying the fail.
    // Therefore: Return 0 for both 0 and 1. All other states result in
    // terminateBuild().
    $signal = $result->getSignal();
    switch ($signal) {
      case 0:
      case 1:
        $signal = 0;
        break;

      case 2:
        $this->terminateBuild('Simpletest exception', $result->getOutput() . "\n\n" . $result->getError());
        break;

      default:
        $this->terminateBuild('Simpletest fatal error', $result->getError());
        break;
    }
    return $signal;
  }

  protected function getRunTestsCommand() {
    // Figure out if this is a contrib test.
    $is_extension_test = isset($this->configuration['extension_test']) && ($this->configuration['extension_test']);
    $environment_variables = 'MINK_DRIVER_ARGS_WEBDRIVER=\'["chrome", {"browserName":"chrome","chromeOptions":{"args":["--disable-gpu","--headless"]}}, "http://' . $this->environment->getChromeContainerHostname() . ':9515"]\'';
    $command = ["cd " . $this->environment->getExecContainerSourceDir() . " && sudo " . $environment_variables . " -u www-data php " . $this->environment->getExecContainerSourceDir() . $this->runscript];
    if ($is_extension_test) {
      // Always add --suppress-deprecations for contrib, if its available
      $suppress_check_command = "sudo -u www-data php /var/www/html/core/scripts/run-tests.sh --help |grep suppress";
      $result = $this->environment->executeCommands($suppress_check_command);
      // @todo Turn this off when some other solution is decided in
      //   https://www.drupal.org/project/drupal/issues/2607260

      if ($result->getSignal() == 0) {
        $this->configuration['suppress-deprecations'] = TRUE;
      }
    }

    // Parse the flags and optional values.
    $command[] = $this->getRunTestsFlagValues($this->configuration);
    $command[] = $this->getRunTestsValues($this->configuration);

    // If its a contrib test, then either empty, --all, or a --directory
    // switch needs to be converted to use our TrueExtensionSubDirectory.
    if ($is_extension_test && (empty($this->configuration['testgroups']) || ($this->configuration['testgroups'] == '--all') || (substr($this->configuration['testgroups'], 0, 11 ) == "--directory"))) {
      $command[] = "--directory " . $this->codebase->getTrueExtensionSubDirectory();
    }
    else {
      // Add the test groups last, if this is not an extension test.
      $command[] = $this->configuration['testgroups'];
    }

    return implode(' ', $command);
  }

  /**
   * @inheritDoc
   */
  public function complete($childStatus) {

    $gdbcommands = ['source /usr/src/php/.gdbinit','bt','zbacktrace','q' ];
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
      'types' => 'Simpletest,PHPUnit-Unit,PHPUnit-Kernel,PHPUnit-Functional',
      'color' => TRUE,
      'die-on-fail' => FALSE,
      'keep-results' => TRUE,
      'keep-results-table' => FALSE,
      'verbose' => FALSE,
      'concurrency' => 0,
      // testing modules or themes?
      'extension_test' => FALSE,
      'suppress-deprecations' => FALSE,
    ];
  }

  /**
   * Prepare the filesystem for a run-tests.sh run.
   *
   */
  protected function prepareFilesystem() {
    $sourcedir = $this->environment->getExecContainerSourceDir();
    $setup_commands = [
      'mkdir -p ' . $sourcedir . '/sites/simpletest/xml',
      'ln -s ' . $sourcedir . ' ' . $sourcedir . '/subdirectory',
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
    $this->configuration['sqlite'] = $this->environment->getContainerWorkDir() . '/' . $this->pluginDir . '/' . $sqlite_db_filename;
    $dbfile = $this->pluginWorkDir . '/' . $sqlite_db_filename;
    $this->results_database->setDBFile($dbfile);
    $this->results_database->setDbType('sqlite');
    // $this->saveContainerArtifact($this->configuration['sqlite'], $sqlite_db_filename);
  }

  /**
   * Generate a list of tests and groups.
   *
   * @return int
   *   The shell result code.
   *
   * @throws BuildTaskException
   *   Thrown if there was an error generating the list.
   */
  protected function generateTestGroups() {
    $testgroups_file = $this->environment->getContainerWorkDir() . '/' . $this->pluginDir . '/testgroups.txt';
    $cmd = 'sudo -u www-data php ' . $this->environment->getExecContainerSourceDir() . $this->runscript . ' --list > ' . $testgroups_file;
    $result = $this->environment->executeCommands($cmd);
    if ($result->getSignal() !== 0) {
      $this->terminateBuild('Unable to generate test groups', $result->getError());
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
      'suppress-deprecations',
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
    $config['dburl'] = $this->system_database->getUrl();
    // Unless somebody has provided a local url, the url should be blank, and
    // Should then be set to the hostname of the executable container.
    // We dont want this to be in the configuration itself as then it would get
    // saved to the build.yml, and persisted, and the hostnames will change.
    // In a perfect world we wouldnt be getting the hostname directly off of the
    // container, but from a better abstraction. but we're gonna gut that part
    // anyhow for a docker compose build methodology.
    if (empty($config['url'])) {
      $config['url'] = 'http://' . $this->environment->getExecContainer()['name'] . '/subdirectory';
    }
    if (empty($config['concurrency'])) {
      $config['concurrency'] = $this->environment->getHostProcessorCount();
    }

    foreach ($config as $key => $value) {
      // Temporary backwards compatibility fix for https://www.drupal.org/node/2906212
      // This will allow us to use older build.yml files. Remove after Feb 2018 or so.
      if ($key == 'url') {
        $value = preg_replace('/checkout/','subdirectory',$value);
      }
      //
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
