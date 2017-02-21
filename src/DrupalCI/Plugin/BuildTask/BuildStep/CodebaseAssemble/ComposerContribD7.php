<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use Composer\Json\JsonFile;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;

/**
 * @PluginID("composer_contrib_d7")
 */
class ComposerContribD7 extends ComposerContrib implements BuildStepInterface, BuildTaskInterface {

  /* @var \DrupalCI\Build\Codebase\CodebaseInterface */
  protected $codebase;

  protected $drupalPackageRepository = 'https://packages.drupal.org/7';

  /**
   * @inheritDoc
   */
  public function run() {
    // TODO when https://www.drupal.org/node/2853889 lands, stop using the
    // ExtnetionProjectSubdir to determine the correct branch/project under test.
    foreach ($this->configuration['repositories'] as $checkout_repo) {
      $checkout_directory = $checkout_repo['checkout_dir'];
      if ($checkout_directory == $this->codebase->getExtensionProjectSubdir()) {

        $source_dir = $this->codebase->getSourceDirectory();
        $cmd = "composer init --name \"drupal/drupal\" --type \"drupal-core\" -n --working-dir " . $source_dir;
        $this->io->writeln("Initializing composer repository");
        $this->execRequiredCommand($cmd, 'Composer init failure');

        $cmd = "composer config minimum-stability dev --working-dir " . $source_dir;
        $this->io->writeln("Setting Minimum Stability");
        $this->execRequiredCommand($cmd, 'Composer config failure');

        $cmd = "composer config prefer-stable true --working-dir " . $source_dir;
        $this->io->writeln("Setting Preferred Stability");
        $this->execRequiredCommand($cmd, 'Composer config failure');

        $cmd = "./bin/composer require composer/installers --working-dir " . $source_dir;
        $this->io->writeln("Composer Command: $cmd");
        $this->execRequiredCommand($cmd, 'Composer require failure');

        $composer_json = $source_dir . '/composer.json';
        if (file_exists($composer_json)) {
          $composerFile = new JsonFile($composer_json);
          $composer_config = $composerFile->read();
          foreach ($this->codebase->getExtensionPaths() as $extension_type => $path) {
            $path = $path . '/{$name}';
            $extension_type = rtrim($extension_type, 's');
            $composer_config['extra']['installer-paths'][$path] = ["type:drupal-$extension_type"];
          }
          $composerFile->write($composer_config);
        }
      }
    }
    parent::run();
  }

}
