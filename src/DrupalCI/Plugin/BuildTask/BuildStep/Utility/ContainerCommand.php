<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\Utility;

use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskException;
use Pimple\Container;

/**
 * @PluginID("container_command")
 */
class ContainerCommand extends Command implements BuildStepInterface, BuildTaskInterface {

  /**
   * The testing environment.
   *
   * @var \DrupalCI\Build\Environment\EnvironmentInterface
   */
  protected $environment;

  /**
   * {@inheritdoc}
   */
  public function inject(Container $container) {
    $this->environment = $container['environment'];
    parent::inject($container);
  }

  /**
   * Execute the commands in the PHP container.
   *
   * @param string[] $commands
   * @param bool $die_on_fail
   *
   * @todo: Explicitly set the container in executeCommands().
   * @return int
   * @throws \DrupalCI\Plugin\BuildTask\BuildTaskException
   */
  protected function execute($commands, $die_on_fail) {
    // Set some environment variables for these executions.
    $this->command_environment[] = "HOST_SOURCE_DIR={$this->codebase->getSourceDirectory()}";
    $this->command_environment[] = "HOST_PROJECT_DIR={$this->codebase->getSourceDirectory()}/{$this->codebase->getTrueExtensionSubDirectory()}";
    $this->command_environment[] = "TEST_SOURCE_DIR={$this->environment->getExecContainerSourceDir()}";
    $this->command_environment[] = "TEST_PROJECT_DIR={$this->environment->getExecContainerSourceDir()}/{$this->codebase->getTrueExtensionSubDirectory()}";

    if ($die_on_fail) {
      $result = $this->execRequiredEnvironmentCommands($commands, 'Custom Commands Failed');
    }
    else {
      $result = $this->execEnvironmentCommands($commands);
    }
    // exedRequiredComannds should terminate The build further down if there's
    // an error. And since we have no idea what to do with a custom command
    // that isnt required, we'll just return 0 at this point.
    // Maybe this should return $result->getSignal() instead and make sure
    // devs know about 0, 1, and 2 ?
    return 0;
  }
}
