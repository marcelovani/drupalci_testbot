<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\Utility;

use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskException;
use Pimple\Container;

/**
 * @PluginID("host_command")
 */
class HostCommand extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface {

  /**
   * The codebase.
   *
   * @var \DrupalCI\Build\Codebase\CodebaseInterface
   */
  protected $codebase;

  /**
   * {@inheritdoc}
   */
  public function inject(Container $container) {
    parent::inject($container);
    $this->codebase = $container['codebase'];
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
    $this->io->writeln('<info>Host command.</info>');

    // Don't do anything if there's nothing to do.
    if (empty($this->configuration['commands'])) {
      $this->io->writeln('No commands to execute.');
      return 0;
    }

    // Normalize on arrays.
    if (!is_array($this->configuration['commands'])) {
      $this->configuration['commands'] = [$this->configuration['commands']];
    }

    // Execute.
    return $this->executeOnHost(
      $this->configuration['commands'],
      $this->configuration['die-on-nonzero']
    );
  }

  /**
   * Execute the commands on the host environment.
   *
   * @param type $commands
   * @param type $die_on_fail
   * @return int
   * @throws BuildTaskException
   */
  protected function executeOnHost($commands, $die_on_fail) {
    if (!chdir($this->codebase->getWorkingDirectory())) {
      $message = 'Unable to change working directory to source directory.';
      if ($die_on_fail) {
        $this->terminateBuild($message);
      }
      $this->io->drupalCIError($message);
      return 0;
    }
    foreach ($commands as $key => $command) {
      try {
        $this->execWithArtifact($command, 'command_output.' . $key);
      } catch (BuildTaskException $e) {
        if ($die_on_fail) {
          throw $e;
        }
      }
    }
    return 0;
  }

}
