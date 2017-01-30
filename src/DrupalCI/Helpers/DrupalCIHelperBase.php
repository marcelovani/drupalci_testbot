<?php

namespace DrupalCI\Helpers;

/**
 * Just some helpful debugging stuff for now.
 */
class DrupalCIHelperBase {

  public function locate_binary($cmd) {
    return shell_exec("command -v " . escapeshellcmd($cmd));
  }

}
