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
    if ('TRUE' === strtoupper(getenv('DCI_Debug'))) {
      $verbose = ' -vvv';
      $progress = '';
    } else {
      $verbose = '';
      $progress = ' --no-progress';
    }
    return array_merge(
      parent::getDefaultConfiguration(),
      [
        // The 'options' configuration should be exactly the same as parent
        // config, but without --ignore-platform-reqs.
        'options' => "${verbose} install --prefer-dist --no-suggest${progress} --no-interaction",
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
    // TODO: halt-on-fail should determine which execEnvironmentComands
    // instead of terminating the build itself

    $result = $this->execEnvironmentCommands($commands);

    if ($result->getSignal() != 0) {
      if ($this->configuration['halt-on-fail']) {
        $this->terminateBuild('Composer error. Unable to continue.', $result->getError());
      }
    }

    return 0;
  }

}
