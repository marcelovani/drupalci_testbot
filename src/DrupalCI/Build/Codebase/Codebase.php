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
  public function getTmpDirectory() {
    return $this->build->getBuildDirectory() . '/tmp';
  }

  public function setupDirectories() {
    $result =  $this->build->setupDirectory($this->getSourceDirectory());
    if (!$result) {
      return FALSE;
    }
    $result =  $this->build->setupDirectory($this->getTmpDirectory());
    if (!$result) {
      return FALSE;
    }
    return TRUE;
  }
}
