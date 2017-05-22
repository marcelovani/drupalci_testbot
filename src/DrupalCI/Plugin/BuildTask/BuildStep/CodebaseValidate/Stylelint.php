<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseValidate;

use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * A plugin to run csslint
 *
 * @PluginID("stylelint")
 *
 * The rules:
 * - Lint changed css files only, unless: 1) env variables tell us not to, 2)
 *   .csslintrc has been modified.
 * - If the project does not specify a .csslintrc ruleset, then the .csslintrc
 *   that ships with drupal core will be used.
 */
class Stylelint extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface {

  protected $configFile = '.stylelintrc.json';

  /**
   * The name of the checkstyle report file.
   *
   * @var string
   */
  protected $checkstyleReportFile = 'checkstyle.xml';

  /**
   * @var \DrupalCI\Build\Codebase\CodebaseInterface
   */
  protected $codebase;

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      // If lint_fails_test is TRUE, then abort the build.
      'lint_fails_test' => FALSE,
      'skip_linting' => FALSE,
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
    $this->io->writeln('<info>stylelinting the project.</info>');
    $config = $this->getToolConfigFile($this->configFile);
    // If there is no config file or we want to skip csslint outright
    if (empty($config) || $this->configuration['skip_linting']) {
      return 0;
    }

    $outputfile = $this->pluginWorkDir . '/' . $this->checkstyleReportFile;

    $base_dir = $this->codebase->getSourceDirectory() . '/' . $this->codebase->getTrueExtensionSubDirectory(TRUE);
    $this->io->writeln('Executing stylelint for directory: ' . $base_dir);
    $cwd = getcwd();
    chdir($base_dir);
    $this->exec('yarn lint:css', $output, $return);
    $this->io->warning('stylelint: ' . implode("\n", $output));
    // csslint doesnt produce valid xml
//    $command = 'cd ' . $this->codebase->getSourceDirectory() . " && perl -CSDA -i -pe 's/[^\x9\xA\xD\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]+//g;' " . $outputfile;
//    $this->exec($command, $output, $return);

    $this->saveHostArtifact($outputfile, $this->checkstyleReportFile);

    if ($this->configuration['lint_fails_test']) {
      if ($return !== 0) {
        $this->terminateBuild('Stylelint failed.', implode("\n", $output));
      }
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
   * Returns the full path of the directory to run csslint in.
   *
   * If a project has a .csslintrc, we want to run csslint from the
   * project directory, otherwise we run from the root directory to use
   * the default drupal rules, unless there arent any.
   */
  protected function getToolConfigFile($file_name) {
    $config_file = $this->codebase->getTrueExtensionSubDirectory(TRUE) . '/' . $file_name;
    $this->io->warning('this: ' . $config_file);
  //  if (!file_exists($this->codebase->getSourceDirectory() . '/' . $config_file))   {
  //    return '';
  //  }
    return $config_file;
  }

  /**
   * Check if the .csslintignore file has been modified by git.
   *
   * @returns bool
   *   TRUE if config file if either file is modified, FALSE otherwise.
   */
  protected function configFileIsModified() {
    // Get the list of modified files.
    $modified_files = $this->codebase->getModifiedFiles();
    $config = $this->getToolConfigFile($this->configFile);

    return (
      in_array($config, $modified_files)
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
