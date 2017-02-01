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
      // Adjust 'to' so the patch applies to the correct place.
      if ($details['to'] == $this->codebase->getExtensionProjectSubdir()) {
        // This patch should be applied to wherever composer checks out to.
        $details['to'] = $this->codebase->getSourceDirectory() . '/' . $this->codebase->getTrueExtensionDirectory('modules');
      }
      else {
        $details['to'] = $this->codebase->getSourceDirectory();
      }
      // Create a new patch object based on the adjusted 'to'.
      $patch = $this->patchFactory->getPatch(
        $details,
        $this->codebase->getAncillarySourceDirectory()
      );
      $this->codebase->addPatch($patch);

      try {
        // Validate our patch's source file and target directory
        if (!$patch->validate()) {
          $this->terminateBuild("Patch Validation Error", "Failed to validate the patch.");
        }

        // Apply the patch
        if ($patch->apply() !== 0) {
          $this->terminateBuild("Patch Failed to Apply", "Unable to apply the patch.");
        }
      }
      catch (BuildTaskException $e) {

        // Hack to save an xml file to the Jenkins artifact directory.
        // TODO: Remove once proper build failure processing is in place

        // Not all BuildTaskExceptions represent a failed command line
        // operation, so we have to handle that case.
        $output = '';

        $results = $patch->getPatchApplyResults();
        if (!empty($results)) {
          $output = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '�', implode("\n", $results));
        }

        // Build the XML.
        $xml_error = '<?xml version="1.0"?>

                      <testsuite errors="1" failures="0" name="Error: Patch failed to apply" tests="1">
                        <testcase classname="Apply Patch" name="' . $patch->getFilename() . '">
                          <error message="Patch Failed to apply" type="PatchFailure">Patch failed to apply</error>
                        </testcase>
                        <system-out><![CDATA[' . $output . ']]></system-out>
                      </testsuite>';
        $output_directory = $this->build->getXmlDirectory();
        file_put_contents($output_directory . "/patchfailure.xml", $xml_error);

        // TODO: return 0 for now until https://www.drupal.org/node/2846398 goes
        // in.
        //throw $e;
        return 0;
      };
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
