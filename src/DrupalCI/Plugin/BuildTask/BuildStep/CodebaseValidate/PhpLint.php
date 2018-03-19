<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseValidate;

use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * @PluginID("phplint")
 */
class PhpLint extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface {

  /* @var \DrupalCI\Build\Codebase\CodebaseInterface */
  protected $codebase;

  public function inject(Container $container) {
    parent::inject($container);
    $this->codebase = $container['codebase'];
  }

  /**
   * @inheritDoc
   */
  public function configure() {
    if (FALSE !== getenv('DCI_Concurrency')) {
      $this->configuration['concurrency'] = getenv('DCI_Concurrency');
    }
  }

  /**
   * @inheritDoc
   */
  public function run() {

    $this->io->writeln('<info>SyntaxCheck checking for php syntax errors.</info>');

    $modified_php_files = $this->codebase->getModifiedPhpFiles();

    if (empty($modified_php_files)) {
      return 0;
    }

    $file_list = [];

    foreach ($modified_php_files as $file) {
      $file_list[] = $this->environment->getExecContainerSourceDir() . "/" . $file;
    }

    $lintable_files = $this->pluginWorkDir . '/lintable_files.txt';
    $this->io->writeln("<info>" . $lintable_files . "</info>");
    file_put_contents($lintable_files, implode("\n", $file_list));


    // Make sure
    if (0 < filesize($lintable_files)) {
      $this->saveHostArtifact($lintable_files,'lintable_files.txt');

      if (empty($this->configuration['concurrency'])) {
        $this->configuration['concurrency'] = $this->environment->getHostProcessorCount();
      }
      $concurrency = $this->configuration['concurrency'];

      // This should be come Codebase->getLocalDir() or similar
      // Use xargs to concurrently run linting on file.
      $cmd = "cd " . $this->environment->getExecContainerSourceDir() . " && xargs -P $concurrency -a " . $this->environment->getContainerWorkDir() . '/' . $this->pluginDir . "/lintable_files.txt -I {} php -l '{}'";
      // TODO Throw a BuildException if there are syntax errors.
      $result = $this->environment->executeCommands($cmd);
      if ($result->getSignal() !== 0) {
        // Git threw an error.
        $this->terminateBuild("PHPLint Failed", $result->getError());
      }
    }
    return 0;
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
    ];
  }

}
