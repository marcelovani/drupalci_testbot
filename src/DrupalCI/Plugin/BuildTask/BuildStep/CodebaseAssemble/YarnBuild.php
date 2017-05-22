<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;
use DrupalCI\Build\Codebase\CodebaseInterface;

/**
 * Build yarn package.json within the project.
 *
 * @PluginID("yarn_build")
 */
class YarnBuild extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface, Injectable {

  /**
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
   * {@inheritDoc}
   */
  public function run() {
    $output = [];
    $result = 0;
    $this->io->writeln('Building with yarn.');
    // @todo Right now this only works with core.
    $core_dir = $this->codebase->getSourceDirectory() . '/core';
    $this->exec("cd $core_dir && yarn install", $output, $result);
    if ($result !== 0) {
      $this->terminateBuild('Unable to build yarn.', $output);
    }
    return $result;
  }

}
