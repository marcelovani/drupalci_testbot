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
   */
  public function __construct($path) {
    $this->sourcePath = $path;
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
    $this->artifactPath = $this->build->getArtifactDirectory() . "/" . basename($this->sourcePath);
    // Only copy files that are not already under the artifacts directory.
    if (strpos($this->sourcePath, $this->build->getArtifactDirectory()) === FALSE) {
      if (is_dir($this->sourcePath)){
        $fs->mirror($this->sourcePath, $this->artifactPath);
      } else {
        $fs->copy($this->sourcePath, $this->artifactPath);
      }
    }
    $fs->chown($this->sourcePath, $uid, TRUE);
    $fs->chgrp($this->sourcePath, $gid, TRUE);
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
