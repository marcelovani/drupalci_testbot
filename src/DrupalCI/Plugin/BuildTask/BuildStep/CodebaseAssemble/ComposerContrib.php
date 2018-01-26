<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * @PluginID("composer_contrib")
 */
class ComposerContrib extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface {

  /* @var \DrupalCI\Build\Codebase\CodebaseInterface */
  protected $codebase;

  protected $drupalPackageRepository = 'https://packages.drupal.org/8';

  public function inject(Container $container) {
    parent::inject($container);
    $this->codebase = $container['codebase'];
  }

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
    // TODO when https://www.drupal.org/node/2853889 lands, stop using the
    // ExtnetionProjectSubdir to determine the correct branch/project under test.
    if (TRUE === (getenv('DCI_Debug'))) {
      $verbose = '-vvv';
    } else {
      $verbose = '';
    }
    foreach ($this->configuration['repositories'] as $checkout_repo) {
      $checkout_directory = $checkout_repo['checkout_dir'];
      if ($checkout_directory == $this->codebase->getExtensionProjectSubdir()) {
        $branch = $checkout_repo['branch'];
        $composer_branch = $this->getSemverBranch($branch);

        $source_dir = $this->codebase->getSourceDirectory();
        $cmd = "./bin/composer ${verbose} config repositories.pdo composer " . $this->drupalPackageRepository . " --working-dir " . $source_dir;
        $this->io->writeln("Adding packages.drupal.org as composer repository");
        $this->execRequiredCommand($cmd, 'Composer config failure');


        $cmd = "./bin/composer ${verbose} require drupal/" . $this->codebase->getProjectName() . " " . $composer_branch . " --ignore-platform-reqs --prefer-source --prefer-stable --no-progress --no-suggest --no-interaction --working-dir " . $source_dir;

        $this->io->writeln("Composer Command: $cmd");
        $this->execRequiredCommand($cmd, 'Composer require failure');

        // Composer does not respect require-dev anywhere but the root package
        // Lets probe for require-dev in our newly installed module, and add
        // Those dependencies in as well.
        $packages = $this->codebase->getComposerDevRequirements();
        if (!empty($packages)) {
          $cmd = "./bin/composer ${verbose} require --no-interaction --ignore-platform-reqs " . implode(' ',$packages) . " --ignore-platform-reqs --prefer-stable --no-progress --no-suggest --working-dir " . $source_dir;
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
