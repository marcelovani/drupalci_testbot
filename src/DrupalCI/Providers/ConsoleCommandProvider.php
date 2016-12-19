<?php

namespace DrupalCI\Providers;

use DrupalCI\Console\Command\Docker\DockerBuildCommand;
use DrupalCI\Console\Command\Docker\DockerRemoveCommand;
use DrupalCI\Console\Command\Init\InitAllCommand;
use DrupalCI\Console\Command\Init\InitBaseContainersCommand;
use DrupalCI\Console\Command\Init\InitDatabaseContainersCommand;
use DrupalCI\Console\Command\Init\InitDependenciesCommand;
use DrupalCI\Console\Command\Init\InitDockerCommand;
use DrupalCI\Console\Command\Init\InitWebContainersCommand;
use DrupalCI\Console\Command\Docker\DockerPullCommand;
use DrupalCI\Console\Command\Run\RunCommand;
use DrupalCI\Console\Command\Status\StatusCommand;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Command service provider for all our CLI commands.
 *
 * Note that this provider requires the existence of console.input and
 * console.output, so it must be registered after those services are available.
 */
class ConsoleCommandProvider implements ServiceProviderInterface {

  /**
   * Register all our console commands.
   *
   * @param Container $container
   */
  public function register(Container $container)
  {
    // Console Commands
    $container['command.status'] = function ($container) {
      return new StatusCommand();
    };
    $container['command.run'] = function ($container) {
      return new RunCommand();
    };

    $container['commands'] = function ($container) {
      return array(
        $container['command.status'],
        $container['command.run']
      );
    };
  }

}
