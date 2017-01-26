<?php

namespace DrupalCI\Providers;

use DrupalCI\Build\Codebase\PatchFactory;
use DrupalCI\Console\DrupalCIConsoleApp;
use DrupalCI\Plugin\PluginManagerFactory;
use DrupalCI\Providers\DockerServiceProvider;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use DrupalCI\Providers\DatabaseServiceProvider;
use DrupalCI\Providers\YamlServiceProvider;
use DrupalCI\Providers\GuzzleClientServiceProvider;
use DrupalCI\Providers\CommandResultServiceProvider;

/**
 * Registers application-level services.
 */
class DrupalCIServiceProvider implements ServiceProviderInterface {

  /**
    * Register all our app-level services.
    *
    * @param Container $container
    */
  public function register(Container $container) {
    $container->register(new DockerServiceProvider());
    $container->register(new DatabaseServiceProvider());
    $container->register(new YamlServiceProvider());
    $container->register(new GuzzleClientServiceProvider());
    $container->register(new BuildServiceProvider());
    $container->register(new CodebaseServiceProvider());
    $container->register(new EnvironmentServiceProvider());
    $container->register(new CommandResultServiceProvider());
    $container['console'] = function ($container) {
      $console = new DrupalCIConsoleApp('DrupalCI - CommandLine', '0.2');
      $console->inject($container);
      return $console;
    };
    $container['plugin.manager.factory'] = function ($container) {
      return new PluginManagerFactory($container);
    };
    // fugly.
    $container['app.root'] = __DIR__ . "/../../..";
    $container['patch_factory'] = function ($container) {
      return new PatchFactory($container);
    };
  }
}
