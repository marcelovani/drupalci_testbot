<?php

/**
 * @file
 * Base Helper class for Drupal CI.
 */

namespace DrupalCI\Helpers;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Just some helpful debugging stuff for now.
 */
class DrupalCIHelperBase {

  public function locate_binary($cmd) {
    return shell_exec("command -v " . escapeshellcmd($cmd));
  }

}
