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

    // Check the execution environment first because we want the user to know
    // this problem first.
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

    switch ($this->configuration['execution-environment']) {
      case 'host':
        return $this->executeOnHost(
          $this->configuration['commands'],
          $this->configuration['die-on-nonzero']
        );

      case 'php-container':
        return $this->executeOnPhpContainer(
          $this->configuration['commands'],
          $this->configuration['die-on-nonzero']
        );
    }
    return 0;
  }

  /**
   * Execute the commands on the host environment.
   *
   * @param type $commands
   * @param type $die_on_fail
   * @return int
   * @throws BuildTaskException
   */
  protected function executeOnHost($commands, $die_on_fail) {
    // @todo: Add stuff for contrib.
    // @todo: Don't die if we're configured not to.
    if (!chdir($this->codebase->getSourceDirectory())) {
      $this->terminateBuild('Unable to change working directory to source directory.');
    }
    foreach ($commands as $key => $command) {
      try {
        $this->execWithArtifact($command, 'command_output.' . $key);
      } catch (BuildTaskException $e) {
        if ($die_on_fail) {
          throw $e;
        }
      }
    }
    return 0;
  }

  /**
   * Execute the commands in the PHP container.
   *
   * @param string[] $commands
   * @param bool $die_on_fail
   * @return int
   * @throws BuildTaskException
   *
   * @todo: Explicitly set the container in executeCommands().
   */
  protected function executeOnPhpContainer($commands, $die_on_fail) {
    // @todo: Add contrib path stuff.
    $commands = array_merge(
      ['cd ' . $this->environment->getExecContainerSourceDir()],
      $commands
    );

    $result = $this->environment->executeCommands($commands);
    $this->saveStringArtifact('php-container-command', $result->getOutput());
    if ($result->getSignal() != 0) {
      if ($die_on_fail) {
        $this->terminateBuild('Unable to execute in PHP container.', $result->getError());
      }
    }
    return 0;
  }

}
