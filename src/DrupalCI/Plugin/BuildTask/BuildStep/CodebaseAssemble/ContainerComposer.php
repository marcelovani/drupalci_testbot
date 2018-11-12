<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble\HostComposer;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskBase;
use Pimple\Container;

/**
 * Runs Composer inside the container.
 *
 * Subclass the other composer class, so we inherit default config.
 *
 * @PluginID("composer")
 */
class ContainerComposer extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface {

  /* @var \DrupalCI\Build\Codebase\CodebaseInterface */
  protected $codebase;

  protected $executable_path = 'sudo -u www-data /usr/local/bin/composer';
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
  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    if ('TRUE' === strtoupper(getenv('DCI_Debug'))) {
      $verbose = '-vvv ';
      $progress = '';
    } else {
      $verbose = '';
      $progress = ' --no-progress';
    }
    return [
      'working-directory' => '',
      'options' => "${verbose}install --prefer-dist --no-suggest --no-interaction${progress}",
      'halt-on-fail' => TRUE,
    ];
  }

  /**
   * @inheritDoc
   */
  public function run() {
    $this->io->writeln('<info>Running Composer within the environment.</info>');


    // Build a containerized Composer command to ignore/discard changes
    $command = [ $this->executable_path,
      'config -g discard-changes true',
    ];
    $commands[] = implode(' ', $command);
    $result = $this->execEnvironmentCommands($commands);


    // Build a containerized Composer command.
    $working_directory = empty($this->configuration['working-directory']) ?
      $this->environment->getExecContainerSourceDir() :
      $this->environment->getExecContainerSourceDir() . '/' . $this->configuration['working-directory'];
    $command = [
      $this->executable_path,
      $this->configuration['options'],
      '--working-dir ' . $working_directory,
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
