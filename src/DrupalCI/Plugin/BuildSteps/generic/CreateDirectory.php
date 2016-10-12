<?php

/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\generic\CreateDirectory.
 */

namespace DrupalCI\Plugin\BuildSteps\generic;

use DrupalCI\Plugin\BuildSteps\generic\ContainerCommand;
use DrupalCI\Build\BuildInterface;

/**
 * @PluginID("mkdir")
 *
 * Creates a directory on the job runner host
 */
class CreateDirectory extends ContainerCommand {

  /**
   * {@inheritdoc}
   */
  public function run(BuildInterface $job, $directories) {
    // Data format: 'directory' or array('directory1', 'directory2')
    // $data May be a string if one directory required, or array if multiple
    // Normalize data to the array format, if necessary
    $directories = is_array($directories) ? $directories : [$directories];
    foreach ($directories as $directory) {
      $cmd = "mkdir -p $directory";
      parent::run($job, $cmd);
    }
  }
}
