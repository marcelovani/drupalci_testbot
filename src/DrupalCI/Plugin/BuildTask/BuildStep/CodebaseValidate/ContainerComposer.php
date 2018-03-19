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
        'executable-path' => '/usr/local/bin/composer',
        'fail-should-terminate' => TRUE,
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
      $this->configuration['executable-path'],
      'config -g discard-changes true',
    ];
    $commands[] = implode(' ', $command);
    $result = $this->environment->executeCommands($commands);

    // Build a containerized Composer command.
    $command = [ 'COMPOSER_ALLOW_SUPERUSER=TRUE',
      $this->configuration['executable-path'],
      $this->configuration['options'],
      '--working-dir ' . $this->environment->getExecContainerSourceDir(),
    ];
    $commands[] = implode(' ', $command);
    $result = $this->environment->executeCommands($commands);

    if ($result->getSignal() != 0) {
      if ($this->configuration['fail-should-terminate']) {
        $this->terminateBuild('Composer error. Unable to continue.', $result->getError());
      }
    }

    return 0;
  }

}
