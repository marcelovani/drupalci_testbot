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
    if (file_exists("{$this->codebase->getSourceDirectory()}/core/nightwatch.settings.json.default")) {

      $this->prepareFilesystem();

      $hostname = $this->environment->getChromeContainerHostname();
      $database_url = $this->system_database->getUrl();
      $base_url = 'http://' . $this->environment->getExecContainer()['name'] . '/subdirectory';

      $nightwatch_settings = json_decode(file_get_contents("{$this->codebase->getSourceDirectory()}/core/nightwatch.settings.json.default"), TRUE);
      $nightwatch_settings['BASE_URL'] = $base_url;
      $nightwatch_settings['DB_URL'] = $database_url;
      $nightwatch_settings['WEBDRIVER_HOSTNAME'] = $hostname;
      $nightwatch_settings['NIGHTWATCH_OUTPUT'] = "{$this->environment->getExecContainerSourceDir()}/nightwatch_output";
      file_put_contents("{$this->codebase->getSourceDirectory()}/core/nightwatch.settings.json", json_encode($nightwatch_settings));


      $runscript = "sudo NODE_ENV=testbot -u www-data {$this->runscript}";
      $result = $this->environment->executeCommands("$runscript");

      // Save some artifacts for the build
      if ($result->getSignal() == 0) {
        $this->saveContainerArtifact($this->environment->getExecContainerSourceDir() . '/nightwatch_output', 'nightwatch_xml');
      }
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
      "ln -s ${sourcedir} ${sourcedir}/subdirectory",
      "mkdir -p ${sourcedir}/nightwatch_output",
      "chown -fR www-data:www-data ${sourcedir}/sites",
      "chown -fR www-data:www-data ${sourcedir}/nightwatch_output",
    ];
    $result = $this->environment->executeCommands($setup_commands);
  }

}
