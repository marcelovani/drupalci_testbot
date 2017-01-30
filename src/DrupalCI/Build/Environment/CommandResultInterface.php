<?php

namespace DrupalCI\Build\Environment;

interface CommandResultInterface {

  /**
   * @return int
   *
   * Returns the signal for this container execution. 0 is success.
   * 1 can be test failures, or other fails, 2 can be exceptions for testing
   * apps, or some other failure, and > 2 always indicates some kind of
   * execution failure.
   */
  public function getSignal();

  /**
   * @return string
   *
   * Gives the Output of all the commands executed. Each command printed,
   * prefaced with "EXECUTING:" followed by the stdout of that command.
   */
  public function getOutput();

  /**
   * @return string
   *
   * Gives the StdErr of all the commands executed. Each command printed,
   * prefaced with "EXECUTING:" followed by the stderr of that command.
   */
  public function getError();

  /**
   * @param int
   *
   * Sets the signal for this container execution.
   */
  public function setSignal($signal);

  /**
   * @param $string
   *
   * Adds to the output for an existing command execution.
   */
  public function appendOutput($string);

  /**
   * @param string
   *
   * Adds to the error for an existing command execution.
   */
  public function appendError($string);

}
