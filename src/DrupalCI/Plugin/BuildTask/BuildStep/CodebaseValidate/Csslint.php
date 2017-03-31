<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseValidate;

use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * A plugin to run csslint
 *
 * @PluginID("csslint")
 *
 * The rules:
 * - Lint changed css files only, unless: 1) env variables tell us not to, 2)
 *   .csslintrc has been modified.
 * - If the project does not specify a .csslintrc ruleset, then the .csslintrc
 *   that ships with drupal core will be used.
 */
class Csslint extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface {

  /**
   * The name of the checkstyle report file.
   *
   * @var string
   */
  protected $checkstyleReportFile = 'checkstyle.xml';

  /* @var \DrupalCI\Build\Environment\EnvironmentInterface */
  protected $environment;

  /* @var \DrupalCI\Build\Codebase\CodebaseInterface */
  protected $codebase;

  /**
   * Relative path to the csslint config file.
   *
   * @var string
   */
  protected $configFile = '';

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      // If lint_fails_test is TRUE, then abort the build.
      'lint_fails_test' => FALSE,
      'skip_linting' => FALSE,
      // Config file is not always in start directory.
      'start_directory' => 'core',
    ];
  }

  /**
   * @inheritDoc
   */
  public function configure() {

    if (FALSE !== getenv('DCI_CSS_LintFailsTest')) {
      $this->configuration['lint_fails_test'] = getenv('DCI_CSS_LintFailsTest');
    }
    if (FALSE !== getenv('DCI_CSS_SkipLinting')) {
      $this->configuration['skip_linting'] = getenv('DCI_CSS_SkipLinting');
    }
    if (FALSE !== getenv('DCI_CSS_StartDirectory')) {
      $this->configuration['start_directory'] = getenv('DCI_CSS_StartDirectory');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function inject(Container $container) {
    parent::inject($container);
    $this->environment = $container['environment'];
    $this->codebase = $container['codebase'];
  }

  /**
   * Adjust state for contrib versus core, and find the config file.
   *
   * For core, we can have a config file in root, and core/ for start_directory.
   * For all other cases, we only want to lint if there is a config file within
   * the adjusted start_directory.
   *
   * @todo The way of figuring out if the build is core or contrib might change.
   *       Adjust this method when it does.
   */
  protected function discoverStartDirectoryAndConfig() {
    $project_name = $this->codebase->getProjectName();
    // Should we set up for contrib?
    if ($project_name !== 'drupal') {
      $this->configuration['start_directory'] = $this->codebase->getTrueExtensionSubDirectory();
      $config_file = $this->configuration['start_directory'] . '/.csslintrc';
      if (file_exists($this->codebase->getSourceDirectory() . '/' . $config_file)) {
        $this->configFile = $config_file;
      }
      return;
    }
    // Set up for core.
    if (file_exists($this->codebase->getSourceDirectory() . '/.csslintrc')) {
      $this->configFile = '.csslintrc';
    }
  }

  /**
   * Perform the step run.
   */
  public function run() {
    $this->discoverStartDirectoryAndConfig();
    // If there is no config file or we want to skip csslint outright
    if (empty($this->configFile) || $this->configuration['skip_linting']) {
      return 0;
    }
    $this->io->writeln('<info>csslinting the project.</info>');

    $outputfile = $this->pluginWorkDir . '/' . $this->checkstyleReportFile;

    // Lint either changed files only, or the project directory
    $files_to_lint = $this->getLintableFiles();
    $lintfiles = implode(' ',$files_to_lint);
    if (empty($lintfiles)) {
      // Lint all files.
      if (!empty($this->configuration['start_directory'])) {
        $lintfiles = $this->configuration['start_directory'];
      }
      else {
        // If there's no start_directory, use .
        $lintfiles = '.';
      }
    }
    elseif ($lintfiles == 'none') {
      return 0;
    }

    $this->io->writeln('Executing csslint.');

    $command = 'cd ' . $this->codebase->getSourceDirectory() . ' && ' . 'csslint --format=checkstyle-xml --config=' . $this->configFile . ' ' . $lintfiles . ' > ' . $outputfile;
    $this->saveStringArtifact('csslint_command.txt', $command);
    $this->exec($command, $output, $return);

    // csslint doesnt produce valid xml
    $command = 'cd ' . $this->codebase->getSourceDirectory() . " && perl -CSDA -i -pe 's/[^\x9\xA\xD\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]+//g;' " . $outputfile;
    $this->exec($command, $output, $return);

    $this->saveHostArtifact($this->pluginWorkDir . '/' . $this->checkstyleReportFile, $this->checkstyleReportFile);


//    // Save rules used as an artifact.
//    $commands[] = 'cd ' . $this->environment->getExecContainerSourceDir() . $start_dir . ' && ' . $this->environment->getExecContainerSourceDir() . static::$phpcsExecutable . ' -e ' . ' ' . implode(' ', $args) . ' > ' . $this->environment->getContainerWorkDir() . '/' . $this->pluginDir . '/phpcs_sniffs.txt';
//    $this->environment->executeCommands($commands);
//    $this->saveHostArtifact($this->pluginWorkDir . '/phpcs_sniffs.txt', 'phpcs_sniffs.txt');

    // TODO: create a patch.
    //$this->saveHostArtifact($this->pluginWorkDir . '/' . $this->patchFile, $this->patchFile);

    // Allow for failing the test run if CS was bad.
    // TODO: if this is supposed to fail the build, we should put in a
    // $this->terminatebuild.
    if ($this->configuration['lint_fails_test']) {
      return $result->getSignal();
    }
    return 0;
  }

  /**
   * Write out the list of sniffable files.
   *
   * @param $lintable_files
   * @param $file_path
   */
  protected function writeLintableFiles($lintable_files, $file_path) {
    $this->io->writeln("<info>Writing: " . $file_path . "</info>");
    $container_source = $this->environment->getExecContainerSourceDir();
    $lintable_file_list = [];
    foreach ($lintable_files as $file) {
      $lintable_file_list[] = $container_source . "/" . $file;
    }
    file_put_contents($file_path, implode("\n", $lintable_file_list));
    $this->saveHostArtifact($file_path, 'lintable_files.txt');
  }

  /**
   * Check if the .csslintignore file has been modified by git.
   *
   * @returns bool
   *   TRUE if config file if either file is modified, FALSE otherwise.
   */
  protected function configFileIsModified() {
    return (
      in_array(
        $this->configFile, $this->codebase->getModifiedFiles()
      )
    );
  }

  protected function getLintableFiles() {

    // No modified files? Sniff the whole repo.
    if (empty($this->codebase->getModifiedFiles())) {
      $this->io->writeln('<info>No modified files. Sniffing all files.</info>');
      return [$this->codebase->getTrueExtensionSubDirectory()];
    }
    elseif ($this->configFileIsModified()) {
      // Sniff all files if .csslintrc has been modified. The file could be
      // 'modified' in that it was removed
      $this->io->writeln('<info>Csslint config file modified, sniffing entire project.</info>');
      return [$this->codebase->getTrueExtensionSubDirectory()];
    }
    else {
      $modified_css =  preg_grep("{.*\.css$}",$this->codebase->getModifiedFiles());
      if (empty($modified_css)) {
        $this->io->writeln('<info>No modified files are eligible to be sniffed</info>');
        return ['none'];
      }
      else {
        $this->io->writeln('<info>Running csslint on modified css files.</info>');

        // Make a list of of modified files to this file.
        $lintable_files = $this->build->getAncillaryWorkDirectory() . '/' . $this->pluginDir . '/lintable_files.txt';
        $this->writeLintableFiles($modified_css, $lintable_files);
        return ($modified_css);
      }
    }
  }

}
