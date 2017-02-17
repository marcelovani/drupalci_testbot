<?php

namespace DrupalCI\Providers;

use DrupalCI\Build\Environment\Environment;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class EnvironmentServiceProvider implements ServiceProviderInterface {

  /**
   * Register our Environment
   *
   * @param Container $container
   */
  public function register(Container $container) {

    // Parent Docker object
    $container['environment'] = function ($container) {
      return Environment::create($container);
    };
  }

}
