<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;

/**
 * Install yarn if necessary.
 *
 * @todo: This installer is temporary until https://www.drupal.org/node/2874027
 *
 * @PluginID("yarn_install")
 */
class YarnInstall extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface, Injectable {

  /**
   * @inheritDoc
   */
  public function run() {
    $output = [];
    $result = 0;
    // Figure out if yarn is already installed.
    $this->exec('which yarn', $output, $result);
    if ($result === 0) {
      $this->io->writeln('yarn is already installed.');
      return 0;
    }
    // We're using npm to install yarn, which is not entirely recommended.
    // However, it's much less of a hassle than using apt-get, and this plugin
    // is intended to be a temporary solution until yarn is an environment-
    // level dependency.
    $this->io->writeln('installing yarn.');
    $output = [];
    $this->exec('sudo npm install --global yarn', $output, $result);
    if ($result !== 0) {
      $this->terminateBuild('Unable to install yarn.', $result->getError());
    }
    return $result;
  }

}
