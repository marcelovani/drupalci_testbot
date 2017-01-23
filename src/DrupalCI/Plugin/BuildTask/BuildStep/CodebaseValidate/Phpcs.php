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
      'start_directory' => 'core/',
      'installed_paths' => 'vendor/drupal/coder/coder_sniffer/',
      'warning_fails_sniff' => FALSE,
      'sniff_fails_test' => FALSE,
      // @todo: Add a test which changes this.
      'report_file_path' => 'phpcs/checkstyle.xml'
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
    if (isset($_ENV['DCI_CS_ReportFilePath'])) {
      $this->configuration['report_file_path'] = $_ENV['DCI_CS_ReportFilePath'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $return = $this->doRun();
    $this->adjustCheckstylePaths();
    return $return;
  }

  /**
   * Perform the step run.
   *
   * The rules for phpcs go like this:
   *
   * If the project under test does not have a phpcs executable then exit with
   * no complaints.
   *
   * If the project under test does not have a phpcs.xml or phpcs.xml.dist file
   * within start_directory, then exit with no complaints.
   *
   * If {start_directory}/phpcs.xml or .dist have been modified, then override
   * the sniff_only_changed config to sniff the whole project.
   */
  protected function doRun() {
    $this->io->writeln('<info>Checking for phpcs tool in codebase.</info>');

    // If there's no phpcs executable in the codebase then there's nothing else
    // to do.
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

    // Check if we're testing contrib, adjust start path accordingly.
    $project = $this->codebase->getProjectName();
    $this->io->writeln("Sniffing this project: $project");
    // @todo: For now, core has no project name, but contrib does. This could
    // easily change, so we'll need to change the behavior here.
    if (!empty($project)) {
      $this->configuration['start_directory'] = $this->codebase->getTrueExtensionDirectory('modules');
    }

    // The rule is that we never perform a sniff unless there is a
    // phpcs.xml(.dist) file present in start_directory.
    $this->io->writeln('<info>Checking for phpcs.xml(.dist) file.</info>');
    if (!$this->projectHasPhpcsConfig()) {
      $this->io->writeln('PHPCS config file not found.');
      return 0;
    }

    // Make a list of of modified files to this file.
    $sniffable_file = $this->build->getArtifactDirectory() . '/sniffable_files.txt';

    // Sniff all files if phpcs.xml(.dist) has been modified.
    if ($this->phpcsConfigFileIsModified()) {
      $this->io->writeln('<info>PHPCS config file modified, sniffing entire project.</info>');
      $this->configuration['sniff_only_changed'] = FALSE;
    }

    // Check if we should only sniff modified files.
    if ($this->configuration['sniff_only_changed']) {
      $this->io->writeln('<info>Running PHP Code Sniffer review on modified files.</info>');
      $modified_php_files = $this->codebase->getModifiedPhpFiles();

      // No modified files? We're done.
      if (empty($modified_php_files)) {
        $this->io->writeln('<info>No modified files to sniff.</info>');
        return 0;
      }
      $container_source = $this->environment->getExecContainerSourceDir();
      foreach ($modified_php_files as $file) {
        $sniffable_file_list[] = $container_source . "/" . $file;
      }

      $this->io->writeln("<info>Writing: " . $sniffable_file . "</info>");
      file_put_contents($sniffable_file, implode("\n", $sniffable_file_list));
      $this->build->addArtifact($sniffable_file);
    }
    else {
      $this->io->writeln('<info>Sniffing all files starting at ' . $this->configuration['start_directory'] . '.</info>');
    }

    // Set up the report file artifact.
    $this->build->setupDirectory($this->build->getArtifactDirectory() . '/' . dirname($this->configuration['report_file_path']));
    $report_file = $this->build->getArtifactDirectory() . '/' . $this->configuration['report_file_path'];
    $this->build->addArtifact($report_file);

    // Figure out sniff start directory. This is the directory where the
    // phpcs.xml file is presumed to reside.
    $start_dir = $this->getStartDirectory();

    // We have to configure phpcs to use drupal/coder. We can't do this during
    // code assemble time because config and path will change under the PHP
    // container. Just running phpcs without adding an installed path will still
    // work in a generic way, but core needs specific sniffs which should be
    // added with this config.
    if (!empty($this->configuration['installed_paths'])) {
      $cmd = [
        $phpcs_bin,
        '--config-set installed_paths ' . $this->environment->getExecContainerSourceDir() . '/' . $this->configuration['installed_paths'],
      ];
      $this->environment->executeCommands(implode(' ', $cmd));
      // Let the user figure out if it worked.
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
      'cd ' . $start_dir . ' &&',
      $phpcs_bin,
      '-ps',
      '--warning-severity=' . $minimum_error,
      '--report-checkstyle=' . $this->environment->getContainerArtifactDir() . '/' . $this->configuration['report_file_path'],
    ];

    // Should we only sniff modified files? --file-list lets us specify.
    if ($this->configuration['sniff_only_changed']) {
      $cmd[] = '--file-list=' . $this->environment->getContainerArtifactDir() . '/sniffable_files.txt';
    }
    else {
      // We can use start_directory since we're supposed to sniff the codebase.
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
    throw new \RuntimeException('phpcs executable does not exist: ' . $phpcs_bin);
  }

  /**
   * Get the start directory within the container.
   *
   * @return string
   *   Container path to the configured start directory. If no config was
   *   specified, return the root path to the container source directory.
   */
  protected function getStartDirectory() {
    // Get the project root.
    $source_dir = $this->environment->getExecContainerSourceDir();
    $start_dir = $source_dir;
    // Add the start directory from configuration.
    if (!empty($this->configuration['start_directory'])) {
      $start_dir = $source_dir . '/' . $this->configuration['start_directory'];
    }
    return $start_dir;
  }

  /**
   * Determine whether the project has a phpcs.xml(.dist) file.
   *
   * Uses start_directory as the place to look.
   *
   * @return bool
   *   TRUE if the config file exists, false otherwise.
   */
  protected function projectHasPhpcsConfig() {
    // Check if phpcs.xml(.dist) exists.
    $config_dir = $this->getStartDirectory();
    $config_file = $config_dir . '/phpcs.xml*';
    $this->io->writeln('Checking for PHPCS config file: ' . $config_file);
    $result = $this->environment->executeCommands('test -e ' . $config_file);
    return ($result->getSignal() == 0);
  }

  /**
   * Check if the phpcs.xml or phpcs.xml.dist file has been modified by git.
   *
   * We should return true for a modification to either, because we don't want
   * drupalci to have an opinion about which config is more important.
   *
   * @returns bool
   *   TRUE if config file if either file is modified, FALSE otherwise.
   */
  protected function phpcsConfigFileIsModified() {
    // Get the list of modified files.
    $modified_files = $this->codebase->getModifiedFiles();
    $start_dir = '';
    if (!empty($this->configuration['start_directory'])) {
      $start_dir = $this->configuration['start_directory'];
    }
    return (
      in_array($start_dir . 'phpcs.xml', $modified_files) ||
      in_array($start_dir . 'phpcs.xml.dist', $modified_files)
    );
  }

  /**
   * Adjust paths in the checkstyle report.
   *
   * The checkstyle report will show file paths inside the container, and we
   * want it to show paths in the host environment. We do a preg_replace() to
   * swap out paths.
   */
  protected function adjustCheckstylePaths() {
    $checkstyle_report_filename = $this->build->getArtifactDirectory() . '/' . $this->configuration['report_file_path'];
    $this->io->writeln('now processing: ' . $checkstyle_report_filename);
    if (file_exists($checkstyle_report_filename)) {
      // The file is probably owned by root and not writable.
      // @todo remove this when container and host uids have parity.
      exec('sudo chmod 666 ' . $checkstyle_report_filename);
      $checkstyle_xml = file_get_contents($checkstyle_report_filename);
      $checkstyle_xml = preg_replace("!<file name=\"". $this->environment->getExecContainerSourceDir() . "!","<file name=\"" . $this->codebase->getSourceDirectory(), $checkstyle_xml);
      file_put_contents($checkstyle_report_filename, $checkstyle_xml);
    }
  }
}
