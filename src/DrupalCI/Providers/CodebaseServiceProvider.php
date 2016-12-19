<?php

namespace DrupalCI\Providers;

use DrupalCI\Build\Build;
use DrupalCI\Build\Codebase\Codebase;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Yaml\Parser;

class CodebaseServiceProvider implements ServiceProviderInterface {
  /**
   * @inheritDoc
   */
  public function register(Container $container) {
    $container['codebase'] = function ($container) {
      $codebase = new Codebase();
      $codebase->inject($container);
      return $codebase;
    };
  }

}
