<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\FileHandlerTrait;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskException;
use Pimple\Container;

/**
 * @PluginID("patch")
 */
class Patch extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface, Injectable {

  use FileHandlerTrait;

  /**
   * @var \DrupalCI\Build\Codebase\CodebaseInterface
   */
  protected $codebase;

  /**
   * @var \DrupalCI\Build\Codebase\PatchFactoryInterface
   */
  protected $patchFactory;

  public function inject(Container $container) {
    parent::inject($container);
    $this->codebase = $container['codebase'];
    $this->patchFactory = $container['patch_factory'];
  }

  /**
   * @inheritDoc
   */
  public function configure() {
    // @TODO make into a test
    // putenv('DCI_Patch=https://www.drupal.org/files/issues/2796581-region-136.patch,.;https://www.drupal.org/files/issues/another.patch,.');
    if (FALSE !== getenv('DCI_Patch')) {
      $this->configuration['patches'] = $this->process(getenv('DCI_Patch'));
    }
  }

  /**
   * {@inheritDoc}
   */
  public function run() {
    $files = $this->configuration['patches'];

    if (empty($files)) {
      $this->io->writeln('No patches to apply.');
    }
    foreach ($files as $key => $details) {
      // Validate 'from'.
      if (empty($details['from'])) {
        $this->terminateBuild("Invalid Patch", "No valid patch file provided for the patch command.");
      }
      $details['to'] = $this->codebase->getProjectSourceDirectory();

      // Create a new patch object based on the adjusted 'to'.
      /* @var $patch \DrupalCI\Build\Codebase\Patch */
      $patch = $this->patchFactory->getPatch(
        $details,
        $this->build->getAncillaryWorkDirectory()
      );
      $this->codebase->addPatch($patch);

      // Validate our patch's source file and target directory
      if (!$patch->validate()) {
        $this->terminateBuild("Patch Validation Error", "Failed to validate the patch.");
      }

      // Apply the patch
      if ($patch->apply() !== 0) {
        $this->terminateBuild("Patch Failed to Apply", implode("\n", $patch->getPatchApplyResults()));
      }

      // Update our list of modified files
      $this->codebase->addModifiedFiles($patch->getModifiedFiles());
    }
    return 0;
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'patches' => [],
    ];
  }

}
