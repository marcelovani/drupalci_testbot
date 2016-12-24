<?php

/**
 * @file
 * Contains \DrupalCI\Build\Codebase\Codebase
 */

namespace DrupalCI\Build\Codebase;

use DrupalCI\Build\Codebase\Patch;
use DrupalCI\Injectable;
use Pimple\Container;

class Codebase implements CodebaseInterface, Injectable {

  /**
   * Style object.
   *
   * @var \DrupalCI\Console\DrupalCIStyle
   */
  protected $io;

  /**
   * @var \DrupalCI\Build\BuildInterface
   */
  protected $build;

  protected $extensionProjectSubDirectory = '';

  protected $projectName = '';

  protected $extensionPaths = '';

  public function inject(Container $container) {
    $this->io = $container['console.io'];
    $this->build = $container['build'];
  }

  /**
   * Any patches used to generate this codebase
   *
   * @var \DrupalCI\Build\Codebase\Patch[]
   */
  protected $patches;

  public function getPatches() {
    return $this->patches;
  }

  public function setPatches($patches) {
    $this->patches = $patches;
  }

  public function addPatch(Patch $patch) {
    if (!empty($this->patches) && !in_array($patch, $this->patches)) {
      $this->patches[] = $patch;
    }
  }

  /**
   * A storage variable for any modified files
   */
  protected $modified_files = [];

  public function getModifiedFiles() {
    return $this->modified_files;
  }

  /**
   * {@inheritdoc}
   */
  public function getModifiedFilesForNewPath($new_path) {
    $new_modified_files = [];

    $source_dir = $this->getSourceDirectory();
    foreach($this->getModifiedFiles() as $file) {
      if (strpos($file, $source_dir) === 0) {
        $new_modified_files[] = str_replace($source_dir, $new_path, $file);
      }
    }

    return $new_modified_files;
  }

  public function addModifiedFile($filename) {
    if (!is_array($this->modified_files)) {
      $this->modified_files = [];
    }
    if (!in_array($filename, $this->modified_files)) {
      $this->modified_files[] = $filename;
    }
  }

  public function addModifiedFiles($files) {
    foreach ($files as $file) {
      $this->addModifiedFile($file);
    }
  }

  /**
   * @inheritDoc
   */
  public function getSourceDirectory() {
    return $this->build->getBuildDirectory() . '/source';
  }

  /**
   * @inheritDoc
   */
  public function getAncillarySourceDirectory() {
    return $this->build->getBuildDirectory() . '/ancillary';
  }

  public function setupDirectories() {
    $result =  $this->build->setupDirectory($this->getSourceDirectory());
    if (!$result) {
      return FALSE;
    }
    $result =  $this->build->setupDirectory($this->getAncillarySourceDirectory());
    if (!$result) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @return string
   */
  public function getProjectName() {
    return $this->projectName;
  }

  /**
   * @param string $projectName
   */
  public function setProjectName($projectName) {
    $this->projectName = $projectName;
  }

  /**
   * @inheritDoc
   */
  public function getExtensionProjectSubdir() {
    return $this->extensionProjectSubDirectory;
  }

  /**
   * @param string $extensionProjectDir
   */
  public function setExtensionProjectSubdir($extensionProjectDir) {
    $this->extensionProjectSubDirectory = $extensionProjectDir;
  }

  /**
   * @return string
   */
  public function getExtensionPaths() {
    return $this->extensionPaths;
  }

  /**
   * @param string $extensionPaths
   */
  public function setExtensionPaths($extensionPaths) {
    $this->extensionPaths = $extensionPaths;
  }
  // TODO: get rid of this
  // this is a helper convenience function for geting the ultimate calculated
  // path set by composer.
  public function getTrueExtensionDirectory($type){
    return $this->extensionPaths[$type] . '/' . $this->projectName;
  }

}
