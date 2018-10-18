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
      'halt-on-fail' => FALSE,
      'commands' => [],
      'artifacts' => [],
    ];
  }

  /**
   * @inheritDoc
   */
  public function run() {

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
    return $this->execute($this->configuration['commands'], $this->configuration['halt-on-fail']);
  }

  /**
   * {@inheritdoc}
   */
  public function complete($childStatus) {

    foreach ($this->configuration['artifacts'] as $artifact) {
      $artifact['source'] = str_replace('${SOURCE_DIR}', $this->codebase->getSourceDirectory(), $artifact['source']);
      $artifact['source'] = str_replace('${PROJECT_DIR}', $this->codebase->getProjectSourceDirectory(), $artifact['source']);
      // Save any defined artifacts at the end
      $this->saveHostArtifact($artifact['source'], $artifact['destination']);
    }

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
    $this->io->writeln('<info>Host command.</info>');

    // Set some environment variables for these executions.
    $this->command_environment['SOURCE_DIR'] = $this->codebase->getSourceDirectory();
    $this->command_environment['PROJECT_DIR'] = $this->codebase->getProjectSourceDirectory();

    // TODO: Loop through $commands and set
    $this->codebase->getSourceDirectory();

    if ($die_on_fail) {
      $result = $this->execRequiredCommands($commands, 'Custom Commands Failed');
    }
    else {
      $result = $this->execCommands($commands);
    }
    // execRequiredCommands() should terminate the build if there's an error.
    return 0;
  }

}
