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
    // Override any Environment Variables
    if (isset($_ENV['DCI_PHPInterpreter'])) {
      $this->configuration['php'] = $_ENV['DCI_PHPInterpreter'];
    }
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
    // Build a phpunit command.
    $command = ["cd " . $this->environment->getExecContainerSourceDir() . " && sudo -u www-data php " . $this->environment->getExecContainerSourceDir() . $this->runscript];
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

  protected function generateTestGroups() {
    $testgroups_file = $this->environment->getContainerArtifactDir() . "/phpunit.testgroups.txt";
    $cmd = "sudo -u www-data php " . $this->environment->getExecContainerSourceDir() . $this->runscript . " --list-groups > " . $testgroups_file;
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
