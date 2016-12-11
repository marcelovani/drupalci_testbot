<?php

namespace DrupalCI\Plugin\BuildTask\BuildStage;

use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTask\BuildStage\BuildStageInterface;
use DrupalCI\Plugin\BuildTaskBase;
use Pimple\Container;

/**
 * @PluginID("codebase")
 */

class CodebaseBuildStage extends BuildTaskBase  implements BuildStageInterface, BuildTaskInterface  {

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
    $this->codebase->buildDirs();

  }
}
