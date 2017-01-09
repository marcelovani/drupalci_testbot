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
  public function getModifiedPhpFiles() {
    $host_source_dir = $this->getSourceDirectory();
    $phpfiles = [];
    foreach ($this->modified_files as $file) {
      $file_path = $host_source_dir . "/" . $file;
      // Checking for: if not in a vendor dir, if the file still exists, and if the first 32 (length - 1) bytes of the file contain <?php
      if (file_exists($file_path)) {
        $isphpfile = strpos(fgets(fopen($file_path, 'r'), 33), '<?php') !== FALSE;
        $not_vendor = strpos($file, 'vendor/') === FALSE;
        if ($not_vendor && $isphpfile) {
          $phpfiles[] = $file;
        }
      }
    }
    return $phpfiles;
  }

  public function addModifiedFile($filename) {
    // Codebase' modified files should be a relative path and not
    // contain the host or container environments' source path.
    if (substr($filename, 0, strlen($this->getSourceDirectory())) == $this->getSourceDirectory()) {
      $filename = substr($filename, strlen($this->getSourceDirectory())+1);
    }
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

  /**
   * Returns a list of require-dev packages for the current project.
   *
   * @return array
   */
  public function getComposerDevRequirements() {
    $install_json = $this->getSourceDirectory() . '/vendor/composer/installed.json';
    $packages = [];
    if (file_exists($install_json)) {
      $installed_json = json_decode(file_get_contents($install_json), TRUE);
      foreach ($installed_json as $package) {
        if ($package['name'] == "drupal/" . $this->projectName) {
          if (!empty($package['require-dev'])) {
            $this->io->writeln("<error>Adding testing (require-dev) dependencies.</error>");
            foreach ($package['require-dev'] as $dev_package => $constraint) {
              $packages[] = escapeshellarg($dev_package . ":" . $constraint);
            }
          }
        }
      }
      return $packages;
    }
    return [];
  }

}
