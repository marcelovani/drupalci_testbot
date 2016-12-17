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
    $ancillary_dir = $this->codebase->getAncillarySourceDirectory() . '/' . $project_name;
    $contrib_dir = $source_dir . '/' . $this->codebase->getTrueExtensionDirectory('modules');

      if (in_array($contrib_dir . '/composer.json', $modified_files)) {
        // 1. Get the currently checked out composer branch name <CBRANCH>
        $cmd = "composer show --working-dir " . $source_dir . " |grep drupal/$project_name |awk '{print $2}'";
        $this->io->writeln("Determining composer branch: $cmd");
        $this->exec($cmd, $cmdoutput, $result);
        if ($result !== 0) {
          // Git threw an error.
          throw new BuildTaskException("Unable to determine composer branch.  Error Code: $result");
        }

        $composer_branchname = $cmdoutput[0];
        // 2. rsync directory to ancillary
        $this->exec("mv $contrib_dir $ancillary_dir", $cmdoutput, $result);
        if ($result !== 0) {
          // Git threw an error.
          throw new BuildTaskException("Rsync Failure.  Error Code: $result");
        }
        // 3. make a fake branch in ancillary <TBRANCH>
        $cmd = "cd " . $ancillary_dir . " && git checkout -b ancillary-branch";
        $this->io->writeln("Creating ancillary branch: $cmd");
        $this->exec($cmd, $cmdoutput, $result);
        if ($result !== 0) {
          // Git threw an error.
          throw new BuildTaskException("Ancillary branch creation failure.  Error Code: $result");
        }
        // 4. commit to ancillary
        $cmd = "cd " . $ancillary_dir . " && git commit -am 'intermediate commit'";
        $this->io->writeln("Git Command: $cmd");
        $this->exec($cmd, $cmdoutput, $result);
        if ($result > 1) {
          // Git threw an error.
          throw new BuildTaskException("Ancillary commit failure.  Error Code: $result");
        }
        // 5. unset pdo
        $cmd = "./bin/composer config repositories.pdo --unset --working-dir " . $source_dir;
        $this->io->writeln("Unset pdo repo: $cmd");
        $this->exec($cmd, $cmdoutput, $result);

        if ($result > 1) {
          // Git threw an error.
          throw new BuildTaskException("Ancillary repository config failure.  Error Code: $result");
        }
        // 6. add ancillary as a composer repo
        $cmd = "./bin/composer config repositories.ancillary '{\"type\": \"path\", \"url\": \"" . $ancillary_dir . "\", \"options\": {\"symlink\": false}}' --working-dir " . $source_dir;

        $this->io->writeln("Git Command: $cmd");
        $this->exec($cmd, $cmdoutput, $result);

        if ($result > 1) {
          // Git threw an error.
          throw new BuildTaskException("Ancillary repository config failure.  Error Code: $result");
        }

        // 7. reset pdo
        $cmd = "./bin/composer config repositories.pdo composer $this->drupalPackageRepository --working-dir " . $source_dir;

        $this->io->writeln("Git Command: $cmd");
        $this->exec($cmd, $cmdoutput, $result);

        if ($result > 1) {
          // Git threw an error.
          throw new BuildTaskException("Ancillary repository config failure.  Error Code: $result");
        }
        // 8. composer require drupal/project "<TBRANCH> AS <CBRANCH>"
        $cmd = "./bin/composer require drupal/" . $project_name . " 'dev-ancillary-branch as $composer_branchname' --working-dir " . $source_dir;

        $this->io->writeln("Git Command: $cmd");
        $this->exec($cmd, $cmdoutput, $result);

        if ($result > 1) {
          // Git threw an error.
          throw new BuildTaskException("Ancillary require failure.  Error Code: $result");
        }

      }
    }


}
