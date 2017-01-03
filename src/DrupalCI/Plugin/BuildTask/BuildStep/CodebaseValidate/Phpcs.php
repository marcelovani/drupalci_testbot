<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseValidate;

use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * @PluginID("phpcs")
 */
class Phpcs extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface {

  /**
   * The testing environment.
   *
   * @var \DrupalCI\Build\Environment\EnvironmentInterface
   */
  protected $environment;

  /**
   * The codebase.
   *
   * @var \DrupalCI\Build\Codebase\CodebaseInterface
   */
  protected $codebase;

  public function inject(Container $container) {
    parent::inject($container);
    $this->environment = $container['environment'];
    $this->codebase = $container['codebase'];
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'sniff_only_changed' => TRUE,
      'config_directory' => '',
      'start_directory' => '',
      'installed_paths' => '',
      'sniff_fails_test' => FALSE,
      'warning_fails_sniff' => FALSE,
    ];
  }

  /**
   * @inheritDoc
   */
  public function configure() {
    // The start directory is where the phpcs.xml file resides. Relative to the
    // source directory.
    if (isset($_ENV['DCI_CS_SniffOnlyChanged'])) {
      $this->configuration['sniff_only_changed'] = $_ENV['DCI_CS_SniffOnlyChanged'];
    }
    if (isset($_ENV['DCI_CS_ConfigDirectory'])) {
      $this->configuration['config_directory'] = $_ENV['DCI_CS_ConfigDirectory'];
    }
    if (isset($_ENV['DCI_CS_SniffStartDirectory'])) {
      $this->configuration['start_directory'] = $_ENV['DCI_CS_SniffStartDirectory'];
    }
    if (isset($_ENV['DCI_CS_ConfigInstalledPaths'])) {
      $this->configuration['installed_paths'] = $_ENV['DCI_CS_ConfigInstalledPaths'];
    }
    if (isset($_ENV['DCI_CS_SniffFailsTest'])) {
      $this->configuration['sniff_fails_test'] = $_ENV['DCI_CS_SniffFailsTest'];
    }
    if (isset($_ENV['DCI_CS_WarningFailsSniff'])) {
      $this->configuration['warning_fails_sniff'] = $_ENV['DCI_CS_WarningFailsSniff'];
    }
  }

  /**
   * @inheritDoc
   */
  public function run() {
    $this->io->writeln('<info>Checking for phpcs tool in codebase.</info>');

    try {
      $phpcs_bin = $this->getPhpcsExecutable();
    }
    catch (\RuntimeException $e) {
      $this->io->writeln($e->getMessage());
      // @todo: Not all core versions are expected to have phpcs installed, so
      //        we just return 0 for this plugin if it's not. In the future we
      //        might want this to be an error.
      return 0;
    }

    $this->io->writeln('<info>Running PHP Code Sniffer review on modified files.</info>');

    // Make a list of of modified files to this file.
    $sniffable_file = $this->build->getArtifactDirectory() . '/sniffable_files.txt';

    // Check if we should only sniff modified files.
    if ($this->configuration['sniff_only_changed']) {
      $modified_php_files = $this->codebase->getModifiedPhpFiles();

      // No modified files? We're done.
      if (empty($modified_php_files)) {
        $this->io->writeln('<info>No modified files to sniff.</info>');
        return 0;
      }
      foreach ($modified_php_files as $file) {
        $sniffable_file_list[] = $this->environment->getExecContainerSourceDir() . "/" . $file;
      }

      $this->io->writeln("<info>Writing: " . $sniffable_file . "</info>");
      file_put_contents($sniffable_file, implode("\n", $sniffable_file_list));
      $this->build->addArtifact($sniffable_file);
    }
    else {
      $this->io->writeln('<info>Sniffing all files in repo.</info>');
    }

    // Set up the report file artifact.
    $this->build->setupDirectory($this->build->getArtifactDirectory() . '/phpcs');
    $report_file = $this->build->getArtifactDirectory() . '/phpcs/phpcs_checkstyle.xml';
    touch($report_file);
    $this->build->addArtifact($report_file);

    // Figure out sniff config directory.
    $source_dir = $this->environment->getExecContainerSourceDir();
    $phpcs_config_dir = $source_dir;
    // Add the start directory from config.
    if (!empty($this->configuration['config_directory'])) {
      $phpcs_config_dir = $source_dir . '/' . $this->configuration['config_directory'];
    }

    // We have to configure phpcs to use drupal/coder. We can't do this during
    // code assemble time because config and path will change under the PHP
    // container. Just running phpcs without adding an installed path will still
    // work in a generic way, but core needs specific sniffs which should be
    // added with this config.
    if (!empty($this->configuration['installed_paths'])) {
      $cmd = [
        $phpcs_bin,
        '--config-set installed_paths ' . $this->environment->getExecContainerSourceDir() . $this->configuration['installed_paths'],
      ];
      $this->environment->executeCommands(implode(' ', $cmd));
      // Verify that it worked.
      $this->environment->executeCommands("$phpcs_bin -i");
    }

    // Set minimum error level for fail. phpcs uses 1 for warning and 2 for
    // error.
    $minimum_error = 2;
    if ($this->configuration['warning_fails_sniff']) {
      $minimum_error = 1;
    }

    // Execute phpcs. The project's phpcs.xml(.dist) should configure file types
    // and all other constraints.
    $cmd = [
      'cd ' . $phpcs_config_dir . ' &&',
      $phpcs_bin,
      '-ps',
      '--warning-severity=' . $minimum_error,
      '--report-checkstyle=' . $this->environment->getContainerArtifactDir() . '/phpcs/phpcs_checkstyle.xml',
    ];

    // Should we only sniff modified files? --file-list lets us specify.
    if ($this->configuration['sniff_only_changed']) {
      $cmd[] = '--file-list=' . $this->environment->getContainerArtifactDir() . '/sniffable_files.txt';
    }
    else {
      // We can use start_directory since there is not a file-list.
      if (!empty($this->configuration['start_directory'])) {
        $cmd[] = $this->environment->getExecContainerSourceDir() . '/' . $this->configuration['start_directory'];
      }
    }
    $this->io->writeln('Executing PHPCS.');
    $result = $this->environment->executeCommands(implode(' ', $cmd));

    // Allow for failing the test run if CS was bad.
    if ($this->configuration['sniff_fails_test']) {
      return $result->getSignal();
    }
    return 0;
  }

  /**
   * Get the full path to the phpcs executable.
   *
   * @return string
   *   The full path to the phpcs executable.
   *
   * @throws \RuntimeException
   *   Thrown when the phpcs executable can't be found.
   */
  protected function getPhpcsExecutable() {
    $source_dir = $this->environment->getExecContainerSourceDir();
    $phpcs_bin = $source_dir . '/vendor/bin/phpcs';
    $result = $this->environment->executeCommands('test -e ' . $phpcs_bin);
    if ($result->getSignal() == 0) {
      return $phpcs_bin;
    }
    throw new \RuntimeException('phpcs file does not exist: ' . $phpcs_bin);
  }

}
