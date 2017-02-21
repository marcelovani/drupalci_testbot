<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * @PluginID("update_dependencies")
 */
class UpdateDependencies extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface, Injectable {

  protected $drupalPackageRepository = 'https://packages.drupal.org/8';

  public function inject(Container $container) {
    parent::inject($container);
    $this->codebase = $container['codebase'];

  }

  /* @var \DrupalCI\Build\Codebase\CodebaseInterface */
  protected $codebase;

  /**
   * @inheritDoc
   */
  public function run() {
    // TODO: throw an exception/warning if info.yml changed and not
    // composer.json and vice versa.

    // TODO: composer validate --strict

    // Get the modified files from the codebase.
    // Anything that has a change to the composer.json or .info/.info.yml should
    // be detected.
    // If composer.json is modified, remove the project, validate the composer
    // json, move it to ancillary source directory, make a branch, commit it,
    // add it to the core composer.json as an additional repo, and composer
    // require the new version again.

    $modified_files = $this->codebase->getModifiedFiles();
    $source_dir = $this->codebase->getSourceDirectory();
    $project_name = $this->codebase->getProjectName();
    $ancillary_dir = $this->build->getAncillaryWorkDirectory() . '/' . $project_name;
    $contrib_dir = $this->codebase->getTrueExtensionSubDirectory('modules');

    if (in_array($contrib_dir . '/composer.json', $modified_files)) {
      $this->io->writeln("composer.json changed by patch: recalculating depenendices");

      // 1. Get the currently checked out composer branch name <CBRANCH>
      $cmd = "composer show --working-dir " . $source_dir . " |grep drupal/$project_name |awk '{print $2}'";
      $this->io->writeln("Determining composer branch: $cmd");
      $cmdoutput = $this->execRequiredCommand($cmd, 'Unable to determine composer branch');


      $composer_branchname = $cmdoutput[0];
      // 2. move directory to ancillary
      $project_dir = $source_dir . '/' . $contrib_dir;
      $cmd = "mv $project_dir $ancillary_dir";
      $this->execRequiredCommand($cmd, 'Ancillary mv Failure');

      // 3. make a fake branch in ancillary <TBRANCH>
      $cmd = "cd " . $ancillary_dir . " && git checkout -b ancillary-branch";
      $this->io->writeln("Creating ancillary branch: $cmd");
      $this->execRequiredCommand($cmd, 'Ancillary branch creation failure');

      // 4. commit to ancillary
      $cmd = "cd " . $ancillary_dir . " && git add . && git config --global user.email \"drupalci@drupalci.org\" &&
git config --global user.name \"The Testbot\" && git commit -am 'intermediate commit'";
      $this->io->writeln("Git Command: $cmd");
      $this->execRequiredCommand($cmd, 'Ancillary commit failure');

      // 5. unset pdo
      $cmd = "./bin/composer config repositories.pdo --unset --working-dir " . $source_dir;
      $this->io->writeln("Unset pdo repo: $cmd");
      $this->execRequiredCommand($cmd, 'Ancillary repo config failure');

      // 6. add ancillary as a composer repo
      $cmd = "./bin/composer config repositories.ancillary '{\"type\": \"path\", \"url\": \"" . $ancillary_dir . "\", \"options\": {\"symlink\": false}}' --working-dir " . $source_dir;

      $this->io->writeln("Git Command: $cmd");
      $this->execRequiredCommand($cmd, 'Ancillary repo config failure');

      // 7. reset pdo
      $cmd = "./bin/composer config repositories.pdo composer $this->drupalPackageRepository --working-dir " . $source_dir;

      $this->io->writeln("Git Command: $cmd");
      $this->execRequiredCommand($cmd, 'Ancillary repo config failure');

      // 8. composer require drupal/project "<TBRANCH> AS <CBRANCH>"
      $cmd = "./bin/composer require drupal/" . $project_name . " 'dev-ancillary-branch as $composer_branchname' --ignore-platform-reqs --working-dir " . $source_dir;

      $this->io->writeln("Git Command: $cmd");
      $this->execRequiredCommand($cmd, 'Ancillary require failure');

      // 9. Look for changes to require-dev too:
      $packages = $this->codebase->getComposerDevRequirements();
      if (!empty($packages)) {

        $cmd = "./bin/composer require " . implode(" ", $packages) . " --ignore-platform-reqs --prefer-stable --no-progress --no-suggest --working-dir " . $source_dir;
        $this->io->writeln("Composer Command: $cmd");
        $this->execRequiredCommand($cmd, 'Composer require failure');

      }
    }
  }

}
