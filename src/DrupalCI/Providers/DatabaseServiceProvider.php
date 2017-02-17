<?php

namespace DrupalCI\Providers;

use Pimple\Container;
use DrupalCI\Build\Environment\Database;
use Pimple\ServiceProviderInterface;

class DatabaseServiceProvider implements ServiceProviderInterface {

  /**
   * Register all of our Databases. Currently there are two, system and results.
   *
   * @param Container $container
   */
  public function register(Container $container) {

    $container['db.system'] = function ($container) {
      $db = new Database($container['console.io'], 'system');
      return $db;
    };
    $container['db.results'] = function ($container) {
      $db = new Database($container['console.io'], 'results');
      return $db;
    };
  }

}
