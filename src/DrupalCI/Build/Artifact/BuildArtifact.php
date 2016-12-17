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
  protected $path;

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
    $this->path = $path;
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
    // Only copy files that are not already under the artifacts directory.
    if (strpos($this->path, $this->build->getArtifactDirectory()) === FALSE) {
      if (is_dir($this->path)){
        $fs->mirror($this->path, $this->build->getArtifactDirectory() . "/" . basename($this->path));
      } else {
        $fs->copy($this->path, $this->build->getArtifactDirectory() . "/" . basename($this->path));
      }
    }
    $fs->chown($this->path, $uid, TRUE);
    $fs->chgrp($this->path, $gid, TRUE);
  }

  /**
   * @inheritDoc
   */
  public function getPath() {
    return $this->path;
  }

}
