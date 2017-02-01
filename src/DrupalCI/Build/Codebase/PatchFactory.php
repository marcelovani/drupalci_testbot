<?php

namespace DrupalCI\Build\Codebase;

use DrupalCI\Injectable;
use Pimple\Container;

/**
 * A service which makes patch objects for you to use.
 */
class PatchFactory implements PatchFactoryInterface {

  protected $container;

  public function __construct(Container $container) {
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public function getPatch($patch_details, $ancillary_workspace) {
    $patch = new Patch($patch_details, $ancillary_workspace);
    if ($patch instanceof Injectable) {
      $patch->inject($this->container);
    }
    return $patch;
  }

}
