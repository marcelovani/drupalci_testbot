<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;


use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;

/**
 * @PluginID("update_dependencies_d7")
 */
class UpdateDependenciesD7 extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface, Injectable {

  protected $drupalPackageRepository = 'https://packages.drupal.org/7';

}
