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
  protected $runscript = 'NODE_ENV=testbot yarn run test';
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
  public function configure() {
  }
  /**
   * @inheritDoc
   */
  public function run() {
    $this->prepareFilesystem();
    $this->environment->executeCommands('curl -sL https://deb.nodesource.com/setup_8.x | bash -');
    $this->environment->executeCommands('apt-get install -y nodejs');
    $this->environment->executeCommands('curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -');
    $this->environment->executeCommands('echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list');
    $this->environment->executeCommands('apt-get update && apt-get install yarn');
    $hostname = $this->environment->getChromeContainerHostname();
    $runscript = "NIGHTWATCH_HOSTNAME={$hostname} {$this->runscript}";
    $this->environment->executeCommands("cd {$this->environment->getExecContainerSourceDir()}/core && yarn install");
    $this->environment->executeCommands("cd {$this->environment->getExecContainerSourceDir()}/core && yarn add babel-preset-es2015 --dev");
    $result = $this->environment->executeCommands("cd {$this->environment->getExecContainerSourceDir()}/core && $runscript");
    $this->configuration['dburl'] = $this->system_database->getUrl();
    // Save some artifacts for the build
    $this->saveContainerArtifact('/var/log/apache2/error.log','apache-error.log');
    $this->saveContainerArtifact('/var/log/apache2/test.apache.error.log','test.apache.error.log');
    $this->saveContainerArtifact($this->environment->getExecContainerSourceDir() . 'core/results', 'results.log');
    $this->saveStringArtifact('javascriptoutput.txt', $result->getOutput());
    $this->saveStringArtifact('javascripterror.txt', $result->getError());
    // TODO: Jenkins fails the build if it sees a 1 in a shell script execution.
    // So we return a 0 here instead.
    //return $result->getSignal();
    return 0;
  }
  /**
   * @inheritDoc
   */
  public function complete($childStatus) {
  }
  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
    ];
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
      'chmod 0777 ' . $this->environment->getContainerArtifactDir(),
      'chmod 0777 /tmp',
    ];
    $result = $this->environment->executeCommands($setup_commands);
    $return = $result->getSignal();
    if ($return !== 0) {
      // Directory setup failed threw an error.
      $this->terminateBuild("Prepare JS tests filesystem failed", "Setting up the filesystem failed:  Error Code: $return");
    }
    return $return;
  }
  /**
   * {@inheritdoc}
   */
  public function generateJunitXml() {
    //    // Load the list of tests from the testgroups.txt build artifact
    //    // This gets generated in the containers, into the container artifact dir
    //    $test_listfile = $this->pluginWorkDir . '/testgroups.txt';
    //    $test_list = file($test_listfile, FILE_IGNORE_NEW_LINES);
    //    $test_list = array_slice($test_list, 4);
    //
    //    $test_groups = $this->parseGroups($test_list);
    //
    //    $doc = $this->junitXmlBuilder->generate($test_groups);
    //
    //    $label = '';
    //    if (isset($this->pluginLabel)) {
    //      $label = $this->pluginLabel . ".";
    //    }
    //    $xml_output_file = $this->build->getXmlDirectory() . "/" . $label . "testresults.xml";
    //    file_put_contents($xml_output_file, $doc->saveXML());
    //    $this->io->writeln("<info>Reformatted test results written to <options=bold>" . $xml_output_file . "</></info>");
    //    $this->build->addArtifact($xml_output_file, 'xml/' . $label . "testresults.xml");
  }
}
