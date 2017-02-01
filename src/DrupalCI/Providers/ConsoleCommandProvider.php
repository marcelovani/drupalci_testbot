<?php

namespace DrupalCI\Providers;

use DrupalCI\Console\Command\Run\RunCommand;
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
  public function register(Container $container) {
    // Console Commands
    $container['command.run'] = function ($container) {
      return new RunCommand();
    };

    $container['commands'] = function ($container) {
      return array(
        $container['command.run']
      );
    };
  }

}
