<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;


use Composer\Json\JsonFile;
use DrupalCI\Build\BuildInterface;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\FileHandlerTrait;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

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


    foreach ($this->configuration['repositories'] as $checkout_repo) {
      $checkout_directory = $checkout_repo['checkout_dir'];
      if ($checkout_directory == $this->codebase->getExtensionProjectSubdir()) {

        $source_dir = $this->codebase->getSourceDirectory();
        $cmd = "composer init --name \"drupal/drupal\" --type \"drupal-core\" -n --working-dir " . $source_dir;
        $this->io->writeln("Initializing composer repository");
        $this->exec($cmd, $cmdoutput, $result);
        if ($result > 1) {
          // Composer threw an error.
          $this->terminateBuild("Composer init failure.", "Composer init failure.  Error Code: $result");
        }

        $cmd = "composer config minimum-stability dev --working-dir " . $source_dir;
        $this->io->writeln("Setting Minimum Stability");
        $this->exec($cmd, $cmdoutput, $result);
        if ($result > 1) {
          // Composer threw an error.
          $this->terminateBuild("Composer init failure.", "Composer init failure.  Error Code: $result");
        }

        $cmd = "./bin/composer require composer/installers --working-dir " . $source_dir;
        $this->io->writeln("Composer Command: $cmd");
        $this->exec($cmd, $cmdoutput, $result);
        if ($result > 1) {
          // Composer threw an error.
          $this->terminateBuild("Composer require failure.", "Composer require failure. Error Code: $result");
        }

        $composer_json = $source_dir . '/composer.json';
        if (file_exists($composer_json)) {
          $composerFile = new JsonFile($composer_json);
          $composer_config = $composerFile->read();
            foreach ($this->codebase->getExtensionPaths() as $extension_type => $path) {
              $path = $path . '/{$name}';
              $extension_type = rtrim($extension_type,'s');
              $composer_config['extra']['installer-paths'][$path] = ["type:drupal-$extension_type"];
            }
          $composerFile->write($composer_config);
        }
      }
    }
    parent::run();
  }
}
