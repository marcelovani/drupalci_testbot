<?php

namespace DrupalCI\Plugin;

interface PluginManagerInterface {

  /**
   *
   * @param type $type
   * @param type $plugin_id
   */
  public function hasPlugin($type, $plugin_id);

  /**
   * @param $type
   * @param $plugin_id
   * @param array $configuration
   * @return \DrupalCI\Plugin\BuildTaskBase
   */
  public function getPlugin($type, $plugin_id, $configuration = []);

}
