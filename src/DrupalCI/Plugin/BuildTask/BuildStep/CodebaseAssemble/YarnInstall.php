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
      'halt-on-fail' => FALSE,
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

    $work_dir = $this->environment->getExecContainerSourceDir() . '/core';
    // Should this be execRequiredCommand?
    $result = $this->execEnvironmentCommands("sudo -u www-data yarn${verbose} install${progress} --non-interactive --cwd ${work_dir}");
    $this->saveStringArtifact($result->getOutput(), 'yarn_install.txt');

    if ($result->getSignal() !== 0) {
      $message = "Yarn install command returned code: {$result->getOutput()}";
      if ($this->configuration['halt-on-fail']) {
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

    $result = $this->execEnvironmentCommands("sudo -u www-data yarn${verbose} list{$progress} --non-interactive --cwd ${work_dir} > {$this->environment->getContainerWorkDir()}/{$this->pluginDir}/yarn_list.txt");
    $this->saveHostArtifact("{$this->environment->getContainerWorkDir()}/{$this->pluginDir}/yarn_list.txt", "yarn_list.txt");

    $result = $this->execEnvironmentCommands("sudo -u www-data yarn${verbose}{$progress} --non-interactive --cwd ${work_dir} licenses list > {$this->environment->getContainerWorkDir()}/{$this->pluginDir}/yarn_licenses.txt");
    $this->saveHostArtifact("{$this->environment->getContainerWorkDir()}/{$this->pluginDir}/yarn_licenses.txt", "yarn_licenses.txt");
    return $result->getSignal();
  }

}
