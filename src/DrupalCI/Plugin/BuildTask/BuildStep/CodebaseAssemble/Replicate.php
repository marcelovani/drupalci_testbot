<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\FileHandlerTrait;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * @PluginID("replicate")
 */
class Replicate extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface, Injectable {

  use FileHandlerTrait;
  /* @var \DrupalCI\Build\Codebase\CodebaseInterface */
  protected $codebase;

  public function inject(Container $container) {
    parent::inject($container);
    // TODO: not using the codebase in here, but we might want to in order to
    // add whatever repositories we checkout to the codebase object
    $this->codebase = $container['codebase'];

  }

  /**
   * @inheritDoc
   */
  public function configure() {

    // The source directory to copy
    if (FALSE !== getenv(('DCI_UseLocalCodebase'))) {
      $this->configuration['local-dir'] = getenv(('DCI_UseLocalCodebase'));
    }
    // If either DCI_LocalBranch or DCI_LocalCommitHash is specified,
    // assume those Refer to the git repository at the root of the directory.
    if (FALSE !== getenv(('DCI_LocalBranch'))) {
      $this->configuration['git-branch'] = getenv(('DCI_LocalBranch'));
    }

    if (FALSE !== getenv(('DCI_LocalCommitHash'))) {
      $this->configuration['git-commit-hash'] = getenv(('DCI_LocalCommitHash'));
    }
  }

  /**
   * @inheritDoc
   */
  public function run() {
    $this->io->writeln("<info>Replicating local codebase.</info>");
    $local_dir = $this->configuration['local-dir'];
    if (!empty($local_dir)) {
      // Validate local directory
      if (!is_dir($local_dir)) {
        $this->io->drupalCIError("Directory error", "The local directory <info>$local_dir</info> does not exist.");
        $this->terminateBuild("Replication Failed", "The source directory $local_dir does not exist.");
      }
      $result = $this->execRequiredCommands("git --git-dir ${local_dir}/.git remote -v |grep fetch|awk '{print $2}'", 'Unable to determine git remote url');
      $remote_url = $result->getOutput();
      $directory = $this->codebase->getSourceDirectory();
      $this->io->writeln("<comment>Cloning local core checkout from <options=bold>$local_dir</> to the local checkout directory <options=bold>$directory</> ... </comment>");

      $cmd = "git clone $remote_url --reference ${local_dir} ${directory}";
      $result = $this->execRequiredCommands($cmd, 'Local git clone failed');

      $this->io->writeln("<comment>Copying files complete</comment>");

      // If the copied directory has a .git tree in it, operate on it.
      if (is_dir($directory . '/.git')) {
        if (!empty($this->configuration['git-branch'])) {
          $cmd = "git remote add -t {$this->configuration['git-branch']} drupal {$remote_url}";
          $this->io->writeln("Git Command: $cmd");
          $this->execRequiredCommands($cmd, 'git remote add failure');

          $cmd = "git fetch drupal";
          $this->io->writeln("Git Command: $cmd");
          $this->execRequiredCommands($cmd, 'git fetch failure');

          $cmd = "cd " . $directory . " && git checkout " . $this->configuration['git-branch'];
          $this->io->writeln("Git Command: $cmd");
          $this->execRequiredCommands($cmd, 'git checkout failure');

        }
        if (!empty($this->configuration['git-commit-hash'])) {
          $cmd = "cd " . $directory . " && git reset -q --hard " . $this->configuration['git-commit-hash'];
          $this->io->writeln("Git Command: $cmd");
          $this->execRequiredCommands($cmd, 'git reset failure');
        }

        $cmd = "cd '$directory' && git log --oneline -n 1 --decorate";
        $result = $this->execCommands($cmd);
        $cmdoutput = $result->getOutput();
        $this->io->writeln("<comment>Git commit info:</comment>");
        $this->io->writeln("<comment>\t${cmdoutput}");
      }

      $this->io->writeln("<comment>Checkout complete.</comment>");
    }
    return 0;
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    /*
     * Configurables include the directory to replicate, and a comma separated
     * array of directories to exclude from the rsync ('/vendor, .git, etc).
     */
    return [
      'exclude' => [],
      'local-dir' => '',
      'git-branch' => '',
      'git-commit-hash' => '',
    ];
  }

}
