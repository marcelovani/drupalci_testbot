<?php

namespace DrupalCI\Build\Environment;

class CommandResult implements CommandResultInterface {

  /**
   * @var int
   */
  protected $signal = 0;

  /**
   * @var string
   */
  protected $output = '';

  /**
   * @var string
   */
  protected $error = '';

  /**
   * @return int
   */
  public function getSignal() {
    return $this->signal;
  }

  /**
   * @param int $signal
   */
  public function setSignal($signal) {
    $this->signal = $signal;
  }

  /**
   * @return string
   */
  public function getOutput() {
    return $this->output;
  }

  /**
   * @param string $output
   */
  public function appendOutput($output) {
    $this->output = $this->output . "\n" . $output;
  }

  /**
   * @return string
   */
  public function getError() {
    return $this->error;
  }

  /**
   * @param string $error
   */
  public function appendError($error) {
    $this->error = $this->error . "\n" . $error;
  }

}
