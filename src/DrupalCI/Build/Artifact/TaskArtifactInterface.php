<?php

namespace DrupalCI\Build\Artifact;


interface TaskArtifactInterface {

  /**
   * @return int
   *   Moves the artifact to the build artifacts directory. Implementing classes
   * should know whether that is inside, or outside of the container.
   */
  public function preserve();

  /**
   * @return string
   *   Returns the full path to the artifact. It can either be a directory or
   * full filename.
   */
  public function getPath();

}
