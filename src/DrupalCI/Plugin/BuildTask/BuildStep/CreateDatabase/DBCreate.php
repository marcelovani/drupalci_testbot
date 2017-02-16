<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CreateDatabase;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Build\Codebase\CodebaseInterface;
use DrupalCI\Build\Environment\DatabaseInterface;
use DrupalCI\Build\Environment\EnvironmentInterface;
use DrupalCI\Console\DrupalCIStyle;
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
    return new static(
      $container['db.system'],
      $container['build'],
      $container['codebase'],
      $container['environment'],
      $container['console.io'],
      $container,
      $configuration_overrides,
      $plugin_id,
      $plugin_definition
    );
  }

  public function __construct(
    DatabaseInterface $db_system,
    BuildInterface $build,
    CodebaseInterface $codebase,
    EnvironmentInterface $environment,
    DrupalCIStyle $io,
    Container $container,
    array $configuration_overrides = array(),
    $plugin_id = '', $plugin_definition = array()
  ) {
    $this->database = $db_system;
    parent::__construct($build, $codebase, $environment, $io, $container, $configuration_overrides, $plugin_id, $plugin_definition);
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
