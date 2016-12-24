<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;

/**
 * Special case for adding phpcs to core's requirements.
 *
 * @PluginID("composer_phpcs")
 *
 * @todo Remove this after https://www.drupal.org/node/2744463
 */
class ComposerPhpcs extends Composer implements BuildStepInterface, BuildTaskInterface {

  /**
   * @inheritDoc
   */
  public function run() {
    // Parent runs composer install.
    parent::run();

    // This adds phpcs.
    $cmd = './bin/composer require drupal/coder 8.2.8 --working-dir ' . $this->codebase->getSourceDirectory();
    $this->io->writeln("Adding phpcs.");
    $this->exec($cmd, $output, $return_var);
  }

}
