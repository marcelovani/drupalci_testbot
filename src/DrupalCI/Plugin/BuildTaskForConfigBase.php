<?php

namespace DrupalCI\Plugin;

use Pimple\Container;

/**
 * Base class for plugins which only do things if they have a config file.
 */
abstract class BuildTaskForConfigBase extends BuildTaskBase {

  /**
   * @var \DrupalCI\Build\Codebase\CodebaseInterface
   */
  protected $codebase;

  /**
   * {@inheritdoc}
   */
  public function inject(Container $container) {
    parent::inject($container);
    $this->codebase = $container['codebase'];
  }

  /**
   * Returns the relative path to the config file if it exists.
   *
   * @param string
   *   The name of the file to look for. Example: .stylelint.json
   *
   * @return string
   *   Path to the config file, relative to the codebase source.
   *   Example: core/.stylelint.json
   */
  protected function getToolConfigFile($file_name) {
    $config_file = $this->codebase->getTrueExtensionSubDirectory(TRUE) . '/' . $file_name;
    if (!file_exists($this->codebase->getSourceDirectory() . '/' . $config_file))   {
      return '';
    }
    return $config_file;
  }
 
}
