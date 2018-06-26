<?php

namespace DrupalCI\Build\Artifact;

use DrupalCI\Injectable;
use Pimple\Container;
use Symfony\Component\Filesystem\Filesystem;

class StringBuildArtifact extends BuildArtifact{

  /**
   * @var string
   *   The string to save as an artifact
   */
  protected $artifactContents;
  /** @noinspection PhpMissingParentConstructorInspection */

  /**
   * BuildArtifact constructor.
   *
   * @param $contents
   *   The string that should be preserved as a build artifact.
   * @param string $artifactpath
   *   Path within the artifact directory where this artifact should be stored.
   */
  public function __construct($contents, $artifactpath = '') {
    $this->artifactContents = $contents;
    $this->artifactPath = $artifactpath;
  }

  /**
   * @inheritDoc
   */
  public function preserve() {
    $this->artifactPath = $this->build->getArtifactDirectory() . "/" . $this->artifactPath;

    $info = pathinfo($this->artifactPath);
    if (!is_dir($info['dirname'])) {
      $result = mkdir($info['dirname'], 0777, TRUE);
    }
    $uid = posix_getuid();
    $gid = posix_getgid();
    $fs = new Filesystem();
    $fs->appendToFile($this->artifactPath, $this->artifactContents);
    $fs->chown($this->artifactPath, $uid, TRUE);
    $fs->chgrp($this->artifactPath, $gid, TRUE);
  }
}
