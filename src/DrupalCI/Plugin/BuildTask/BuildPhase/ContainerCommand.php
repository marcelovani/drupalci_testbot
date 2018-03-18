<?php

namespace DrupalCI\Plugin\BuildTask\BuildPhase;

use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskException;
use Pimple\Container;

/**
 * @PluginID("container_command")
 */
class ContainerCommand extends BuildTaskBase implements BuildPhaseInterface, BuildTaskInterface {

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
    parent::inject($container);
    $this->environment = $container['environment'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfiguration() {
    return [
      'die-on-nonzero' => FALSE,
      'commands' => [],
    ];
  }

  /**
   * @inheritDoc
   */
  public function run() {
    $this->io->writeln('<info>Container execution.</info>');

    // Don't do anything if there's nothing to do.
    if (empty($this->configuration['commands'])) {
      $this->io->writeln('No commands to execute.');
      return 0;
    }

    // Normalize on arrays.
    if (!is_array($this->configuration['commands'])) {
      $this->configuration['commands'] = [$this->configuration['commands']];
    }

    // @todo: Add arbitrary container name. For now we only care about default
    //   PHP container.
    return $this->executeOnPhpContainer(
      $this->configuration['commands'],
      $this->configuration['die-on-nonzero']
    );
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
  protected function executeOnPhpContainer($commands, $die_on_fail) {
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
