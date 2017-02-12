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

  /* @var  \DrupalCI\Build\Environment\EnvironmentInterface */
  protected $environment;

  public function inject(Container $container) {
    parent::inject($container);
    $this->environment = $container['environment'];
  }

  /**
   * @inheritDoc
   */
  public function run() {
    $sourcedir = $this->environment->getExecContainerSourceDir();
    $setup_commands = [
      'supervisorctl start phantomjs',
    ];
    $result = $this->environment->executeCommands($setup_commands);
    $return = $result->getSignal();
    if ($return !== 0) {
      // Directory setup failed threw an error.
      $this->terminateBuild("Unable to start phantomjs", $result->getError());
    }
    return $result->getSignal();
  }

}
