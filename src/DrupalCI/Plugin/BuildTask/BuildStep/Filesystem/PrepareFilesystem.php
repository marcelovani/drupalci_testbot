<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\Filesystem;

use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * Start up phantomjs and show the PHP version.
 *
 * This plugin only runs for D8 simpletest.
 *
 * @todo: Rename this plugin, refactor to other places.
 *
 * @PluginID("prepare_filesystem")
 */
class PrepareFilesystem extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface, Injectable {

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
      'php -v',
    ];
    $result = $this->environment->executeCommands($setup_commands);
    //phantomjs still fails on
    // 5.3 /5.4 so leave this out for now.
    //    if ($result !== 0)
    // Directory setup failed threw an error.
    //      $this->terminateBuild("Prepare Filesystem failed", "Setting up the filesystem failed:  Error Code: $result");
    //    }
    //return $result->getSignal();
  }

}
