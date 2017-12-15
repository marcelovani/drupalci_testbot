<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Plugin\BuildTaskForConfigBase;

/**
 * Executes 'yarn install' against the package.json within the project.
 *
 * This plugin is meant to install yarn-based dependencies during the
 * codebase_assemble phase.
 *
 * @PluginID("yarn_install")
 */
class YarnInstall extends BuildTaskForConfigBase {

  /**
   * The name of the config file for 'yarn install'.
   *
   * @var string
   */
  protected $configFile = 'package.json';

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
    $this->io->writeln('Installing yarn-based dependencies.');

    $config_file = $this->getToolConfigFile($this->configFile);

    if (empty($config_file)) {
      $this->io->writeln("No $this->configFile file to install.");
      return 0;
    }

    $old_cwd = getcwd();
    $base_dir = $this->codebase->getSourceDirectory() . '/' . $this->codebase->getTrueExtensionSubDirectory(TRUE);

    chdir($base_dir);
    $this->exec('yarn install --no-progress 2>&1', $output, $result);
    chdir($old_cwd);

    $this->saveStringArtifact('yarn_install.txt', implode("\n", $output));
    if ($result !== 0) {
      $message = "Yarn install command returned code: $result";
      if ($this->configuration['die-on-fail']) {

        $this->terminateBuild($message, implode("\n", $output));
      }
      else {
        $this->io->error([$message, 'Continuing...']);
      }
    }
    return $result;
  }

}
