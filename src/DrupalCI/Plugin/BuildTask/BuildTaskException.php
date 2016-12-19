<?php

namespace DrupalCI\Plugin\BuildTask;

/**
 * Class BuildTaskException
 *
 * BuildTasks may throw a BuildTaskException when the execution of a BuildTask
 * results in a state that should prevent the build from proceeding.
 *
 * @package DrupalCI\Plugin\BuildTask
 *
 * @see BuildTaskInterface
 */
class BuildTaskException extends \Exception {
  public function __construct($message) {
    parent::__construct($message, 2);
  }
}
