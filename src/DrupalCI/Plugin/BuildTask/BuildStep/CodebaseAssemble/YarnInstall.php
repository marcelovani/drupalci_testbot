<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Plugin\BuildTaskBase;
use Pimple\Container;

/**
 * Executes 'yarn install' against the package.json within the project.
 *
 * This plugin is meant to install the core yarn/npm dependencies during the
 * codebase_assemble phase.
 *
 * @PluginID("yarn_install")
 */
class YarnInstall extends BuildTaskBase {

  /* @var \DrupalCI\Build\Codebase\CodebaseInterface */
  protected $codebase;

  public function inject(Container $container) {
    parent::inject($container);
    $this->codebase = $container['codebase'];

  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'die-on-fail' => FALSE,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function run() {
    if ('TRUE' === strtoupper(getenv('DCI_Debug'))) {
      $verbose = ' --verbose';
      $progress = '';
    } else {
      $verbose = '';
      $progress = ' --no-progress';
    }
    $output = [];
    $result = 0;
    $this->io->writeln('Executing yarn install for core nodejs dev dependencies.');

    $work_dir = $this->codebase->getSourceDirectory() . '/core';
    // Should this be execRequiredCommand?
    $result = $this->execCommands("yarn${verbose} install${progress} --non-interactive --cwd ${work_dir}", TRUE);
    $this->saveStringArtifact('yarn_install.txt', $result->getOutput());

    if ($result->getSignal() !== 0) {
      $message = "Yarn install command returned code: {$result->getOutput()}";
      if ($this->configuration['die-on-fail']) {

        $this->terminateBuild($message, $output);
      }
      else {
        $this->io->writeln($message . "\nYarn install failed; Proceeding anyways...");
        return 0;
        // Skip the list and licenses below.
      }
    } else {
      $this->io->writeln('Yarn install success');
    }

    $result = $this->execCommands("yarn${verbose} list$progress --non-interactive --cwd ${work_dir}", TRUE);
    $this->saveStringArtifact('yarn_list.txt', $result->getOutput());

    $result = $this->execCommands("yarn${verbose}$progress --non-interactive --cwd ${work_dir} licenses list", TRUE);
    $this->saveStringArtifact('yarn_licenses.txt', $result->getOutput());
    return $result->getSignal();
  }

}
