<?php

namespace DrupalCI\Plugin\BuildTask\BuildPhase;

use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskException;
use Pimple\Container;

/**
 * @PluginID("pre_assessment")
 */
class PreAssessmentPhase extends BuildTaskBase implements BuildPhaseInterface, BuildTaskInterface {

  /**
   * The testing environment.
   *
   * @var \DrupalCI\Build\Environment\EnvironmentInterface
   */
  protected $environment;
  protected $codebase;

  /**
   * {@inheritdoc}
   */
  public function inject(Container $container) {
    parent::inject($container);
    $this->environment = $container['environment'];
    $this->codebase = $container['codebase'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfiguration() {
    return [
      'die-on-nonzero' => FALSE,
      'execution-environment' => 'host',
      'commands' => [],
    ];
  }

  /**
   * Sanity check on the execution environment.
   *
   * @throws BuildTaskException
   *   If we're configured to die on nonzero, then we'll throw an exception if
   *   the execution environment is misconfigured.
   *
   * @todo Unit test this.
   */
  protected function checkExecutionEnvironment() {
    if (!in_array($this->configuration['execution-environment'], ['host', 'php-container'])) {
      $message = 'Unknown execution environment: ' . $this->configuration['execution-environment'];
      if ($this->configuration['die-on-nonzero']) {
        $this->terminateBuild($message);
      }
      $this->io->writeln("<error>$message</error>");
      return;
    }
    $this->io->writeln('Using execution environment: ' . $this->configuration['execution-environment']);
  }

  /**
   * @inheritDoc
   */
  public function run() {
    $this->io->writeln('<info>Pre-assessment phase.</info>');

    $this->checkExecutionEnvironment();

    // Don't do anything if there's nothing to do.
    if (empty($this->configuration['commands'])) {
      $this->io->writeln('No commands to execute.');
      return 0;
    }

    // Normalize on arrays.
    if (!is_array($this->configuration['commands'])) {
      $this->configuration['commands'] = [$this->configuration['commands']];
    }

    // @todo: Add stuff for contrib.
    if (!chdir($this->codebase->getSourceDirectory())) {
      $this->terminateBuild('Unable to change working directory to source directory.');
    }

    switch ($this->configuration['execution-environment']) {
      case 'host':
        $this->executeOnHost($this->configuration['commands'], $this->configuration['die-on-nonzero']);
        break;

      case 'php-container':
        $this->executeOnPhpContainer($this->configuration['commands'], $this->configuration['die-on-nonzero']);
        break;
    }

    return 0;
  }

  protected function executeOnHost($commands, $die_on_fail) {
    foreach ($commands as $key => $command) {
      try {
        $this->execWithArtifact($command, 'command_output.' . $key);
      } catch (BuildTaskException $e) {
        if ($die_on_fail) {
          throw $e;
        }
      }
    }
  }

  protected function executeOnPhpContainer($commands, $die_on_fail) {
    
  }

}
