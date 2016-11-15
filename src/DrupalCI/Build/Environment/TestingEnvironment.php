<?php

namespace DrupalCI\Build\Environment;


class TestingEnvironment extends Environment implements EnvironmentInterface {

  /*
   * Overrides ContainerCommands check with a specific signal check.
   */
  protected function checkCommandStatus($signal) {
    if ($signal > 1) {
      $this->io->drupalCIError('Error', "Received a failed return code from the last command executed on the container.  (Return status: " . $signal . ")");
      return 1;
    }
    else {
      return 0;
    }
  }
}
