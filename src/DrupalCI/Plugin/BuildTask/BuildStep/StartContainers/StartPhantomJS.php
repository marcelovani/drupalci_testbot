<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\StartContainers;

use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * Start up phantomjs and show the PHP version.
 *
 * @todo: Rename this plugin, refactor to other places.
 *
 * @PluginID("start_phantomjs")
 */
class StartPhantomJS extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface, Injectable {

  /**
   * @inheritDoc
   */
  public function run() {
    $setup_commands = [
      'supervisorctl start phantomjs',
    ];
    $result = $this->execRequiredEnvironmentCommands($setup_commands,"Unable to start phantomjs");

    return 0;
  }

}
