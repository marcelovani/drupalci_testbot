<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\Testing;


use DrupalCI\Build\BuildInterface;
use DrupalCI\Build\Environment\Environment;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * Test-runner plugin for PHPUnit.
 *
 * This plugin is optimized for dogfood purposes. That is, it's being developed
 * so that we can use the testbot for testing the testbot. It may not be
 * appropriate for your project.
 *
 * Assumptions:
 * - We can just run phpunit at the root of the project directory without any
 *   special configuration needs for the project itself.
 *
 * @PluginID("phpunit")
 */
class PhpUnit extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface, Injectable  {

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

  /* @var \DrupalCI\Build\Codebase\CodebaseInterface */
  protected $codebase;

  protected $runscript = '/bin/phpunit';

  public function inject(Container $container) {
    parent::inject($container);
    $this->system_database = $container['db.system'];
    $this->results_database = $container['db.results'];
    $this->environment = $container['environment'];
    $this->codebase = $container['codebase'];
  }

  /**
   * {@inheritDoc}
   */
  public function configure() {
    // Override any Environment Variables
    if (isset($_ENV['DCI_PHPInterpreter'])) {
      $this->configuration['php'] = $_ENV['DCI_PHPInterpreter'];
    }
  }

  /**
   * @inheritDoc
   */
  public function run() {
    // Build a phpunit command.
    $command = ["cd " . $this->environment->getExecContainerSourceDir() . " && sudo -u www-data php " . $this->environment->getExecContainerSourceDir() . $this->runscript];
//    $this->configuration['dburl'] = $this->system_database->getUrl();
    $command[] = $this->getPhpUnitFlagValues($this->configuration);
    $command[] = $this->getPhpUnitValues($this->configuration);
    // Special case for log-junit, since it's the file name rather than the
    // whole path.
/*    if (isset($this->configuration['log-junit'])) {
      $command['log-junit'] = $this->build->getXmlDirectory() . "/" . $this->pluginLabel . "testresults.xml";
    }*/

    $command_line = implode(' ', $command);

    $result = $this->environment->executeCommands($command_line);

    // Look at the output for no valid tests, and set that to an acceptable signal.
    if (strpos($result->getOutput(), 'ERROR: No valid tests were specified.') !== FALSE){
      $result->setSignal(0);
    }

    //Save some artifacts for the build
    $this->build->addContainerArtifact("/var/log/apache2/error.log");
    $this->build->addContainerArtifact("/var/log/supervisor/phantomjs.err.log");
    $this->build->addContainerArtifact("/var/log/supervisor/phantomjs.out.log");

    $label = '';
    if (isset($this->pluginLabel)) {
      $label = $this->pluginLabel . ".";
    }

    $this->build->saveStringArtifact($label . 'phpunitoutput.txt', $result->getOutput());
    $this->build->saveStringArtifact($label . 'phpuniterror.txt', $result->getError());

    return $result->getSignal();
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'php' => '/opt/phpenv/shims/php',
      'color' => TRUE,
      'stop-on-error' => TRUE,
      'stop-on-failure' => FALSE,
      'log-junit' => $this->pluginLabel . '.testresults.xml',
    ];
  }

  protected function parseTestItems($testitem) {
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
    // Perhaps this crude hack could go somewhere else.
    // If this is a directory testItem, flag it as an extension test.
    if ($components[0] == 'directory') {
      $this->configuration['extension_test'] = TRUE;
    }
    return $testgroups;
  }

  protected function setupSimpletestDB(BuildInterface $build) {


    // This is a rare instance where we're meddling with config after the object
    // is underway. Perhaps theres a better way?
    $this->configuration['sqlite'] = $this->environment->getContainerArtifactDir() . "/simpletest" . $this->pluginLabel .".sqlite";
    $dbfile = $this->build->getArtifactDirectory() . "/simpletest" . $this->pluginLabel .".sqlite";
    $this->results_database->setDBFile($dbfile);
    $this->results_database->setDbType('sqlite');
    $this->build->addContainerArtifact($this->configuration['sqlite']);
  }

  protected function generateTestGroups() {
    $testgroups_file = $this->environment->getContainerArtifactDir() . "/testgroups.txt";
    $cmd = "sudo -u www-data php " . $this->environment->getExecContainerSourceDir() . $this->runscript . " --list --php " . $this->configuration['php'] . " > " . $testgroups_file;
    $result = $this->environment->executeCommands($cmd);
    $host_testgroups = $this->build->getArtifactDirectory() . '/testgroups.txt';
    $this->build->addContainerArtifact($testgroups_file);
    return $result->getSignal();
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
   * @param array $config
   *   This plugin's config, from run().
   *
   * @return string
   *   The assembled command line fragment.
   */
  protected function getPhpUnitFlagValues($config) {
    $command = [];
    $flags = [
      'color',
      'stop-on-error',
      'stop-on-failure',
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
   * @param array $config
   *   This plugin's config, from run().
   *
   * @return string
   *   The assembled command line fragment.
   */
  protected function getPhpUnitValues($config) {
    $command = [];
    $args = [
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

}
