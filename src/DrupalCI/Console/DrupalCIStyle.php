<?php

namespace DrupalCI\Console;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * DrupalCI input/output style.
 *
 * Added here for future expansion.
 */
class DrupalCIStyle extends SymfonyStyle {

  public function drupalCIError($type, $message) {
    if (!empty($type)) {
      $this->writeln("<error>$type</error>");
    }
    if (!empty($message)) {
      $this->writeln("<comment>$message</comment>");
    }
  }

}
