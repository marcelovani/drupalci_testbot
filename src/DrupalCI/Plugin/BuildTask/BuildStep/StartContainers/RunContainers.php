<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\StartContainers;


use Docker\DockerClient;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Build\Environment\DatabaseInterface;
use DrupalCI\Console\Output;
use DrupalCI\Providers\DockerServiceProvider;
use Http\Client\Common\Exception\ClientErrorException;
use Pimple\Container;

/**
 * @PluginID("runcontainers")
 */
class RunContainers extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface, Injectable  {

  /* @var DatabaseInterface */
  protected $database;

  /* @var \DrupalCI\Build\Environment\EnvironmentInterface */
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

    if (isset($_ENV['DCI_PHPVersion'])) {
      $this->configuration['phpversion'] = $_ENV['DCI_PHPVersion'];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function run() {

    $this->io->writeln("<info>Parsing required Web container image names ...</info>");
    $php_version = $this->configuration['phpversion'];
   // $images['web']['image'] = "drupalci/web-$php_version";
    $images['web'] = ["Image" => "drupalci/web-$php_version"];
    $this->io->writeln("<comment>Adding image: <options=bold>drupalci/web-$php_version</options=bold></comment>");
    $this->environment->startExecContainer($images['web']);

    $this->io->writeln("<info>Parsing required database container image names ...</info>");
    $db_version = $this->database->getDbType() . '-' . $this->database->getVersion();
    $images['db'] = ["Image" => "drupalci/$db_version"];
    $this->io->writeln("<comment>Adding image: <options=bold>drupalci/$db_version</options=bold></comment>");
    $this->environment->startServiceContainerDaemons($images['db']);

  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'phpversion' => '5.5',
    ];
  }

}
