<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CreateDatabase;

use DrupalCI\Build\Environment\DatabaseInterface;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * @PluginID("dbcreate")
 */
class DBCreate extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface {

  /**
   * @var \DrupalCI\Build\Environment\DatabaseInterface
   */
  protected $database;

  public static function create(Container $container, array $configuration_overrides = array(), $plugin_id = '', $plugin_definition = array()) {
    $plugin = new static($container['db.system'], $configuration_overrides, $plugin_id, $plugin_definition);
    $plugin->inject($container);
    return $plugin;
  }

  public function __construct(DatabaseInterface $db_system, array $configuration_overrides = array(), $plugin_id = '', $plugin_definition = array()) {
    parent::__construct($configuration_overrides, $plugin_id, $plugin_definition);
    $this->database = $db_system;
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
