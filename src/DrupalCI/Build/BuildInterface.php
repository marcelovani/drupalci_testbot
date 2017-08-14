<?php

namespace DrupalCI\Build;

interface BuildInterface {

  /**
   * @return string
   */
  public function getBuildType();

  /**
   * @return string
   */
  public function getBuildId();

  /**
   * @param string
   */
  public function setBuildId($id);

  /**
   * @return string
   *
   * The filename that was originally used to define this build.
   */
  public function getBuildFile();

  /**
   * Full path to build defintion file if present in the project under test.
   *
   * If our codebase project (core, contrib) has a drupalci.yml file, return a
   * string to it from here,. Generally this will be used to launch the
   * user-space build process.
   *
   * @return string|null
   *   The full path to the project's drupalci.yml file if it has one.
   */
  public function getProjectBuildFile();

  /**
   * @param string
   * Takes in either the full path to a build.yml file, or the name of one of
   * the predefined build_definitions like simpletest or simpletest7, or if
   * null, defaults to simpletest.  Once it loads the yaml definition, it
   * recursively iterates over the definition creating and configuring the
   * build plugins for this build.
   */
  public function generateBuild($build_file);

  public function generateBootstrapBuild();

  public function generateEnvironmentBuild($project_build_file);

  /**
   * Executes a configured build.
   *
   * @return mixed
   */
  public function executeBuild();

  /**
   * Preserve build artifacts for the build object.
   *
   * We can execute multiple builds per build object. This preserves artifacts
   * from all of them.
   */
  public function preserveBuildArtifacts();


  /**
   * This is the directory where we place everything specific to this build
   * The primary exception of something that is needed that does not live
   * under the build directory is the Database.
   *
   * @return mixed
   */
  public function getBuildDirectory();

  /**
   * This is the directory where we place all of our artifacts.
   *
   * @return mixed
   */
  public function getArtifactDirectory();

  /**
   * Temporary workspace directory where we can checkout repositories and
   * manipulate them prior to adding them to the main source directory.
   * Primarily used to check out a project, apply patches to composer.json,
   * and require that project as a local composer repo in order to see the
   * changed dependencies.
   *
   * @return string
   */
  public function getAncillaryWorkDirectory();

  /**
   * This is the directory where core dumps should end up on the host os
   *
   * @return mixed
   */
  public function getHostCoredumpDirectory();

  /**
   * This is the directory on the host where composer cache lives.
   *
   * @return mixed
   */
  public function getHostComposerCacheDirectory();

  /**
   * This is the directory where we place artifacts that can be parsed
   * by jenkins xml parsing. It is usually located *under* the artifacts
   * directory
   *
   * @return mixed
   */
  public function getXmlDirectory();

  /**
   * This is where we put the database It should be volume mounted over
   * to wherever the data directory specifies from the Database Environment
   *
   * @return mixed
   */
  public function getDBDirectory();

  public function generateBuildId();

  public function addArtifact($path, $artifactpath);

  public function addContainerArtifact($containerpath, $artifactpath);

  /**
   * @param $filename
   * @param $string
   *
   * Takes in a string, and saves it as an artifact in the artifact directory.
   */
  public function addStringArtifact($filename, $string);

  public function getBuildArtifacts();

  public function setupDirectory($directory);

}
