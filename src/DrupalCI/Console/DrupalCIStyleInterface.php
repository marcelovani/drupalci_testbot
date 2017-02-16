<?php

namespace DrupalCI\Console;

use Symfony\Component\Console\Style\StyleInterface;

/**
 * DrupalCI input/output style.
 *
 * Added here for future expansion.
 */
interface DrupalCIStyleInterface extends StyleInterface {

  public function drupalCIError($type, $message);

}
