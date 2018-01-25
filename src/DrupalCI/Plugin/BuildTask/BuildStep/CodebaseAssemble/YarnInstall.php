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
    $output = [];
    $result = 0;
    $this->io->writeln('Executing yarn install for core nodejs dev dependencies.');

    $work_dir = $this->codebase->getSourceDirectory() . '/core';
    $this->exec("yarn install --no-progress --non-interactive --cwd ${work_dir} 2>&1", $output, $result);

    $this->saveStringArtifact('yarn_install.txt', implode("\n", $output));

    if ($result !== 0) {
      $message = "Yarn install command returned code: $result";
      if ($this->configuration['die-on-fail']) {

        $this->terminateBuild($message, implode("\n", $output));
      }
      else {
        $this->io->writeln($message . "\nYarn install failed; Proceeding anyways...");
        return 0;
        // Skip the list and licenses below.
      }
    } else {
      $this->io->writeln('Yarn install success');
    }
    $output = [];
    $this->exec('yarn list --no-progress --non-interactive --cwd ' . $work_dir . ' 2>&1', $output, $result);
    $this->saveStringArtifact('yarn_list.txt', implode("\n", $output));
    $output = [];
    $this->exec('yarn --no-progress --non-interactive --cwd ' . $work_dir . ' licenses list 2>&1', $output, $result);
    $this->saveStringArtifact('yarn_licenses.txt', implode("\n", $output));
    return $result;
  }

}
