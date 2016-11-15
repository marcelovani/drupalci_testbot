<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;


use DrupalCI\Build\BuildInterface;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildTaskException;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\FileHandlerTrait;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Build\Codebase\PatchInterface;
use DrupalCI\Build\Codebase\Patch as PatchFile;
use Pimple\Container;

/**
 * @PluginID("patch")
 */
class Patch extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface, Injectable  {

  use FileHandlerTrait;

  public function inject(Container $container) {
    parent::inject($container);
    $this->codebase = $container['codebase'];

  }

  /**
   * @inheritDoc
   */
  public function configure() {
    // @TODO make into a test
    // $_ENV['DCI_Patch']='https://www.drupal.org/files/issues/2796581-region-136.patch,.;https://www.drupal.org/files/issues/another.patch,.';
    if (isset($_ENV['DCI_Patch'])) {
      $this->configuration['patches'] = $this->process($_ENV['DCI_Patch']);
    }
  }

  /**
   * @inheritDoc
   */
  public function run() {

    $files = $this->configuration['patches'];

    if (empty($files)) {
      $this->io->writeln('No patches to apply.');
    }
    foreach ($files as $key => $details) {
      try {
        if (empty($details['from'])) {
          $this->io->drupalCIError("Patch error", "No valid patch file provided for the patch command.");
          throw new BuildTaskException('No valid patch file provided for the patch command.');

        }
        // Create a new patch object
        $patch = new PatchFile($details, $this->build->getSourceDirectory());
        $patch->inject($this->container);
        $this->codebase->addPatch($patch);
        // Validate our patch's source file and target directory
        if (!$patch->validate()) {
          throw new BuildTaskException('Failed to validate the patch source and/or target directory.');
        }

        // Apply the patch
        if ($patch->apply() !== 0) {
          throw new BuildTaskException('Unable to apply the patch.');
        }
      }
      catch (BuildTaskException $e) {

        // Hack to create a xml file for processing by Jenkins.
        // TODO: Remove once proper build failure processing is in place

        // Save an xmlfile to the jenkins artifact directory.
        // find jenkins artifact dir
        //
        $output_directory = $this->build->getXmlDirectory();

        if (!is_dir($output_directory)) {
          mkdir($output_directory, 0777, TRUE);
        }

        $output = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '�', implode("\n", $patch->getPatchApplyResults()));

        $xml_error = '<?xml version="1.0"?>

                      <testsuite errors="1" failures="0" name="Error: Patch failed to apply" tests="1">
                        <testcase classname="Apply Patch" name="' . $patch->getSource() . '">
                          <error message="Patch Failed to apply" type="PatchFailure">Patch failed to apply</error>
                        </testcase>
                        <system-out><![CDATA[' . $output . ']]></system-out>
                      </testsuite>';
        file_put_contents($output_directory . "/patchfailure.xml", $xml_error);

        throw $e;
      };
      // Update our list of modified files
      $this->codebase->addModifiedFiles($patch->getModifiedFiles());
    }
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
