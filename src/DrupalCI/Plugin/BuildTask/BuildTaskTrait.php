<?php

namespace DrupalCI\Plugin\BuildTask;

/**
 * @TODO: this should probably be rethought of as a Timer Trait that can be
 * used to time things, and not have the run/complete functions built in.
 */
trait BuildTaskTrait {

  /**
   * @var float
   */
  protected $startTime;

  /**
   * @var float
   *   Total time taken for this build task, including child tasks
   */
  protected $elapsedTime;

  /**
   * Decorator for run functions to allow all of them to be timed.
   *
   */
  public function start() {
    $this->startTime = microtime(TRUE);
    $this->io->writeLn("<info>----------------   Starting <options=bold>" . $this->pluginId . "</>   ----------------</info>");

    $this->setup();
    $statuscode = $this->run();
    if (!isset($statuscode)) {
      return 0;
    }
    else {
      return $statuscode;
    }
  }

  /**
   * Decorator for complete functions to stop their timer.
   *
   * @param $childStatus
   */
  public function finish($childStatus) {
    $this->complete($childStatus);
    $this->teardown();
    $elapsed_time = microtime(TRUE) - $this->startTime;
    $this->elapsedTime = $elapsed_time;
    $datetime = new \DateTime();
    $this->io->writeLn("<info>---------------- Finished <options=bold>" . $this->pluginId . "</> in " . $elapsed_time . " ---------------- </info>");

  }

  /**
   * @inheritDoc
   */
  public function getElapsedTime($inclusive = TRUE) {
    return $this->elapsedTime;
  }

}
