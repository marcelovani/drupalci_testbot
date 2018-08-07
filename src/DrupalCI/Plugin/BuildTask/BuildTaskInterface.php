<?php

namespace DrupalCI\Plugin\BuildTask;

/**
 * Interface for all build tasks.
 *
 * All tasks receive these signals in these order:
 * start->run->complete->finish
 *
 * @package Plugin
 */
interface BuildTaskInterface {

  /**
   * Prepares this task to run.
   *
   *   An array of configuration for this build task determined by the following
   *   precedence ordering:
   *     1. Default values for this task.
   *     2. Any Environment Variables that should override the defaults
   *     3. Any command line settings that should override the defaults
   *     4. Any passed in overrides that are provided from the build.yml
   */
  public function configure();

  /**
   * Allow the task to know that it is about to run.
   *
   * Mainly a decorator for run() functions to allow all of them to be timed.
   *
   * start() and finish() are the bookends wrapping all child tasks of the
   * current one.
   */
  public function start();

  /**
   * Perform the main purpose of the task.
   *
   * @return int
   *   returns the status code of this BuildTask's execution. 0 = pass,
   *   1 = fail, and 2 = exception.  Note that if this BuildTask needs to halt
   *   execution of the build, it should throw a BuildTaskException rather than
   *   return a 2.
   *
   * @throws \DrupalCI\Plugin\BuildTask\BuildTaskException
   *   This exception should halt the build and allow for cleanup and artifact
   *   collection.
   */
  public function run();

  /**
   * Allow the task to determine what to do, based on child task status.
   *
   * Mainly a decorator for complete functions to stop their timer.
   *
   * start() and finish() are the bookends wrapping all child tasks of the
   * current one.
   *
   * @param $childStatus
   *   aggregate status code of all child tasks
   */
  public function finish($childStatus);

  /**
   * Allows the plugin to have a chance to do something after run().
   *
   * @param $childStatus
   *   aggregate status code of all child tasks
   *
   */
  public function complete($childStatus);

  /**
   * @param boolean $inclusive
   *   If true, will return the total elasped time for this task and all of its
   *   chilren.  If false, will return the elapsed time for this task, minus
   *   the time of its children.
   *
   * @return float
   *
   *   Returns the time seconds.microseconds
   */
  public function getElapsedTime($inclusive);

  /**
   * Gives a list of default values for variables for this task.
   *
   * Set the ::configuration property to a keyed array.
   *
   * Keys are configuration options, with values. The keys defined here are the
   * options for the given build task.
   *
   * @return array
   *   An array of configuration that this build task can accept. Used primarily
   *   to generate a build template for discoverability.
   */
  public function getDefaultConfiguration();

  /**
   * Returns the computed configuration array for this plugin
   *
   * @return array
   *   An array of configuration values this buildtask will use. Used primarily
   *   to generate a build template for repeatability.
   */
  public function getComputedConfiguration();

  /**
   * @return array
   *   This returns any child tasks as strings.
   */
  public function getChildTasks();

  /**
   * @param $buildTasks
   *
   *   Sets the subordinate Tasks on this Task
   */
  public function setChildTasks($buildTasks);

  /**
   * @param $errorLabel
   *   A short, < 50 character label describing the reason the build was
   *   terminated. This is what should display in the UI.
   * @param $errorDetails
   *   Comprehensive details/error messages for why the build failed.
   *
   * If a buildTask reaches a point in execution where it should not proceed,
   * It can terminate the build which will thrown an exception. The message
   * should be < 50 characters,
   */
  public function terminateBuild($errorLabel, $errorDetails);

  /**
   * Allow a fail to terminate a build.
   *
   * This represents a failed build, rather than one that has an error.
   *
   * @param $errorLabel
   *   A short, < 50 character label describing the reason the build was
   *   terminated. This is what should display in the UI.
   * @param $errorDetails
   *   Comprehensive details/error messages for why the build failed.
   */
  public function terminateBuildWithFail($errorLabel, $errorDetails);

  /* TODO: each task should be able to define their own command line switches
   * that override config like the environment variables do.
   * public function getCLIHelp();
   *
   * TODO: each task should be able to display their configurable values and
   * we should use that to help with discovery or something.
   * public function getConfigurables();
   */

}
