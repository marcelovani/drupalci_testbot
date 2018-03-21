<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\Utility;

use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskException;
use Pimple\Container;

/**
 * @PluginID("host_command")
 */
class Command extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface {

  /**
   * The codebase.
   *
   * @var \DrupalCI\Build\Codebase\CodebaseInterface
   */
  protected $codebase;

  /**
   * {@inheritdoc}
   */
  public function inject(Container $container) {
    parent::inject($container);
    $this->codebase = $container['codebase'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfiguration() {
    return [
      'die-on-nonzero' => FALSE,
      'commands' => [],
    ];
  }

  /**
   * @inheritDoc
   */
  public function run() {
    $this->io->writeln('<info>Host command.</info>');

    // Don't do anything if there's nothing to do.
    if (empty($this->configuration['commands'])) {
      $this->io->writeln('No commands to execute.');
      return 0;
    }

    // Normalize on arrays.
    if (!is_array($this->configuration['commands'])) {
      $this->configuration['commands'] = [$this->configuration['commands']];
    }

    // Execute.
    return $this->execute($this->configuration['commands'], $this->configuration['die-on-nonzero']);
  }

  /**
   * Execute the commands on the host environment.
   *
   * @param $commands
   * @param $die_on_fail
   *
   * @return int
   * @throws \DrupalCI\Plugin\BuildTask\BuildTaskException
   */
  protected function execute($commands, $die_on_fail) {

    // Set some environment variables for these executions.
    $this->command_environment['HOST_SOURCE_DIR'] = $this->codebase->getSourceDirectory();
    $this->command_environment['HOST_PROJECT_DIR'] = $this->codebase->getSourceDirectory(). '/' . $this->codebase->getTrueExtensionSubDirectory();
    $this->command_environment['TEST_SOURCE_DIR'] = $this->environment->getExecContainerSourceDir();
    $this->command_environment['TEST_PROJECT_DIR'] = $this->environment->getExecContainerSourceDir() . '/' . $this->codebase->getTrueExtensionSubDirectory();

    // TODO: Loop through $commands and set
    $this->codebase->getSourceDirectory();

    if ($die_on_fail) {
      $result = $this->execRequiredCommands($commands, 'Custom Commands Failed');
    }
    else {
      $result = $this->execCommands($commands);
    }
    // exedRequiredComannds should terminate The build further down if there's
    // an error. And since we have no idea what to do with a custom command
    // that isnt required, we'll just return 0 at this point.
    // Maybe this should return $result->getSignal() instead and make sure
    // devs know about 0, 1, and 2 ?
    return 0;
  }

}
