<?php

namespace DrupalCI\Plugin;

use Pimple\Container;

class PluginManagerFactory {
  protected $container;

  public function __construct(Container $container) {
    $this->container = $container;
  }

  /**
   * Create a plugin manager.
   *
   * @param string $plugin_type
   * @return \DrupalCI\Plugin\PluginManager
   */
  public function create($plugin_type) {
    return new PluginManager($plugin_type, $this->container);
  }

}
