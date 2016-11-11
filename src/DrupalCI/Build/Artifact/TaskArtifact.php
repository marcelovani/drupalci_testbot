<?php

namespace DrupalCI\Build\Artifact;


class TaskArtifact implements TaskArtifactInterface {

  protected $path;

  /**
   * TaskArtifact constructor.
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
  public function preserve() {
    // TODO: Implement preserve() method.
  }

  /**
   * @inheritDoc
   */
  public function getPath() {
    return $this->path;
  }

}
