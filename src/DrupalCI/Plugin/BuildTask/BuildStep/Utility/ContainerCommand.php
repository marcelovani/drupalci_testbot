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
class ContainerCommand extends Command implements BuildStepInterface,BuildTaskInterface {

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
   * @return int
   * @throws BuildTaskException
   *
   * @todo: Explicitly set the container in executeCommands().
   */
  protected function execute($commands, $die_on_fail) {
    // @todo: Add contrib path stuff.
    $commands = array_merge(
      ['cd ' . $this->environment->getExecContainerSourceDir()],
      $commands
    );

    $result = $this->environment->executeCommands($commands);
    $this->saveStringArtifact('php-container-command', $result->getOutput());
    if ($result->getSignal() != 0) {
      $message = 'Return code: ' . $result->getSignal();
      if ($die_on_fail) {
        $this->terminateBuild($message, $result->getError());
      }
      else {
        $this->io->drupalCIError($message, $result->getError());
      }
    }
    return 0;
  }

}
