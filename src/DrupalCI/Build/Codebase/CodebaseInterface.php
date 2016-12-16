<?php

namespace DrupalCI\Build\Codebase;

interface CodebaseInterface {

  public function getPatches();

  public function setPatches($patches);

  public function addPatch(Patch $patch);

  public function getModifiedFiles();

  public function addModifiedFile($filename);

  public function addModifiedFiles($files);

  /**
   * This is the codebase that we will test. It should be volume mounted over
   * to wherever the $execContainerSourceDir is set on the Environment object
   *
   * @return string
   */
  public function getSourceDirectory();

  /**
   * Temporary workspace directory where we can checkout repositories and
   * manipulate them prior to adding them to the main source directory.
   * Primarily used to check out a project, apply patches to composer.json,
   * and require that project as a local composer repo in order to see the
   * changed dependencies.
   *
   * @return string
   */
  public function getAncillarySourceDirectory();

  public function setupDirectories();

  /**
   * This is a temporary hack so that we can utilize the directory subdir
   * to identify fetches/patches/repos that belong to the project vs. deps.
   *
   * @return string
   */
  public function getExtensionProjectSubdir();

  public function setExtensionProjectSubdir($ancillaryDirectory);

  /**
   * This is the directory for the 'Project under test'.
   *
   * @return string
   */
  public function getProjectName();

  public function setProjectName($ancillaryDirectory);

  /**
   * For contributed modules, this is where the modules will get checked out
   * Needed so we can know where to run the tests.
   * It is a key value array of extension type to path location
   *
   * @return array
   */
  public function getExtensionPaths();

  public function setExtensionPaths($extensionPaths);


}
