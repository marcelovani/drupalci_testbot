<?php
namespace DrupalCI\Plugin\BuildTask\BuildStep\Testing;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use Pimple\Container;
/**
 * @PluginID("nightwatchjs")
 */
class NightwatchJS extends BuildTaskBase implements BuildStepInterface {
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
  protected $runscript = 'yarn --cwd /var/www/html/core test:js';
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
  }
  /**
   * @inheritDoc
   */
  public function run() {

    $this->prepareFilesystem();

    $hostname = $this->environment->getChromeContainerHostname();
    $database_url = $this->system_database->getUrl();
    $base_url = 'http://' . $this->environment->getExecContainer()['name'] . '/subdirectory';

    $cmd = "cp {$this->codebase->getSourceDirectory()}/core/nightwatch.settings.json.default {$this->codebase->getSourceDirectory()}/core/nightwatch.settings.json";
    $this->exec($cmd, $output, $return);

    $runscript = "BASE_URL={$base_url} DB_URL={$database_url} WEBDRIVER_HOSTNAME={$hostname} NIGHTWATCH_OUTPUT={$this->environment->getExecContainerSourceDir()}/nightwatch_output NODE_ENV=testbot {$this->runscript}";
    $result = $this->environment->executeCommands("$runscript");

    // Save some artifacts for the build
    if ($result->getSignal() == 0) {
      $this->saveContainerArtifact($this->environment->getExecContainerSourceDir() . '/nightwatch_output', 'nightwatch_xml');
    }
    return 0;
  }

  /**
   * Prepare the filesystem for a run-tests.sh run.
   *
   */
  protected function prepareFilesystem() {
    $sourcedir = $this->environment->getExecContainerSourceDir();
    $setup_commands = [
      'ln -s ' . $sourcedir . ' ' . $sourcedir . '/subdirectory',
      'chown -fR www-data:www-data ' . $sourcedir . '/sites',
    ];
    $result = $this->environment->executeCommands($setup_commands);
    $return = $result->getSignal();
    if ($return !== 0) {
      // Directory setup failed threw an error.
      $this->terminateBuild("Prepare filesystem failed", "Setting up the filesystem failed:  Error Code: $return");
    }
  }

}
