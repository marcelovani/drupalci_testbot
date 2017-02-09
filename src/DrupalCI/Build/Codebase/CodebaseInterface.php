<?php

namespace DrupalCI\Build\Codebase;

interface CodebaseInterface {

  public function addPatch(PatchInterface $patch);

  public function getModifiedFiles();

  /**
   * Returns an array of modified php files, relative to the source directory.
   */
  public function getModifiedPhpFiles();

  public function addModifiedFile($filename);

  public function addModifiedFiles($files);

  /**
   * This is the codebase that we will test. It should be volume mounted over
   * to wherever the $execContainerSourceDir is set on the Environment object
   *
   * @return string
   */
  public function getSourceDirectory();

  public function setupDirectories();

  /**
   * ExtensionProjectSubDir is what gets passed to us via the --directory
   * command. It is *not* where the extensions actually exist.
   *
   * @return string
   */
  public function getExtensionProjectSubdir();

  public function setExtensionProjectSubdir($extensionDir);

  /**
   * The name of the project under test.
   *
   * @return string
   */
  public function getProjectName();

  public function setProjectName($projectName);

  /**
   * For contributed modules, this is where the modules will get checked out
   * Needed so we can know where to run the tests.
   * It is a key value array of extension type to path location
   *
   * @return array
   */
  public function getExtensionPaths();

  public function setExtensionPaths($extensionPaths);

  public function getTrueExtensionDirectory($type);

  public function getComposerDevRequirements();

  public function getInstalledComposerPackages();

}
