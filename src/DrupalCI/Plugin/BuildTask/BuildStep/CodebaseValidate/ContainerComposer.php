<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseValidate;

use DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble\Composer;
use Pimple\Container;

/**
 * Runs Composer inside the container.
 *
 * Subclass the other composer class, so we inherit default config.
 *
 * @PluginID("container_composer")
 */
class ContainerComposer extends Composer {

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
    return array_merge(
      parent::getDefaultConfiguration(),
      [
        'halt-on-fail' => TRUE,
      ]
     );
  }

  /**
   * @inheritDoc
   */
  public function run() {
    $this->io->writeln('<info>Running Composer within the environment.</info>');

    // Build a containerized Composer command to ignore/discard changes
    $command = [ 'COMPOSER_ALLOW_SUPERUSER=TRUE',
      $this->executable_path,
      'config -g discard-changes true',
    ];
    $commands[] = implode(' ', $command);
    $result = $this->execEnvironmentCommands($commands);

    // Build a containerized Composer command.
    $command = [ 'COMPOSER_ALLOW_SUPERUSER=TRUE',
      $this->executable_path,
      $this->configuration['options'],
      '--working-dir ' . $this->environment->getExecContainerSourceDir(),
    ];
    $commands[] = implode(' ', $command);

    if ($this->configuration['halt-on-fail']) {
      $result = $this->execRequiredEnvironmentCommands($commands, 'Composer error. Unable to continue.');
    }
    else {
      $result = $this->execEnvironmentCommands($commands);
    }

    return 0;
  }

}
