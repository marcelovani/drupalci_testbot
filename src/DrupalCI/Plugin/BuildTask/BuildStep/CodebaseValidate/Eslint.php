<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseValidate;

use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;

/**
 * A plugin to run eslint
 *
 * @PluginID("eslint")
 *
 * The rules:
 * - Lint changed javascript files only, unless: 1) env variables tell us not to, 2)
 *   .eslint has been modified.
 * - If the project does not specify a .eslintrc.json ruleset, then the 'Drupal'
 *   standard will be used. (widget_block/office hours examples of two modules
 * with their own .eslintrc.json
 */
class Eslint extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface {

  /**
   * Whether we should use --standard=Drupal.
   *
   * This implies that there was no phpcs.xml(.dist) file.
   *
   * @var bool
   */
  protected $shouldUseDrupalStandard = FALSE;

  /**
   * Should we install drupal/coder @stable, or rely on the project's spec?
   *
   * @var bool
   */
  protected $shouldInstallGenericCoder = FALSE;

  /**
   * The path where we expect phpcs to reside.
   *
   * @var string
   */
  protected static $phpcsExecutable = '/vendor/squizlabs/php_codesniffer/scripts/phpcs';

  /**
   * The name of the checkstyle report file.
   *
   * @var string
   */
  protected $checkstyleReportFile = 'eslint-checkstyle.xml';

  /**
   * The name of the full report file.
   *
   * @var string
   */
  protected $fullReportFile = 'codesniffer_results.txt';

  /**
   * The name of the codesniffer patch file.
   *
   * @var string
   */
  protected $patchFile = 'codesniffer_fixes.patch';

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'sniff_all_files' => FALSE,
      'start_directory' => 'core',
      'installed_paths' => 'vendor/drupal/coder/coder_sniffer/',
      'warning_fails_sniff' => FALSE,
      // If sniff_fails_test is FALSE, then NO circumstance should let phpcs
      // terminate the build or fail the test.
      'sniff_fails_test' => FALSE,
      'skip_linting' => FALSE,
    ];
  }

  /**
   * @inheritDoc
   */
  public function configure() {
    // The start directory is where the eslint file resides. Relative to the
    // source directory.

    if (FALSE !== getenv('DCI_CS_SniffStartDirectory')) {
      $this->configuration['start_directory'] = getenv('DCI_CS_SniffStartDirectory');
    }
    if (FALSE !== getenv('DCI_CS_ConfigInstalledPaths')) {
      $this->configuration['installed_paths'] = getenv('DCI_CS_ConfigInstalledPaths');
    }
    if (FALSE !== getenv('DCI_CS_SniffFailsTest')) {
      $this->configuration['sniff_fails_test'] = getenv('DCI_CS_SniffFailsTest');
    }
    if (FALSE !== getenv('DCI_CS_WarningFailsSniff')) {
      $this->configuration['warning_fails_sniff'] = getenv('DCI_CS_WarningFailsSniff');
    }
    if (FALSE !== getenv('DCI_CS_CoderVersion')) {
      $this->configuration['coder_version'] = getenv('DCI_CS_CoderVersion');
    }
    if (FALSE !== getenv('DCI_CS_SkipCodesniff')) {
      $this->configuration['skip_codesniff'] = getenv('DCI_CS_SkipCodesniff');
    }
  }

  /**
   * Perform the step run.
   */
  public function run() {
    // Deterimne which rules to use
    // Deterimine which files to lint

    // Use a local file if available, otherwise use the standard from
    // the Root.

    // Lint only the changed files, otherwise lint everything

    $this->io->writeln('<info>eslinting sniffing the project.</info>');

    // Allow for skipping eslint outright, in some tests for example.
    if ($this->configuration['skip_linting']) {
      return 0;
    }

    $configs = $this->getEsLintConfig();

    // Set up state as much as possible in a mockable method.
    $this->shouldUseDrupalStandard = FALSE;

    // Does the code have a phpcs.xml.dist file after patching?
    $this->io->writeln('<info>Checking for phpcs.xml(.dist) file.</info>');
    $has_phpcs_config = $this->projectHasEslintrc();

    // If there is no phpcs.xml(.dist) file, we use the Drupal standard.
    if (!$has_phpcs_config) {
      $this->io->writeln('PHPCS config file not found. Using Drupal standard.');
      $this->shouldUseDrupalStandard = TRUE;
    }
    else {
      $this->io->writeln('Using existing PHPCS config file.');
      $this->shouldUseDrupalStandard = FALSE;
    }

    // Get the  start directory.
    $start_dir = $this->getStartDirectory();

    $args = [
      '--format checkstyle',
      '--output-file  ' . $this->environment->getContainerWorkDir() . '/' . $this->pluginDir . '/' . $this->checkstyleReportFile,
    ];

    $args[] = '--config ' . $this->environment->getExecContainerSourceDir() . $start_dir . '/.eslintrc.json';
    $args[] = '--ignore-path ' . $this->environment->getExecContainerSourceDir() . $start_dir . '/.eslintignore';


    // Should we only sniff modified files? --file-list lets us specify.
    $files_to_lint = $this->getLintableFiles();

    if ($files_to_lint == 'all') {
      // We can use start_directory since we're supposed to sniff the codebase.
      $lintfiles = '.';
    }
    elseif ($files_to_lint == 'none') {
      return 0;
    }
    else {
      $lintfiles = implode(' ',$files_to_lint);
    }

    $this->io->writeln('Executing eslint.');

    $result = $this->environment->executeCommands([
      'cd ' . $this->environment->getExecContainerSourceDir() . $start_dir . ' && ' . 'eslint ' . implode(' ', $args) . ' ' . $lintfiles,
    ]);
    $this->saveHostArtifact($this->pluginWorkDir . '/' . $this->checkstyleReportFile, $this->checkstyleReportFile);


    // Save lints as an artifact.
    $commands[] = 'cd ' . $this->environment->getExecContainerSourceDir() . $start_dir . ' && ' . $this->environment->getExecContainerSourceDir() . static::$phpcsExecutable . ' -e ' . ' ' . implode(' ', $args) . ' > ' . $this->environment->getContainerWorkDir() . '/' . $this->pluginDir . '/phpcs_sniffs.txt';
    $this->environment->executeCommands($commands);
    $this->saveHostArtifact($this->pluginWorkDir . '/phpcs_sniffs.txt', 'phpcs_sniffs.txt');

    $this->saveHostArtifact($this->pluginWorkDir . '/' . $this->fullReportFile, $this->fullReportFile);
    $this->saveHostArtifact($this->pluginWorkDir . '/' . $this->patchFile, $this->patchFile);

    // Allow for failing the test run if CS was bad.
    // TODO: if this is supposed to fail the build, we should put in a
    // $this->terminatebuild.
    if ($this->configuration['sniff_fails_test']) {
      return $result->getSignal();
    }
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function complete($status) {
    $this->adjustCheckstylePaths();
  }

  /**
   * Write out the list of sniffable files.
   *
   * @param $sniffable_files
   * @param $file_path
   */
  protected function writeSniffableFiles($sniffable_files, $file_path) {
    $this->io->writeln("<info>Writing: " . $file_path . "</info>");
    $container_source = $this->environment->getExecContainerSourceDir();
    $sniffable_file_list = [];
    foreach ($sniffable_files as $file) {
      $sniffable_file_list[] = $container_source . "/" . $file;
    }
    file_put_contents($file_path, implode("\n", $sniffable_file_list));
    $this->saveHostArtifact($file_path, 'lintable_files.txt');
  }

  /**
   * Get the start directory relative to the source root
   *
   * @return string
   *   Container path to the configured start directory. If no config was
   *   specified, return nothing.
   */
  protected function getStartDirectory() {
    // Get the project root.
    $project = $this->codebase->getProjectName();
    $start_dir = '';
    if ($project !== 'drupal') {
       $start_dir = '/' . $this->codebase->getTrueExtensionSubDirectory('modules');
    }
    return $start_dir;
  }

  protected function getEsLintConfig() {

  }

  /**
   * Determine whether the project has a .eslintrc.json file.
   *
   * Uses start_directory as the place to look.
   *
   * @return bool
   *   TRUE if the config file exists, false otherwise.
   */
  protected function projectHasEslintrc() {
    // Check if phpcs.xml(.dist) exists.
    $config_dir = $this->getStartDirectory();
    $config_file = $this->environment->getExecContainerSourceDir() . $config_dir . '/.eslintrc.json';
    $this->io->writeln('Checking for .eslintrc.json file: ' . $config_file);
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
  protected function configFileIsModified() {
    // Get the list of modified files.
    $modified_files = $this->codebase->getModifiedFiles();
    $start_dir = $this->getStartDirectory();
    preg_replace('/^\//', '', $start_dir);

    return (
      in_array($start_dir . '/.eslintrc.json', $modified_files) ||
      in_array($start_dir . '/.eslintignore', $modified_files)
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
    $checkstyle_report_filename = $this->pluginWorkDir . '/' . $this->checkstyleReportFile;
    $this->io->writeln('Adjusting paths in report file: ' . $checkstyle_report_filename);
    if (file_exists($checkstyle_report_filename)) {
      // The file is probably owned by root and not writable.
      // @todo remove this when container and host uids have parity.
      exec('sudo chmod 666 ' . $checkstyle_report_filename);
      $checkstyle_xml = file_get_contents($checkstyle_report_filename);
      $checkstyle_xml = preg_replace("!<file name=\"" . $this->environment->getExecContainerSourceDir() . "!", "<file name=\"" . $this->codebase->getSourceDirectory(), $checkstyle_xml);
      file_put_contents($checkstyle_report_filename, $checkstyle_xml);
    }
  }

  protected function getLintableFiles() {

    // No modified files? Sniff the whole repo.
    if (empty($this->codebase->getModifiedFiles())) {
      $this->io->writeln('<info>No modified files. Sniffing all files.</info>');
      return 'all';
    }
    elseif ($this->configFileIsModified()) {
      // Sniff all files if .eslintrc.json or .eslintignor has been modified. The file could be
      // 'modified' in that it was removed
      $this->io->writeln('<info>Eslint config file modified, sniffing entire project.</info>');
      return 'all';
    }
    else {
      $modified_js =  preg_grep("{.*\.js$}",$this->codebase->getModifiedFiles());
      if (empty($modified_js)) {
        $this->io->writeln('<info>No modified files are eligible to be sniffed</info>');
        return 'none';
      }
      else {
        $this->io->writeln('<info>Running eslint on modified js files.</info>');

        // Make a list of of modified files to this file.
        $sniffable_file = $this->build->getAncillaryWorkDirectory() . '/' . $this->pluginDir . '/lintable_files.txt';
        $this->writeSniffableFiles($modified_js, $sniffable_file);
        return ($modified_files);
      }
    }
  }

}
