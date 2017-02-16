<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Build\Codebase\CodebaseInterface;
use DrupalCI\Build\Environment\EnvironmentInterface;
use DrupalCI\Console\DrupalCIStyle;
use DrupalCI\Build\Codebase\PatchFactoryInterface;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\FileHandlerTrait;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * @PluginID("patch")
 */
class Patch extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface {

  use FileHandlerTrait;

  /**
   * @var $patchFactory \DrupalCI\Build\Codebase\PatchFactoryInterface
   */
  protected $patchFactory;

  public static function create(Container $container, array $configuration_overrides = array(), $plugin_id = '', $plugin_definition = array()) {
    return new static(
      $container['patch_factory'],
      $container['build'],
      $container['codebase'],
      $container['environment'],
      $container['console.io'],
      $container,
      $configuration_overrides,
      $plugin_id,
      $plugin_definition
    );
  }

  public function __construct(
    PatchFactoryInterface $patch_factory,
    BuildInterface $build,
    CodebaseInterface $codebase,
    EnvironmentInterface $environment,
    DrupalCIStyle $io,
    Container $container,
    array $configuration_overrides = array(),
    $plugin_id = '', $plugin_definition = array()
  ) {
    $this->patchFactory = $patch_factory;
    parent::__construct($build, $codebase, $environment, $io, $container, $configuration_overrides, $plugin_id, $plugin_definition);
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
      // Adjust 'to' so the patch applies to the correct place.
      if ($details['to'] == $this->codebase->getExtensionProjectSubdir()) {
        // This patch should be applied to wherever composer checks out to.
        $details['to'] = $this->codebase->getSourceDirectory() . '/' . $this->codebase->getTrueExtensionDirectory('modules');
      }
      else {
        $details['to'] = $this->codebase->getSourceDirectory();
      }
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
