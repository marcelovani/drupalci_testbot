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
      $this->configuration['db-type'] = getenv('DCI_DBType');
    }

    if (FALSE !== getenv('DCI_DBVersion')) {
      // DCI_DBVersion can sometimes be in the format of DBType-DBVersion.
      if (strpos(getenv('DCI_DBVersion'), '-')) {
        $this->configuration['db-type'] = explode('-', getenv('DCI_DBVersion'), 2)[0];
        $this->configuration['db-version'] = explode('-', getenv('DCI_DBVersion'), 2)[1];
      }
      else {
        $this->configuration['db-version'] = getenv('DCI_DBVersion');
      }
    }

    if (FALSE !== getenv('DCI_DBHost')) {
      $this->configuration['dbhost'] = getenv('DCI_DBHost');
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
    $this->database->setVersion($this->configuration['db-version']);
    $this->database->setDbType($this->configuration['db-type']);
    $db_name = str_replace('-', '_', $this->build->getBuildId());
    $db_name = preg_replace('/[^0-9_A-Za-z]/', '', $db_name);
    $this->database->setDbname($db_name);
    $this->database->setHost($this->configuration['dbhost']);
    $this->database->setPassword($this->configuration['dbpassword']);
    $this->database->setUsername($this->configuration['dbuser']);

  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'db-type' => 'mysql',
      'db-version' => '5.5',
      'dbhost' => '',
      'dbuser' => 'drupaltestbot',
      'dbpassword' => 'drupaltestbotpw',
    ];
  }

}
