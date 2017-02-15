<?php

namespace DrupalCI\Plugin;

use Pimple\Container;

/**
 * Provide for plugins to use a static factory injection pattern.
 *
 * @see \DrupalCI\Injectable
 */
interface InjectablePluginInterface {

  /**
   * Factory method for creating plugin objects.
   *
   * @param Container $container
   * @param array $configuration_overrides
   * @param type $plugin_id
   * @param type $plugin_definition
   */
  static public function create(Container $container, array $configuration_overrides = [], $plugin_id = '', $plugin_definition = []);

}
