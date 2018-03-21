<?php

namespace DrupalCI\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Process\Process;

class ProcessServiceProvider implements ServiceProviderInterface {

  /**
   * @inheritDoc
   */
  public function register(Container $container) {
    $container['process'] = $container->factory(function ($container) {
      return new Process('');
    });
  }

}
