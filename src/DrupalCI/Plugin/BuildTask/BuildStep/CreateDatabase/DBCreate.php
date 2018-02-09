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
      if (!$this->database->createDB()) {
        // Mysql seems hokey. lets try one more time an fail hard.
        // Im using NetworkException because this will cause jenkins to retry
        // @TODO: we need a standard way to communicate to jenkins to try again
        sleep(5);
        if (!$this->database->createDB()) {
          $this->io->writeln('Maybe MySql is suffering from a NetworkException?');
          $this->terminateBuild('MySql server has gone away', 'Mysql cannot create the database');
        }
      }
    }
  }

}
