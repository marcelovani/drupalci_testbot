<?php

namespace DrupalCI\Plugin\BuildTask\BuildStage;

use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskBase;
use Pimple\Container;

/**
 * @PluginID("environment")
 */

class EnvironmentBuildStage extends BuildTaskBase implements BuildStageInterface, BuildTaskInterface, Injectable {

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
    if (FALSE !== getenv('DCI_DBType')) {
      $this->configuration['db_type'] = getenv('DCI_DBType');
    }

    if (FALSE !== getenv('DCI_DBVersion')) {
      // DCI_DBVersion can sometimes be in the format of DBType-DBVersion.
      if (strpos(getenv('DCI_DBVersion'), '-')) {
        $this->configuration['db_type'] = explode('-', getenv('DCI_DBVersion'), 2)[0];
        $this->configuration['db_version'] = explode('-', getenv('DCI_DBVersion'), 2)[1];
      } else {
        $this->configuration['db_version'] = getenv('DCI_DBVersion');
      }
    }

    if (FALSE !== getenv('DCI_DBUser')) {
      $this->configuration['dbuser'] = getenv('DCI_DBUser');
    }
    if (FALSE !== getenv('DCI_DBPassword')) {
      $this->configuration['dbpassword'] = getenv('DCI_DBPassword');
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

}
