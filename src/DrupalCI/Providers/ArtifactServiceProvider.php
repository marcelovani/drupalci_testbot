<?php

namespace DrupalCI\Providers;

use DrupalCI\Build\Artifact\Junit\JunitXmlBuilder;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Register services related to artifacts.
 */
class ArtifactServiceProvider implements ServiceProviderInterface {

  /**
   * Register our Artifact services.
   *
   * @param \Pimple\Container
   *   The service discovery container.
   */
  public function register(Container $container) {
    $container['junit_xml_builder'] = function ($container) {
      $builder = new JunitXmlBuilder();
      $builder->inject($container);
      return $builder;
    };
  }

}
