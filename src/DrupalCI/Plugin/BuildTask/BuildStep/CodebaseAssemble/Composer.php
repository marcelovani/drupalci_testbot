<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;

/**
 * @PluginID("composer")
 */
class Composer extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface {

  /**
   * @inheritDoc
   */
  public function run() {

    $source_dir = $this->codebase->getSourceDirectory();

    $cmd = "./bin/composer " . $this->configuration['options'] . " --working-dir " . $source_dir;
    $this->execRequiredCommand($cmd, 'Composer Command Failed');

  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'options' => 'install --ignore-platform-reqs --prefer-dist --no-suggest --no-progress',
    ];
  }

}
