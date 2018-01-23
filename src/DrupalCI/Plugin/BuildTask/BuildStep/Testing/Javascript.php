<?php
namespace DrupalCI\Plugin\BuildTask\BuildStep\Testing;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use Pimple\Container;
/**
 * @PluginID("javascript")
 */
class Javascript extends BuildTaskBase implements BuildStepInterface {
  /* @var  \DrupalCI\Build\Environment\DatabaseInterface */
  protected $system_database;
  /**
   * The current container environment
   *
   * @var  \DrupalCI\Build\Environment\EnvironmentInterface
   */
  protected $environment;
  /* @var \DrupalCI\Build\Codebase\CodebaseInterface */
  protected $codebase;
  /**
   * The path to run-tests.sh.
   *
   * @var string
   */
  protected $runscript = 'NODE_ENV=testbot yarn --cwd /var/www/html/core test:js';
  /**
   * Junit XML builder service.
   *
   * @var \DrupalCI\Build\Artifact\Junit\JunitXmlBuilder
   */
  protected $junitXmlBuilder;
  public function inject(Container $container) {
    parent::inject($container);
    $this->system_database = $container['db.system'];
    $this->environment = $container['environment'];
    $this->codebase = $container['codebase'];
    $this->junitXmlBuilder = $container['junit_xml_builder'];
  }
  /**
   * @inheritDoc
   */
  public function run() {

    $hostname = $this->environment->getChromeContainerHostname();
    $database_url = $this->system_database->getUrl();
    $base_url = 'http://' . $this->environment->getExecContainer()['name'] . '/subdirectory';

    $cmd = "cp {$this->codebase->getSourceDirectory()}/core/nightwatch.settings.json.default {$this->codebase->getSourceDirectory()}/core/nightwatch.settings.json";
    $this->execRequiredCommand($cmd, 'Nightwatch settings config setup failure');

    $runscript = "BASE_URL={$base_url} DB_URL={$database_url} WEBDRIVER_HOSTNAME={$hostname} NIGHTWATCH_OUTPUT={$this->codebase->getSourceDirectory()}/nightwatch_output {$this->runscript}";
    $result = $this->environment->executeCommands("$runscript");

    // Save some artifacts for the build
    $this->saveContainerArtifact($this->environment->getExecContainerSourceDir() . '/nightwatch_output', 'nightwatch_xml');
    return 0;
  }

}
