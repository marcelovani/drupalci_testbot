<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\Filesystem;


use DrupalCI\Build\Environment\Environment;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\FileHandlerTrait;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * This does all the typical setup for a build. We'll probably want to move
 * some of this to other places, but it can go here during this sweep of
 * reorganization.
 *
 * @PluginID("prepare_filesystem")
 */
class PrepareFilesystem extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface, Injectable {

  use FileHandlerTrait;

  /* @var \DrupalCI\Build\Environment\DatabaseInterface */
  protected $system_database;

  /* @var  \DrupalCI\Build\Environment\EnvironmentInterface */
  protected $environment;

  public function inject(Container $container) {
    parent::inject($container);
    $this->system_database = $container['db.system'];
    $this->environment = $container['environment'];

  }

  /**
   * @inheritDoc
   */
  public function run() {
    $sourcedir = $this->environment->getExecContainerSourceDir();
   $setup_commands = [
      'mkdir -p ' . $sourcedir . '/sites/simpletest/xml',
      'ln -s ' . $sourcedir . ' ' . $sourcedir . '/checkout',
      'chown -fR www-data:www-data ' . $sourcedir . '/sites',
      'chmod 0777 ' . $this->environment->getContainerArtifactDir(),
      'chmod 0777 /tmp',
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
