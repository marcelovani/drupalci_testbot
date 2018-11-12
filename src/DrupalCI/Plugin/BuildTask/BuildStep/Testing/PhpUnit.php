<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\Testing;

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

  /**
   * The current container environment
   *
   * @var  \DrupalCI\Build\Environment\EnvironmentInterface
   */
  protected $environment;

  protected $runscript = '/vendor/phpunit/phpunit/phpunit';

  public function inject(Container $container) {
    parent::inject($container);
    $this->environment = $container['environment'];
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      // Directory assumed to be the project directory, where you can just type
      // 'phpunit' and the test will work. Should contain a phpunit.xml file.
      'working-directory' => '',
      // Testsuite configured in phpunit.xml. Empty runs all the suites.
      'testsuite' => '',
      'color' => TRUE,
      'stop-on-error' => TRUE,
      'stop-on-failure' => FALSE,
    ];
  }

  /**
   * @inheritDoc
   */
  public function run() {
    $label = '';
    if (isset($this->pluginLabel)) {
      $label = $this->pluginLabel . ".";
    }

    $working_directory = $this->environment->getExecContainerSourceDir() . '/' . $this->configuration['working-directory'];

    $log_junit_file = $label . 'phpunit.junit.xml';
    $results_directory = 'junit';
    $this->configuration['log-junit'] = $this->environment->getExecContainerSourceDir() . '/' . $results_directory . '/' . $log_junit_file;

    // Make a results directory.
    $result = $this->environment->executeCommands(
      "cd $working_directory && sudo -u www-data mkdir $results_directory"
    );

    // Build a phpunit command.
    $command = ["cd $working_directory && sudo -u www-data " . $this->environment->getExecContainerSourceDir() . $this->runscript];
    $command[] = $this->getPhpUnitFlagValues($this->configuration);
    $command[] = $this->getPhpUnitValues($this->configuration);

    $command_line = implode(' ', $command);

    $result = $this->environment->executeCommands($command_line);

    // Look at the output for no valid tests, and set that to an acceptable signal.
    if (strpos($result->getOutput(), 'ERROR: No valid tests were specified.') !== FALSE){
      $result->setSignal(0);
    }

    $this->saveContainerArtifact($this->environment->getExecContainerSourceDir() . '/' . $results_directory, $results_directory);

    $this->saveStringArtifact($label . 'phpunitoutput.txt', $result->getOutput());
    $this->saveStringArtifact($label . 'phpuniterror.txt', $result->getError());

    return $result->getSignal();
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
      'testsuite',
      'log-junit',
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
