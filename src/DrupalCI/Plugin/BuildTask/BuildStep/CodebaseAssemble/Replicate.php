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
    if (isset($_ENV['DCI_UseLocalCodebase'])) {
      $this->configuration['local_dir'] = $_ENV['DCI_UseLocalCodebase'];
    }
    // Comma separated list of directories to exclude from the rsync (like .git)
    if (isset($_ENV['DCI_Exclude'])) {
      $this->configuration['excludes'] = explode(',', $_ENV['DCI_Exclude']);
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
        throw new BuildTaskException("The source directory $local_dir does not exist.");
      }
      // Validate target directory.  Must be within workingdir.
      if (!($directory = $this->validateDirectory($this->build->getSourceDirectory()))) {
        // Invalidate checkout directory
        $this->io->drupalCIError("Directory error", "The checkout directory <info>$directory</info> is invalid.");
        throw new BuildTaskException("The checkout directory $directory is invalid.");
      }
      $this->io->writeln("<comment>Copying files from <options=bold>$local_dir</options=bold> to the local checkout directory <options=bold>$directory</options=bold> ... </comment>");

      foreach ($this->configuration['excludes'] as $exclude_dir) {
        $excludes .= '--exclude=' . $exclude_dir . ' ';
      }
      $this->exec("rsync -a $excludes  $local_dir/. $directory", $cmdoutput, $result);
      if ($result !== 0) {
        $this->io->drupalCIError("Copy error", "Error encountered while attempting to copy code to the local checkout directory.");
        throw new BuildTaskException("The rsync returned an error.  Error Code: $result");
      }

      $this->io->writeln("<comment>Copying files complete</comment>");

      // If the locally copied directory has a .git, lets display it.
      if (is_dir($directory . '/.git')) {
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
    ];
  }
}
