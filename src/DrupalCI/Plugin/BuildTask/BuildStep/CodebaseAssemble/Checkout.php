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
 * @PluginID("checkout")
 */
class Checkout extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface, Injectable {

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

    if (isset($_ENV['DCI_CoreRepository'])) {
      $this->configuration['repositories'][0]['repo'] = $_ENV['DCI_CoreRepository'];
    }
    if (isset($_ENV['DCI_CoreBranch'])) {
      $this->configuration['repositories'][0]['branch'] = $_ENV['DCI_CoreBranch'];
    }
    if (isset($_ENV['DCI_GitCheckoutDepth'])) {
      $this->configuration['repositories'][0]['depth'] = $_ENV['DCI_GitCheckoutDepth'];
    }
    if (isset($_ENV['DCI_GitCommitHash'])) {
      $this->configuration['repositories'][0]['commit_hash'] = $_ENV['DCI_GitCommitHash'];
    }
   // @TODO make a test:  $_ENV['DCI_AdditionalRepositories']='git,git://git.drupal.org/project/panels.git,8.x-3.x,modules/panels,1;git,git://git.drupal.org/project/ctools.git,8.x-3.0-alpha27,modules/ctools,1;git,git://git.drupal.org/project/layout_plugin.git,8.x-1.0-alpha23,modules/layout_plugin,1;git,git://git.drupal.org/project/page_manager.git,8.x-1.0-alpha24,modules/page_manager,1';
    if (isset($_ENV['DCI_AdditionalRepositories'])) {
      // Parse the provided repository string into it's components
      $entries = explode(';', $_ENV['DCI_AdditionalRepositories']);
      foreach ($entries as $entry) {
        if (empty($entry)) { continue; }
        $components = explode(',', $entry);
        // Ensure we have at least 3 components
        if (count($components) < 4) {
          $this->io->writeln("<error>Unable to parse repository information for value <options=bold>$entry</options=bold>.</error>");
          throw new BuildTaskException("Unable to parse repository information for value $entry");
        }
        // Create the build definition entry
        $output = array(
          'protocol' => $components[0],
          'repo' => $components[1],
          'branch' => $components[2],
          'checkout_dir' => $components[3]
        );
        if (!empty($components[4])) {
          $output['depth'] = $components[4];
        }
        $this->configuration['repositories'][] = $output;
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function run() {
    $this->io->writeln("<info>Populating container codebase data volume.</info>");
    foreach ($this->configuration['repositories'] as $repository ) {
      switch ($repository['protocol']) {
        case 'local':
          $this->setupCheckoutLocal($repository);
          break;
        case 'git':
          $this->setupCheckoutGit($repository);
          break;
      }
    }
    return;
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'repositories' => [
        [
          'protocol' => 'git',
          'repo' => 'git://drupalcode.org/project/drupal.git',
          'branch' => '8.0.x',
          'depth' => '1',
          'checkout_dir' => '.',
          'source_dir' => './',
        ]
      ],
    ];

  }

  protected function setupCheckoutLocal($repository) {
    $source_dir = isset($repository['source_dir']) ? $repository['source_dir'] : './';
    $checkout_dir = isset($repository['checkout_dir']) ? $repository['checkout_dir'] : $this->build->getSourceDirectory();
    // TODO: Ensure we don't end up with double slashes
    // Validate source directory
    if (!is_dir($source_dir)) {
      $this->io->drupalCIError("Directory error", "The source directory <info>$source_dir</info> does not exist.");
      throw new BuildTaskException("The source directory $source_dir does not exist.");
    }
    // Validate target directory.  Must be within workingdir.
    if (!($directory = $this->validateDirectory($this->build->getSourceDirectory(), $checkout_dir))) {
      // Invalidate checkout directory
      $this->io->drupalCIError("Directory error", "The checkout directory <info>$directory</info> is invalid.");
      throw new BuildTaskException("The checkout directory $directory is invalid.");
    }
    $this->io->writeln("<comment>Copying files from <options=bold>$source_dir</options=bold> to the local checkout directory <options=bold>$directory</options=bold> ... </comment>");
    // TODO: Make sure target directory is empty
    #  $this->exec("cp -r $source_dir/. $directory", $cmdoutput, $result);
    $exclude_var = isset($repository['DCI_EXCLUDE']) ? '--exclude="' . $repository['DCI_EXCLUDE'] . '"' : "";
    $this->exec("rsync -a $exclude_var  $source_dir/. $directory", $cmdoutput, $result);
    if ($result !== 0) {
      $this->io->drupalCIError("Copy error", "Error encountered while attempting to copy code to the local checkout directory.");
      throw new BuildTaskException("The rsync returned an error.  Error Code: $result");
    }
    $this->io->writeln("<comment>DONE</comment>");
  }

  protected function setupCheckoutGit($repository) {
    $this->io->writeln("<info>Entering setup_checkout_git().</info>");
    // @TODO: these should always have a default. no sense in setting them here.
    $repo = isset($repository['repo']) ? $repository['repo'] : 'git://drupalcode.org/project/drupal.git';

    $git_branch = isset($repository['branch']) ? $repository['branch'] : 'master';
    $checkout_directory = isset($repository['checkout_dir']) ? $repository['checkout_dir'] : $this->build->getSourceDirectory();
    // TODO: Ensure we don't end up with double slashes
    // Validate target directory.  Must be within workingdir.
    if (!($directory = $this->validateDirectory($this->build->getSourceDirectory(), $checkout_directory))) {
      // Invalid checkout directory
      $this->io->drupalCIError("Directory Error", "The checkout directory <info>$directory</info> is invalid.");
      throw new BuildTaskException("The checkout directory $directory is invalid.");
    }
    if (substr($repository['repo'],0,4) == 'file') {
      // If the repository is specified as a local file://tmp/project, then we rsync the
      // project over to avoid re-composering and re-cloning.
      $exclude_var = isset($repository['DCI_EXCLUDE']) ? '--exclude="' . $repository['DCI_EXCLUDE'] . '"' : "";
      $source_dir = substr($repository['repo'],7);
      $cmd = "rsync -a $exclude_var  $source_dir/. $directory";
      $this->io->writeln("<comment>Performing rsync of git checkout of $repo $git_branch branch to $directory.</comment>");
      $this->io->writeln("Rsync Command: $cmd");
      $this->exec($cmd, $cmdoutput, $result);

      if ($result !== 0) {
        // rsync threw an error.
        $this->io->drupalCIError("Checkout Error", "The rsync returned an error.  Error Code: $result");
        throw new BuildTaskException("The rsync returned an error.  Error Code: $result");
      }

      $cmd = "cd $directory; git clean -fx; git checkout -f $git_branch";
      $this->exec($cmd, $cmdoutput, $result);

      if ($result !== 0) {
        // Git threw an error.
        $this->io->drupalCIError("Checkout Error", "Unable to change branch.  Error Code: $result");
        throw new BuildTaskException("Unable to change branch.  Error Code: $result");
      }
    } else {
      $this->io->writeln("<comment>Performing git checkout of $repo $git_branch branch to $directory.</comment>");
      // TODO: Make sure target directory is empty
      $git_depth = '';
      if (isset($repository['depth']) && empty($repository['commit_hash'])) {
        $git_depth = '--depth ' . $repository['depth'];
      }
      $cmd = "git clone -b $git_branch $git_depth $repo '$directory'";
      $this->io->writeln("Git Command: $cmd");
      $this->exec($cmd, $cmdoutput, $result);

      if ($result !== 0) {
        // Git threw an error.
        $this->io->drupalCIError("Checkout Error", "The git checkout returned an error.  Error Code: $result");
      }
    }

    if (!empty($repository['commit_hash'])) {
      $cmd =  "cd " . $directory . " && git reset -q --hard " . $repository['commit_hash'] . " ";
      $this->io->writeln("Git Command: $cmd");
      $this->exec($cmd, $cmdoutput, $result);
      if ($result !==0) {
        // Git threw an error.
        throw new BuildTaskException("git reset returned an error.  Error Code: $result");
      }
    }


    $cmd = "cd '$directory' && git log --oneline -n 1 --decorate";
    $this->exec($cmd, $cmdoutput, $result);
    $this->io->writeln("<comment>Git commit info:</comment>");
    $this->io->writeln("<comment>\t" . implode($cmdoutput));

    $this->io->writeln("<comment>Checkout complete.</comment>");
  }


}
