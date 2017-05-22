<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Plugin\BuildTaskForConfigBase;

/**
 * Build yarn package.json within the project.
 *
 * @PluginID("yarn_build")
 */
class YarnBuild extends BuildTaskForConfigBase {

  /**
   * The name of the config file for 'yarn install'.
   *
   * @var string
   */
  protected $configFile = 'package.json';

  /**
   * {@inheritDoc}
   */
  public function run() {
    $output = [];
    $result = 0;
    $this->io->writeln('Building with yarn.');

    $config_file = $this->getToolConfigFile($this->configFile);

    if (empty($config_file)) {
      $this->io->writeln("No $this->configFile file to build.");
      return 0;
    }

    $old_cwd = getcwd();
    $base_dir = $this->codebase->getSourceDirectory() . '/' . $this->codebase->getTrueExtensionSubDirectory(TRUE);
    chdir($base_dir);
    $this->exec('yarn install 2>&1', $output, $result);
    chdir($old_cwd);
    $this->saveStringArtifact('yarn_install_build.txt', implode("\n", $output));
    if ($result !== 0) {
      $this->terminateBuild('Unable to build yarn.', $output);
    }
    return $result;
  }

}
