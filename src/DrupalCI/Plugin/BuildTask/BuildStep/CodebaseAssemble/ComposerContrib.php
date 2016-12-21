<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;


use DrupalCI\Build\BuildInterface;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskException;
use DrupalCI\Plugin\BuildTask\FileHandlerTrait;
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
    if (isset($_ENV['DCI_AdditionalRepositories'])) {
      // Parse the provided repository string into it's components
      $entries = explode(';', $_ENV['DCI_AdditionalRepositories']);
      foreach ($entries as $entry) {
        if (empty($entry)) {
          continue;
        }
        $components = explode(',', $entry);
        // Ensure we have at least 3 components
        if (count($components) < 4) {
          $this->io->writeln("<error>Unable to parse repository information for value <options=bold>$entry</options=bold>.</error>");
          throw new BuildTaskException("Unable to parse repository information for value $entry");
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
        $this->exec($cmd, $cmdoutput, $result);

        if ($result > 1) {
          // Git threw an error.
          throw new BuildTaskException("Composer config failure.  Error Code: $result");
        }

        $cmd = "./bin/composer require drupal/" . $this->codebase->getProjectName() . " " . $composer_branch . " --prefer-source --working-dir " . $source_dir;

        $this->io->writeln("Composer Command: $cmd");
        $this->exec($cmd, $cmdoutput, $result);

        if ($result > 1) {
          // Git threw an error.
          throw new BuildTaskException("Composer require failure.  Error Code: $result");
        }
        // Composer does not respect require-dev anywhere but the root package
        // Lets probe for require-dev in our newly installed module, and add
        // Those dependencies in as well.
        $install_json = $this->codebase->getSourceDirectory() . '/vendor/composer/installed.json';
        if (file_exists($install_json)) {
          $installed_json = json_decode(file_get_contents($install_json), TRUE);
          foreach ($installed_json as $package) {
            if ($package['name'] == "drupal/" . $this->codebase->getProjectName()) {
              if (!empty($package['require-dev'])) {
                foreach ($package['require-dev'] as $dev_package => $constraint) {
                  $cmd = "./bin/composer require " . $dev_package . " " . escapeshellarg($constraint) . " --prefer-source --working-dir " . $source_dir;

                  $this->io->writeln("Composer Command: $cmd");
                  $this->exec($cmd, $cmdoutput, $result);

                  if ($result > 1) {
                    // Git threw an error.
                    throw new BuildTaskException("Composer require failure.  Error Code: $result");
                  }
                }
              }
            }
          }
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
    $converted_version = preg_replace('/^\d+\.x-/', '', $branch) . '-dev';
    return $converted_version;
  }
}
