<?php

namespace DrupalCI\Providers;


use DrupalCI\Build\Environment\CommandResult;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class CommandResultServiceProvider implements ServiceProviderInterface {
  /**
   * @inheritDoc
   */
  public function register(Container $container) {
    $container['command.result'] = $container->factory(function ($container) {
      return new CommandResult();
    });
  }

}
