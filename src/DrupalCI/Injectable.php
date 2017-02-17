<?php

namespace DrupalCI;

use Pimple\Container;

/**
 * Allows classes to signal that they can receive container injection.
 *
 * @see \DrupalCI\InjectableTrait
 */
interface Injectable {

  public static function create(Container $container);

}
