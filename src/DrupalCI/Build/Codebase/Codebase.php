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

  public function inject(Container $container) {
    $this->io = $container['console.io'];
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
}
