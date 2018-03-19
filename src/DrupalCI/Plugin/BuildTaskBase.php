<?php

namespace DrupalCI\Plugin;

use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildTaskException;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * Base class for plugins.
 */
abstract class BuildTaskBase implements Injectable, BuildTaskInterface {

  /**
   * The plugin_id.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The plugin_label
   *
   * @var string
   */
  protected $pluginLabel;

  /**
   * Any variables that can affect the behavior of this plugin, that are
   * specific to this plugin, reside in a configuration array within the plugin.
   *
   * @var array
   *
   */
  protected $configuration = [];

  /**
   * Configuration overrides passed into the plugin.
   *
   * @var array
   */
  protected $configuration_overrides;

  /**
   * The plugin implementation definition.
   *
   * @var array
   */
  protected $pluginDefinition;

  /**
   * Style object.
   *
   * @var \DrupalCI\Console\DrupalCIStyle
   */
  protected $io;

  /**
   * The current build.
   *
   * @var \DrupalCI\Build\BuildInterface
   */
  protected $build;

  /**
   * The container.
   *
   * We need this to inject into other objects.
   *
   * @var \Pimple\Container
   */
  protected $container;

  /**
   * The working directory under the ancillary directory where temporary
   * artifacts are created.
   */
  protected $pluginWorkDir;

  /**
   * Convenience variable to get the PluginID.PluginLabel combo that will be
   * found in the container.
   */
  protected $pluginDir;

  /**
   * Any host commands and their output that are run by the build tasks should
   * get accumulated here and turned into an artifact.
   *
   * @var array
   */
  protected $buildTaskCommandOutput;

  /**
   * @var float
   */
  protected $startTime;

  /**
   * @var float
   *   Total time taken for this build task, including child tasks
   */
  protected $elapsedTime;

  /**
   * Constructs a Drupal\Component\Plugin\BuildTaskBase object.
   *
   * @param array $configuration_overrides
   *   A configuration array containing overrides from the build.yml file.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration_overrides = [], $plugin_id = '', $plugin_definition = []) {
    $this->configuration = $this->getDefaultConfiguration();
    // Set the plugin label as a special case.
    if (isset($configuration_overrides['plugin_label'])) {
      $this->pluginLabel = $configuration_overrides['plugin_label'];
      unset($configuration_overrides['plugin_label']);
    }
    $this->configuration_overrides = $configuration_overrides;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    // Compute the plugin's configuration.
    $this->configure();
    $this->override_config();
  }

  /**
   * Decorator for run functions to allow all of them to be timed.
   *
   */
  public function start() {
    $this->startTime = microtime(TRUE);
    $this->io->writeLn("<info>----------------   Starting <options=bold>" . $this->pluginId . "</>   ----------------</info>");

    $this->setup();
    $statuscode = $this->run();
    if (!isset($statuscode)) {
      return 0;
    }
    else {
      return $statuscode;
    }
  }

  /**
   * Decorator for complete functions to stop their timer.
   *
   * @param $childStatus
   */
  public function finish($childStatus) {
    $this->complete($childStatus);
    $this->teardown();
    $elapsed_time = microtime(TRUE) - $this->startTime;
    $this->elapsedTime = $elapsed_time;
    $datetime = new \DateTime();
    $this->io->writeLn("<info>---------------- Finished <options=bold>" . $this->pluginId . "</> in " . number_format($elapsed_time, 3) . " seconds ---------------- </info>");
  }

  private function setup(){
    // Sets up the artifact and ancillary directories for the plugins.

    $this->pluginDir = $this->pluginId;
    if (!empty($this->pluginLabel)) {
      $this->pluginDir = $this->pluginDir . '.' . $this->pluginLabel;
    }
    $this->pluginWorkDir = $this->build->getAncillaryWorkDirectory() . '/' . $this->pluginDir;
    $this->build->setupDirectory($this->pluginWorkDir);

  }

  private function teardown() {
    if (!empty($this->buildTaskCommandOutput)){
      $output = implode("\n", $this->buildTaskCommandOutput);
      $this->saveStringArtifact('command_output',$output);

    }
  }

  /**
   * @inheritDoc
   */
  public function getElapsedTime($inclusive = TRUE) {
    return $this->elapsedTime;
  }

  /**
   * Execute a shell command.
   * Will save the output as a command_output artifact on the build,
   * however, sometimes you want to explicitly name an artifact that is the
   * output of a command. You can skip saving the command output in that case
   * if the plugin already has other plans for the output.
   *
   * @param string $command
   * @param string[] &$output
   * @param int &$return_var
   * @param bool $save_output
   *
   * @return string|string[]
   */
  protected function exec($command, &$output, &$return_var, $save_output = TRUE) {
    // TODO: detect if this is there already and only add if absent.
    $command .= ' 2>&1';
    exec($command, $output, $return_var);
    $output = implode("\n",$output);
    if ($save_output) {
      $this->buildTaskCommandOutput[] = "Host command: ${command}";
      $this->buildTaskCommandOutput[] = "Return code: ${return_var}";
      $this->buildTaskCommandOutput[] = "Output: ${output}";
    }
    return $output;
  }

  /**
   * Execute a shell command, terminate build on failure, emit message.
   *
   * @param string $command
   * @param string $failure_message
   * @param bool $save_output
   *
   * @return string
   * @throws \DrupalCI\Plugin\BuildTask\BuildTaskException
   */
  protected function execRequiredCommand($command, $failure_message, $save_output = TRUE) {

    $this->exec($command, $output, $return_var, $save_output);
    if ($return_var !== 0) {
      $output = $command . "\nReturn Code:" . $return_var . "\n" . $output;
      $this->terminateBuild($failure_message, $output);
    }
    return $output;

  }

  // TODO 2851000 Ensure saving host artifacts works
  protected function saveHostArtifact($filepath, $savename) {
    $this->build->setupDirectory($this->build->getArtifactDirectory() . '/' . $this->pluginDir);

    $savename = $this->pluginDir . '/' . $savename;
    $this->build->addArtifact($filepath, $savename);
  }

  protected function saveStringArtifact($filename, $contents) {
    $this->build->setupDirectory($this->build->getArtifactDirectory() . '/' . $this->pluginDir);

    $filename = $this->pluginDir . '/' . $filename;
    $this->build->addStringArtifact($filename, $contents);
  }

  /**
   * @param $filepath
   *   The full filepath of the artifact within the container environment.
   * @param $savename
   *   The name of the file or directory that we wish the preserved artifact to
   *  have.
   */
  protected function saveContainerArtifact($filepath, $savename) {
    $this->build->setupDirectory($this->build->getArtifactDirectory() . '/' . $this->pluginDir);

    $this->build->addContainerArtifact($filepath, $this->pluginDir . '/' . $savename);
  }


  public function inject(Container $container) {
    $this->build = $container['build'];
    $this->io = $container['console.io'];
    $this->container = $container;
  }

  /**
   * @inheritDoc
   */
  public function getComputedConfiguration() {
    return $this->configuration;
  }

  protected function override_config() {

    if (!empty($this->configuration_overrides)) {
      if ($invalid_overrides = array_diff_key($this->configuration_overrides, $this->configuration)) {
        // @TODO: somebody is trying to override a non-existant configuration value. Throw an exception? print a warning?
      }
      $this->configuration = array_merge($this->configuration, array_intersect_key($this->configuration_overrides, $this->configuration));
    }
  }

  /**
   * @inheritDoc
   */
  public function complete($childStatus) {
    // TODO: Implement complete() method.
  }

  /**
   * @inheritDoc
   */
  public function configure() {
    // Interface placeholder for plugins lacking config.
  }

  /**
   * @inheritDoc
   */
  public function run() {
    // Interface placeholder for plugins that have no run phase

  }

  /**
   * @inheritDoc
   */
  public function getChildTasks() {
    // TODO: Implement getChildTasks() method.
  }

  /**
   * @inheritDoc
   */
  public function setChildTasks($buildTasks) {
    // TODO: Implement setChildTasks() method.
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function terminateBuild($errorLabel, $errorDetails = '') {
    $this->io->drupalCIError($errorLabel, $errorDetails);
    throw new BuildTaskException($errorLabel, $errorDetails);
  }
}
