<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;

/**
 * @PluginID("composer_contrib")
 */
class ComposerContrib extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface {

  protected $drupalPackageRepository = 'https://packages.drupal.org/8';

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'repositories' => [],
    ];

  }

  /**
   * @inheritDoc
   */
  public function configure() {

    // Currently DCI_AdditionalRepositories, in conjunction with DCI_TestItem,
    // are the mechanisms we use to sort out which contrib module to check out.
    //
    if (FALSE !== getenv(('DCI_AdditionalRepositories'))) {
      // Parse the provided repository string into it's components
      $entries = explode(';', getenv(('DCI_AdditionalRepositories')));
      foreach ($entries as $entry) {
        if (empty($entry)) {
          continue;
        }
        $components = explode(',', $entry);
        // Ensure we have at least 3 components
        if (count($components) < 4) {
          $this->terminateBuild("Unable to parse repository info", "Unable to parse repository info for value $entry");
        }
        // Create the build definition entry
        $output = [
          'repo' => $components[1],
          'branch' => $components[2],
          'checkout_dir' => $components[3]
        ];
        $this->configuration['repositories'][] = $output;
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function run() {

    foreach ($this->configuration['repositories'] as $checkout_repo) {
      $checkout_directory = $checkout_repo['checkout_dir'];
      if ($checkout_directory == $this->codebase->getExtensionProjectSubdir()) {
        $branch = $checkout_repo['branch'];
        $composer_branch = $this->getSemverBranch($branch);

        $source_dir = $this->codebase->getSourceDirectory();
        $cmd = "./bin/composer config repositories.pdo composer " . $this->drupalPackageRepository . " --working-dir " . $source_dir;
        $this->io->writeln("Adding packages.drupal.org as composer repository");
        $this->execRequiredCommand($cmd, 'Composer config failure');


        $cmd = "./bin/composer require drupal/" . $this->codebase->getProjectName() . " " . $composer_branch . " --ignore-platform-reqs --prefer-source --prefer-stable --no-progress --no-suggest --working-dir " . $source_dir;

        $this->io->writeln("Composer Command: $cmd");
        $this->execRequiredCommand($cmd, 'Composer require failure');

        // Composer does not respect require-dev anywhere but the root package
        // Lets probe for require-dev in our newly installed module, and add
        // Those dependencies in as well.
        $packages = $this->codebase->getComposerDevRequirements();
        if (!empty($packages)) {
          $cmd = "./bin/composer require --ignore-platform-reqs " . implode(' ',$packages) . " --ignore-platform-reqs --prefer-stable --no-progress --no-suggest --working-dir " . $source_dir;
          $this->io->writeln("Composer Command: $cmd");
          $this->execRequiredCommand($cmd, 'Composer require failure');

        }
      }
    }
  }

  /**
 * Converts a drupal branch string that is stored in git into a composer
 * based branch string. For d8 contrib
 *
 * @param $branch
 *
 * @return mixed
 */
  protected function getSemverBranch($branch) {
    $converted_version = 'dev-' . preg_replace('/^\d+\.x-/', '', $branch);
    return $converted_version;
  }

}
