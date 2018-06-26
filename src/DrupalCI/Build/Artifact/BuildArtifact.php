<?php

namespace DrupalCI\Build\Artifact;

use DrupalCI\Injectable;
use Pimple\Container;
use Symfony\Component\Filesystem\Filesystem;

class BuildArtifact implements BuildArtifactInterface, Injectable {

  /**
   * @var string
   *   The path to the artifact. Could be a directory or a file
   */
  protected $sourcePath;

  /**
   * @var string
   *   The path to the artifact once it has been stored.
   */
  protected $artifactPath;

  /**
   * @var bool
   *   Whether or not to clean up the artifact from the source. Useful for
   *   cleaning up the filesystem between repeat plugins.
   */
  protected $cleanse;

  /**
   * @var \DrupalCI\Build\BuildInterface
   *  The current build.
   */
  protected $build;

  /**
   * BuildArtifact constructor.
   *
   * @param $path
   *   Takes in the path of the artifact. Could be a host path or inside the
   * container, depending on whether its a ContainerTaskArtifact or not.
   * @param string $artifactpath
   *   Path within the artifact directory where this artifact should be stored.
   * @param bool $cleanse
   *   Whether or not to clean up the artifact from the source. Useful for
   * cleaning up the filesystem between repeat plugins.
   */
  public function __construct($path, $artifactpath = '', $cleanse = FALSE) {
    $this->sourcePath = $path;
    $this->artifactPath = $artifactpath;
    $this->cleanse = $cleanse;
  }

  /**
   * @inheritDoc
   */
  public function inject(Container $container) {
    $this->build = $container['build'];
  }

  /**
   * @inheritDoc
   */
  public function preserve() {
    $uid = posix_getuid();
    $gid = posix_getgid();
    $fs = new Filesystem();
    if (empty($this->artifactPath)) {
      $this->artifactPath = basename($this->sourcePath);
    }
    $this->artifactPath = $this->build->getArtifactDirectory() . "/" . $this->artifactPath;
    // Only copy files that are not already under the artifacts directory.
    if (strpos($this->sourcePath, $this->build->getArtifactDirectory()) === FALSE) {
      if (is_dir($this->sourcePath)) {
        $fs->mirror($this->sourcePath, $this->artifactPath);
      }
      else {
        $fs->copy($this->sourcePath, $this->artifactPath);
      }
    }
    $fs->chown($this->artifactPath, $uid, TRUE);
    $fs->chgrp($this->artifactPath, $gid, TRUE);
    if ($this->cleanse) {
      $fs->remove($this->sourcePath);
    }
  }

  /**
   * @inheritDoc
   */
  public function getSourcePath() {
    return $this->sourcePath;
  }

  /**
   *  @inheritDoc
   */
  public function getArtifactPath() {
    return $this->artifactPath;
  }

}
