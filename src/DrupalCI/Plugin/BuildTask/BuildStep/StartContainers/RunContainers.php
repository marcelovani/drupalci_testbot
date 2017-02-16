<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\StartContainers;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Build\Codebase\CodebaseInterface;
use DrupalCI\Build\Environment\DatabaseInterface;
use DrupalCI\Build\Environment\EnvironmentInterface;
use DrupalCI\Console\DrupalCIStyle;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskBase;
use Pimple\Container;

/**
 * @PluginID("runcontainers")
 */
class RunContainers extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface {

  /* @var $database \DrupalCI\Build\Environment\DatabaseInterface */
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

    if (FALSE !== getenv('DCI_PHPVersion')) {
      $this->configuration['phpversion'] = getenv('DCI_PHPVersion');
    }

  }

  /**
   * {@inheritdoc}
   */
  public function run() {

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
