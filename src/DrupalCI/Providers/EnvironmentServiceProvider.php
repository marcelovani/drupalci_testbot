<?php

namespace DrupalCI\Providers;


use Docker\Docker;
use Docker\DockerClient;
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
      $environment = new Environment();
      $environment->inject($container);
      return $environment;
    };
  }

}
