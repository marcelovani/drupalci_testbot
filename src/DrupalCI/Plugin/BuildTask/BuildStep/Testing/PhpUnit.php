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

  /**
   * The current container environment
   *
   * @var  \DrupalCI\Build\Environment\EnvironmentInterface
   */
  protected $environment;

  protected $runscript = '/bin/phpunit';

  public function inject(Container $container) {
    parent::inject($container);
    $this->environment = $container['environment'];
  }

  /**
   * {@inheritDoc}
   */
  public function configure() {
    // Override any Configuration Variables

  }

  /**
   * @inheritDoc
   */
  public function run() {
    // Generate the testgroups as an artifact.
    $status = $this->generateTestGroups();
    if ($status > 0) {
      return $status;
    }
    // Special case for log-junit, since it's the file name rather than the
    // whole path.
    if (isset($this->configuration['log-junit'])) {
      // TODO: obvs we need to have that xml dir inside the container too.
      $this->configuration['log-junit'] = $this->environment->getContainerArtifactDir() . "/xml/" . $this->configuration['log-junit'];
    }
    // Build a phpunit command.
    $command = ["cd " . $this->environment->getExecContainerSourceDir() . " && sudo -u www-data php " . $this->environment->getExecContainerSourceDir() . $this->runscript];
    $command[] = $this->getPhpUnitFlagValues($this->configuration);
    $command[] = $this->getPhpUnitValues($this->configuration);

    $command_line = implode(' ', $command);

    $result = $this->environment->executeCommands($command_line);

    // Look at the output for no valid tests, and set that to an acceptable signal.
    if (strpos($result->getOutput(), 'ERROR: No valid tests were specified.') !== FALSE){
      $result->setSignal(0);
    }

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
      'color' => TRUE,
      'stop-on-error' => TRUE,
      'stop-on-failure' => FALSE,
      'log-junit' => 'phpunit.testresults.xml',
    ];
  }

  protected function generateTestGroups() {
    $testgroups_file = $this->environment->getContainerArtifactDir() . "/phpunit.testgroups.txt";
    $cmd = "cd " . $this->environment->getExecContainerSourceDir() . " && sudo -u www-data php " . $this->environment->getExecContainerSourceDir() . $this->runscript . " --list-groups > " . $testgroups_file;
    $result = $this->environment->executeCommands($cmd);
    $this->build->addContainerArtifact($testgroups_file);
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
