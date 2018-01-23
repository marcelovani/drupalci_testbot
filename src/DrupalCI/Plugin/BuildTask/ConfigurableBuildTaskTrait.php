<?php

namespace DrupalCI\Plugin\BuildTask;

use Pimple\Container;

/**
 * Trait for plugins which only do things if they have a config file.
 */
trait ConfigurableBuildTaskTrait {

  /**
   * @var \DrupalCI\Build\Codebase\CodebaseInterface
   */
  protected $codebase;

  /**
   * Returns the relative path to the config file if it exists.
   *
   * @param string $file_name
   *   The name of the file to look for. Example: .stylelint.json
   * @param \DrupalCI\Build\Codebase\CodebaseInterface $codebase
   *   A Codebase object.
   * @return string
   *   Path to the config file, relative to the codebase source.
   *   Example: core/.stylelint.json
   */
  protected function getToolConfigFile($file_name, $codebase) {
    $project = $this->codebase->getProjectName();
    if ($project == 'drupal') {
      $config_file =  'core/' . $file_name;
    } else {
      $config_file = $codebase->getTrueExtensionSubDirectory() . '/' . $file_name;
    }
    $config_file = $codebase->getTrueExtensionSubDirectory() . '/' . $file_name;
    if (!file_exists($codebase->getSourceDirectory() . '/' . $config_file))   {
      return '';
    }
    return $config_file;
  }

}
