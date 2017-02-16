<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseValidate;

use DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble\Composer;

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
  public function getDefaultConfiguration() {
    return array_merge(
      parent::getDefaultConfiguration(),
      [
        // The 'options' configuration should be exactly the same as parent
        // config, but without --ignore-platform-reqs.
        'options' => 'install --prefer-dist --no-suggest --no-progress',
        'executable_path' => '/usr/local/bin/composer',
        'fail_should_terminate' => TRUE,
      ]
     );
  }

  /**
   * @inheritDoc
   */
  public function run() {
    $this->io->writeln('<info>Running Composer within the environment.</info>');

    // Build a containerized Composer command.
    $command = [ 'COMPOSER_ALLOW_SUPERUSER=TRUE',
      $this->configuration['executable_path'],
      $this->configuration['options'],
      '--working-dir ' . $this->environment->getExecContainerSourceDir(),
    ];
    $commands[] = implode(' ', $command);
    $result = $this->environment->executeCommands($commands);

    if ($result->getSignal() != 0) {
      if ($this->configuration['fail_should_terminate']) {
        $this->terminateBuild('Composer error. Unable to continue.', $result->getError());
      }
    }

    return 0;
  }

}
