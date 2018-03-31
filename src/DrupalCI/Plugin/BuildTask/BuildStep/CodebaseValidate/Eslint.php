<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseValidate;

use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

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
   * The name of the checkstyle report file.
   *
   * @var string
   */
  protected $checkstyleReportFile = 'checkstyle.xml';

  /* @var \DrupalCI\Build\Codebase\CodebaseInterface */
  protected $codebase;

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      // If halt-on-fail is TRUE, then abort the build.
      'halt-on-fail' => FALSE
    ];
  }

  /**
   * @inheritDoc
   */
  public function configure() {

    if (FALSE !== getenv('DCI_ES_LintFailsTest')) {
      $this->configuration['halt-on-fail'] = getenv('DCI_ES_LintFailsTest');
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
   * Perform the step run.
   */
  public function run() {

    $this->io->writeln('<info>eslinting the project.</info>');

    $args = [
      '--format checkstyle',
      '--output-file  ' . $this->pluginWorkDir . '/' . $this->checkstyleReportFile,
    ];

    // Should we only sniff modified files? --file-list lets us specify.
    $files_to_lint = $this->getLintableFiles();

    if ($files_to_lint == 'all') {
      if ($this->codebase->getProjectType() == 'core') {
        $lintfiles = '/core';
      } else {
        $lintfiles = '.';
      }
    }
    elseif ($files_to_lint == 'none') {
      return 0;
    }
    else {
      $lintfiles = implode(' ',$files_to_lint);
    }

    $this->io->writeln('Executing eslint.');

    $command = 'cd ' . $this->codebase->getProjectSourceDirectory() . ' && ' . 'eslint ' . implode(' ', $args) . ' ' . $lintfiles;
    $result = $this->execCommands($command);
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
    if ($this->configuration['halt-on-fail'] && !empty($result->getSignal())) {
      $this->terminatebuild('Javascript coding standards error', '');
    }
    // TODO: d7 eslint doenst have a config file, so, no config means we should
    // pretend everything was fine.
    //return $result->getSignal();
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
   *  returns the full path of both the config file and ignore file
   *  If a project has either eslintrc.json or eslintignore we use those, and
   * fall back to the ones in the root directory
   */
  protected function getEsLintConfig() {
    $config = '';

    $config_directory = $this->codebase->getProjectConfigDirectory();
    $config_path = $this->codebase->getProjectConfigDirectory(FALSE);
    // Check for config files in the project directory first
    if (!empty($config_directory) && file_exists($config_directory . '/.eslintrc.json')) {
      $config = $config_path . '/.eslintrc.json';
    } elseif (!empty($config_directory) && file_exists($config_directory . '/.eslintrc')) {
      $config = $config_path . '/.eslintrc';
    }

    return $config;
  }

  /**
   * Check if the .eslintrc.json or .eslintignore file has been modified by git.
   *
   *
   * @returns bool
   *   TRUE if config file if either file is modified, FALSE otherwise.
   */
  protected function configFileIsModified() {
    // Get the list of modified files.
    $modified_files = $this->codebase->getModifiedFiles();
    $config = $this->getEsLintConfig();

    return in_array($config, $modified_files);

  }


  protected function getLintableFiles() {

    // No modified files? Sniff the whole repo.
    if (empty($this->codebase->getModifiedFiles())) {
      $this->io->writeln('<info>No modified files. Linting all files.</info>');
      return 'all';
    }
    elseif ($this->configFileIsModified()) {
      // Sniff all files if .eslintrc.json or .eslintignore has been modified. The file could be
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
        $this->writeLintableFiles($modified_js, $sniffable_file);
        return ($modified_js);
      }
    }
  }

}
