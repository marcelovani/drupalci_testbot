<?php

namespace DrupalCI\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

class GuzzleClientServiceProvider implements ServiceProviderInterface {
  /**
   * @inheritDoc
   */
  public function register(Container $container) {
    $container['http.client'] = function ($container) {
      return new Client();
    };
  }

}
