<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;


use DrupalCI\Build\BuildInterface;
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
      $this->configuration['local_dir'] = getenv(('DCI_UseLocalCodebase'));
    }
    // Comma separated list of directories to exclude from the rsync (like .git)
    if (FALSE !== getenv(('DCI_Exclude'))) {
      $this->configuration['excludes'] = explode(',', getenv(('DCI_Exclude')));
    }
    // If either DCI_LocalBranch or DCI_LocalCommitHash is specified,
    // assume those Refer to the git repository at the root of the directory.
    if (FALSE !== getenv(('DCI_LocalBranch'))) {
      $this->configuration['git_branch'] = getenv(('DCI_LocalBranch'));
    }

    if (FALSE !== getenv(('DCI_LocalCommitHash'))) {
      $this->configuration['git_commit_hash'] = getenv(('DCI_LocalCommitHash'));
    }
  }

  /**
   * @inheritDoc
   */
  public function run() {
    $this->io->writeln("<info>Replicating local codebase.</info>");
    $local_dir = $this->configuration['local_dir'];
    if (!empty($local_dir)) {
      // Validate local directory
      if (!is_dir($local_dir)) {
        $this->io->drupalCIError("Directory error", "The local directory <info>$local_dir</info> does not exist.");
        $this->terminateBuild("Replication Failed", "The source directory $local_dir does not exist.");
      }
      $directory = $this->codebase->getSourceDirectory();
      $this->io->writeln("<comment>Copying files from <options=bold>$local_dir</> to the local checkout directory <options=bold>$directory</> ... </comment>");

      $excludes = '';
      foreach ($this->configuration['exclude'] as $exclude_dir) {
        $excludes .= '--exclude=' . $exclude_dir . ' ';
      }
      $this->exec("rsync -a $excludes  $local_dir/. $directory", $cmdoutput, $result);
      if ($result !== 0) {
        $this->terminateBuild("The rsync returned an error.", "Error encountered while attempting to copy code to the local checkout directory.");
      }

      $this->io->writeln("<comment>Copying files complete</comment>");

      // If the copied directory has a .git tree in it, operate on it.
      if (is_dir($directory . '/.git')) {
        if (!empty($this->configuration['git_branch'])) {
          $cmd =  "cd " . $directory . " && git checkout " . $this->configuration['git_branch'];
          $this->io->writeln("Git Command: $cmd");
          $this->exec($cmd, $cmdoutput, $result);
          if ($result !==0) {
            // Git threw an error.
            $this->terminateBuild("git checkout returned an error.", "git checkout returned an error. Error Code: $result");
          }
        }
        if (!empty($this->configuration['git_commit_hash'])) {
          $cmd =  "cd " . $directory . " && git reset -q --hard " . $this->configuration['git_commit_hash'];
          $this->io->writeln("Git Command: $cmd");
          $this->exec($cmd, $cmdoutput, $result);
          if ($result !==0) {
            // Git threw an error.
            $this->terminateBuild("git reset returned an error.", "git reset returned an error. Error Code: $result");
          }
        }

        $cmd = "cd '$directory' && git log --oneline -n 1 --decorate";
        $this->exec($cmd, $cmdoutput, $result);
        $this->io->writeln("<comment>Git commit info:</comment>");
        $this->io->writeln("<comment>\t" . implode($cmdoutput));
      }

      $this->io->writeln("<comment>Checkout complete.</comment>");
    }
    return;
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
      'local_dir' => '',
      'git_branch' => '',
      'git_commit_hash' => '',
    ];
  }
}
