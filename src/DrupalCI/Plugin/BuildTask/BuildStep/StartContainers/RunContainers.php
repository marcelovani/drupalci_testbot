<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\StartContainers;

use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskBase;
use Pimple\Container;

/**
 * @PluginID("runcontainers")
 */
class RunContainers extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface, Injectable {

  /* @var DatabaseInterface */
  protected $database;

  /**
   * The environment.
   *
   * @var \DrupalCI\Build\Environment\EnvironmentInterface
   */
  public $environment;

  public function inject(Container $container) {
    parent::inject($container);
    $this->database = $container['db.system'];
    $this->environment = $container['environment'];
  }

  /**
   * @inheritDoc
   */
  public function configure() {

    if (FALSE !== getenv('DCI_PHPVersion')) {
      $this->configuration['phpversion'] = getenv('DCI_PHPVersion');
    }

  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    // Create a network for the containers
    $this->environment->createContainerNetwork();


    $this->io->writeln("<info>Starting chromedriver container ...</info>");
    $images['chrome'] = ["Image" => "drupalci/chromedriver:production"];
    $this->io->writeln("<comment>Adding image: <options=bold>drupalci/chromedriver:dev</></comment>");
    $this->environment->startChromeContainer($images['chrome']);

    $this->io->writeln("<info>Parsing required Web container image names ...</info>");
    $php_version = $this->configuration['phpversion'];
    $images['web'] = ["Image" => "drupalci/$php_version"];
    $this->io->writeln("<comment>Adding image: <options=bold>drupalci/$php_version</></comment>");
    $this->environment->startExecContainer($images['web']);

    $this->io->writeln("<info>Parsing required database container image names ...</info>");
    $db_version = $this->database->getDbType() . '-' . $this->database->getVersion();
    $images['db'] = ["Image" => "drupalci/$db_version"];
    $this->io->writeln("<comment>Adding image: <options=bold>drupalci/$db_version</></comment>");
    $this->environment->startServiceContainerDaemons($images['db']);

  }

  /**
   * {@inheritdoc}
   */
  public function complete($childStatus) {
    // Print the PHP version.
    $commands = ['php -v',];

    // Save phpinfo as an artifact.
    $info_path = $this->environment->getContainerWorkDir() . '/' . $this->pluginDir . '/phpinfo.txt';
    $commands[] = "php -i > $info_path";

    $this->environment->executeCommands($commands);

    $this->saveContainerArtifact($info_path, 'phpinfo.txt');
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'phpversion' => 'php-5.5.38-apache:production',
    ];
  }

}
