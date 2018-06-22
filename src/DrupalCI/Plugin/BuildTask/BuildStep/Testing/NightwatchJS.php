<?php
namespace DrupalCI\Plugin\BuildTask\BuildStep\Testing;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use Symfony\Component\Dotenv\Dotenv;
use Pimple\Container;
/**
 * @PluginID("nightwatchjs")
 */
class NightwatchJS extends BuildTaskBase implements BuildStepInterface {
  /* @var  \DrupalCI\Build\Environment\DatabaseInterface */
  protected $system_database;

  /* @var \DrupalCI\Build\Codebase\CodebaseInterface */
  protected $codebase;
  /**
   * The path to run-tests.sh.
   *
   * @var string
   */
  protected $runscript = 'yarn --cwd /var/www/html/core test:nightwatch';
  /**
   * Junit XML builder service.
   *
   * @var \DrupalCI\Build\Artifact\Junit\JunitXmlBuilder
   */
  protected $junitXmlBuilder;
  public function inject(Container $container) {
    parent::inject($container);
    $this->system_database = $container['db.system'];
    $this->codebase = $container['codebase'];
  }
  /**
   * @inheritDoc
   */
  public function run() {
    $return = 0;
    if (file_exists("{$this->codebase->getSourceDirectory()}/core/.env.example")) {
      $dotenv = new Dotenv();
      $envpath = "{$this->codebase->getSourceDirectory()}/core/.env.example";

      $this->prepareFilesystem();

      $hostname = $this->environment->getChromeContainerHostname();
      $database_url = $this->system_database->getUrl();
      $base_url = 'http://' . $this->environment->getExecContainer()['name'] . '/subdirectory';

      $nightwatch_settings = $dotenv->parse(file_get_contents($envpath), $envpath);
      $nightwatch_settings['DRUPAL_TEST_CHROMEDRIVER_AUTOSTART'] = 'false';
      $nightwatch_settings['DRUPAL_TEST_WEBDRIVER_CHROME_ARGS'] = '--disable-gpu --headless';
      $nightwatch_settings['DRUPAL_TEST_BASE_URL'] = $base_url;
      $nightwatch_settings['DRUPAL_TEST_DB_URL'] = $database_url;
      $nightwatch_settings['DRUPAL_TEST_WEBDRIVER_HOSTNAME'] = $hostname;
      $nightwatch_settings['DRUPAL_TEST_WEBDRIVER_PORT'] = 9515;
      $nightwatch_settings['DRUPAL_NIGHTWATCH_OUTPUT'] = "nightwatch_output";
      if ($this->codebase->getProjectType() != 'core' ) {
        $nightwatch_settings['DRUPAL_NIGHTWATCH_SEARCH_DIRECTORY'] = "../";
      }

      $envfile = '';
      foreach ($nightwatch_settings as $env => $value) {
        $envfile .= $env . '=' . $value . "\n";
      }
      file_put_contents("{$this->pluginWorkDir}/.env", $envfile);
      $command = "sudo -u www-data cp {$this->environment->getContainerWorkDir()}/{$this->pluginDir}/.env {$this->environment->getExecContainerSourceDir()}/core/.env";
      $this->execRequiredEnvironmentCommands($command, "Failed to copy Nightwatch config file");

      #file_put_contents("{$this->codebase->getSourceDirectory()}/core/.env", $envfile);

      $runscript = "sudo BABEL_DISABLE_CACHE=1 -u www-data {$this->runscript}";
      if ($this->codebase->getProjectType() != 'core' ) {
        $runscript = "{$runscript} --tag={$this->codebase->getProjectName()}";
      }
      $result = $this->execEnvironmentCommands("$runscript");

      // Save some artifacts for the build
      if ($result->getSignal() == 0) {
        $this->saveContainerArtifact($this->environment->getExecContainerSourceDir() . '/core/nightwatch_output', 'nightwatch_output');
        $cmd = "find {$this->codebase->getSourceDirectory()}/core/nightwatch_output -name *.xml -print0 |xargs -0 -I{} cp {} {$this->pluginWorkDir}/junitxml/";
        $this->execCommands($cmd);
      }
      else {

        if (strpos($result->getError(), 'Error: No tests defined!')){
          $this->io->writeln("<error>No nightwatchjs tests tagged with {$this->codebase->getProjectName()}</error>");
          return 0;
        }
      }
      $return = $result->getSignal();
    }
    return $return;
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
      "mkdir -p /var/www/.yarn",
      "mkdir -p /var/www/.cache",
      "chown -fR www-data:www-data /var/www/.yarn /var/www/.cache",
      "chown -fR www-data:www-data ${sourcedir}/sites",
      "chown -fR www-data:www-data ${sourcedir}/nightwatch_output",
      "chown -fR www-data:www-data /var/www/.yarn",
      # Some tests create unwritable folders. Some JS lib has a hard time with
      # permissions.
      "find /var/www/html/sites -type d -exec chmod 777 {} \;",
    ];
    $result = $this->execEnvironmentCommands($setup_commands);
  }

}
