<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * @PluginID("composer")
 */
class Composer extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface {

  /* @var \DrupalCI\Build\Codebase\CodebaseInterface */
  protected $codebase;

  protected $executable_path = '/usr/local/bin/composer';

  public function inject(Container $container) {
    parent::inject($container);
    $this->codebase = $container['codebase'];

  }

  /**
   * @inheritDoc
   */
  public function run() {
    if ('TRUE' === strtoupper(getenv('DCI_Debug'))) {
      $verbose = '-vvv';
    } else {
      $verbose = '';
    }

    $source_dir = $this->codebase->getSourceDirectory();
    // We add in discard-changes because we're sometimes working with an existing
    // drupal core that already has coder stripped of its tests, and thus it
    // appears as though they are changed.
    $cmd = "{$this->executable_path} ${verbose} config -g discard-changes true";
    $this->execRequiredCommands($cmd, 'Composer Config Command Failed');

    $cmd = "{$this->executable_path} ${verbose} " . $this->configuration['options'] . " --working-dir " . $source_dir;
    $this->execRequiredCommands($cmd, 'Composer Command Failed');

  }

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
      'options' => "${verbose}install --ignore-platform-reqs --prefer-dist --no-suggest --no-interaction${progress}",
    ];
  }

}
