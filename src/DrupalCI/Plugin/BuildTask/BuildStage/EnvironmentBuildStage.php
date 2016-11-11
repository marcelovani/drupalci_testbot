<?php

namespace DrupalCI\Plugin\BuildTask\BuildStage;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTask\BuildStage\BuildStageInterface;
use DrupalCI\Plugin\PluginBase;
use Pimple\Container;

/**
 * @PluginID("environment")
 */

class EnvironmentBuildStage extends PluginBase  implements BuildStageInterface, BuildTaskInterface, Injectable   {

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
    // TODO: Overriding configuration should not be a manual process.
    if (isset($_ENV['DCI_DBType'])) {
      $this->configuration['db_type'] = $_ENV['DCI_DBType'];
    }

    if (isset($_ENV['DCI_DBVersion'])) {
      // DCI_DBVersion can sometimes be in the format of DBType-DBVersion.
      if (strpos($_ENV['DCI_DBVersion'],'-')) {
        $this->configuration['db_type'] = explode('-', $_ENV['DCI_DBVersion'], 2)[0];
        $this->configuration['db_version'] = explode('-', $_ENV['DCI_DBVersion'], 2)[1];
      } else {
        $this->configuration['db_version'] = $_ENV['DCI_DBVersion'];
      }
    }

    if (isset($_ENV['DCI_DBUser'])) {
      $this->configuration['dbuser'] = $_ENV['DCI_DBUser'];
    }
    if (isset($_ENV['DCI_DBPassword'])) {
      $this->configuration['dbpassword'] = $_ENV['DCI_DBPassword'];
    }

  }

  /**
   * @inheritDoc
   */
  public function run() {
    $this->database->setVersion($this->configuration['db_version']);
    $this->database->setDbType($this->configuration['db_type']);
    $db_name = str_replace('-', '_', $this->build->getBuildId());
    $db_name = preg_replace('/[^0-9_A-Za-z]/', '', $db_name);
    $this->database->setDbname($db_name);
    $this->database->setPassword($this->configuration['dbpassword']);
    $this->database->setUsername($this->configuration['dbuser']);


  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'db_type' => 'mysql',
      'db_version' => '5.5',
      'dbuser' => 'drupaltestbot',
      'dbpassword' => 'drupaltestbotpw',
    ];
  }

  /**
   * @inheritDoc
   */
  public function getChildTasks() {
    // TODO: Implement getChildTasks() method.
  }

  /**
   * @inheritDoc
   */
  public function setChildTasks($buildTasks) {
    // TODO: Implement setChildTasks() method.
  }

  /**
   * @inheritDoc
   */
  public function getArtifacts() {
    // TODO: Implement getArtifacts() method.
  }

}
