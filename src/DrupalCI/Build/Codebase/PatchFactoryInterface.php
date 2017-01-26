<?php

namespace DrupalCI\Build\Codebase;

interface PatchFactoryInterface {

  /**
   * @param string[] $patch_details
   * @param string $ancillary_workspace The real working directory.
   */
  public function getPatch($patch_details, $ancillary_workspace);

}
