<?php

namespace DrupalCI\Build\Codebase;


/**
 * Class Patch
 *
 * @package DrupalCI\Build\Codebase
 */
interface PatchInterface {

  /**
   * @return string
   */
  public function getFilename();

  /**
   * @return string
   */
  public function getTargetApplyDir();

  /**
   * @return string
   */
  public function getPatchApplyResults();

  /**
   * @param string $patch_apply_results
   */
  public function setPatchApplyResults($patch_apply_results);

  /**
   * Validate patch file and target directory
   *
   * @return bool
   */
  public function validate();

  /**
   * Validate file exists
   *
   * @return bool
   */
  public function validate_file();

  /**
   * Validate target directory exists
   *
   * @return bool
   */
  public function validate_target();

  /**
   * Apply the patch
   *
   * @return bool
   */
  public function apply();

  /**
   * Retrieves the files modified by this patch
   *
   * @return array|bool
   */
  public function getModifiedFiles();

}
