<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CreateDatabase;

use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * @PluginID("dbcreate")
 */
class DBCreate extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface, Injectable {

  /**
   * @var \DrupalCI\Build\Environment\DatabaseInterface
   */
  protected $database;

  public function inject(Container $container) {
    parent::inject($container);
    $this->database = $container['db.system'];
  }

  /**
   * @inheritDoc
   */
  public function configure() {

  }

  /**
   * @inheritDoc
   */
  public function run() {

    if ($this->database->getDbType() !== 'sqlite') {
      $this->database->createDB();
    }
  }

}
