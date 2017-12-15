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

  public function inject(Container $container) {
    parent::inject($container);
    $this->codebase = $container['codebase'];

  }

  /**
   * @inheritDoc
   */
  public function run() {

    $source_dir = $this->codebase->getSourceDirectory();
    // We add in discard-changes because we're sometimes working with an existing
    // drupal core that already has coder stripped of its tests, and thus it
    // appears as though they are changed.
    $cmd = "./bin/composer config discard-changes true --working-dir " . $source_dir;
    $this->execRequiredCommand($cmd, 'Composer Config Command Failed');

    $cmd = "./bin/composer " . $this->configuration['options'] . " --working-dir " . $source_dir;
    $this->execRequiredCommand($cmd, 'Composer Command Failed');

    $cmd = "./bin/composer config --unset discard-changes --working-dir " . $source_dir;
    $this->execRequiredCommand($cmd, 'Composer Config Restore Command Failed');

  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'options' => 'install --ignore-platform-reqs --prefer-dist --no-suggest --no-progress --no-interaction',
    ];
  }

}
