<?php

namespace DrupalCI\Plugin\BuildTask;

use DrupalCI\Build\BuildResults;

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

  /**
   * @var string
   *
   * This is the message that will eventually display to the end user to give
   * an indication as to what sort of failure has occurred.  It should be short,
   * no more than 50 chars.
   */
  protected $exceptionLabel;

  /**
   * @var string
   * This is a comprehensive string that contains any information that may be
   * useful for the end user to understand why drupalCI failed.
   */
  protected $exceptionDetails;

  public function __construct($errorLabel, $exceptionDetails = '') {
    $this->exceptionLabel = $errorLabel;
    $this->exceptionDetails = $exceptionDetails;
    parent::__construct($errorLabel, 2);
  }

  public function getBuildResults() {
    return new BuildResults($this->exceptionLabel, $this->exceptionDetails);
  }

}
