<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseValidate;


use DrupalCI\Build\BuildInterface;
use DrupalCI\Build\Environment\EnvironmentInterface;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskException;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * @PluginID("phplint")
 */
class PhpLint extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface {

  /* @var  \DrupalCI\Build\Environment\EnvironmentInterface */
  protected $environment;

  /* @var \DrupalCI\Build\Codebase\CodebaseInterface */
  protected $codebase;


  public function inject(Container $container) {
    parent::inject($container);
    $this->environment = $container['environment'];
    $this->codebase = $container['codebase'];
  }

  /**
   * @inheritDoc
   */
  public function configure() {
    if (isset($_ENV['DCI_Concurrency'])) {
      $this->configuration['concurrency']= $_ENV['DCI_Concurrency'];
    }
  }

  /**
   * @inheritDoc
   */
  public function run() {

    $this->io->writeln('<info>SyntaxCheck checking for php syntax errors.</info>');

    $modified_files = $this->codebase->getModifiedFiles();

    if (empty($modified_files)) {
      return 0;
    }

    $workingdir = $this->build->getSourceDirectory();
    $concurrency = $this->configuration['concurrency'];
    $bash_array = "";
    foreach ($modified_files as $file) {
      $file_path = $workingdir . "/" . $file;
      // Checking for: if not in a vendor dir, if the file still exists, and if the first 32 (length - 1) bytes of the file contain <?php
      if ((strpos($file, '/vendor/') === FALSE) && file_exists($file_path) && (strpos(fgets(fopen($file_path, 'r'), 33), '<?php') !== FALSE)) {
        $bash_array .= "$file\n";
      }
    }

    $lintable_files = $this->build->getArtifactDirectory() .'/lintable_files.txt';
    $this->io->writeln("<info>" . $lintable_files . "</info>");
    file_put_contents($lintable_files, $bash_array);
    // Make sure
    if (0 < filesize($lintable_files)) {
      $this->build->addArtifact($lintable_files);
      // This should be come Codebase->getLocalDir() or similar
      // Use xargs to concurrently run linting on file.
      $cmd = "cd " . $this->environment->getExecContainerSourceDir() . " && xargs -P $concurrency -a " . $this->environment->getContainerArtifactDir() . "/lintable_files.txt -I {} php -l '{}'";
      // TODO Throw a BuildException if there are syntax errors.
      $result = $this->environment->executeCommands($cmd);
      if ($result !== 0) {
        // Git threw an error.
        $this->io->drupalCIError("PHPLint Failed", "Unable to change branch.  Error Code: $result");
        throw new BuildTaskException("PHPLint Failed: $result");
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'concurrency' => '4',
    ];
  }

}
