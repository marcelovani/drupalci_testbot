<?php

namespace DrupalCI\Tests\Plugin\BuildSteps\setup;

use DrupalCI\Build\Environment\CommandResult;

trait TestSetupBaseTrait {

  protected $validate;

  protected $commands = [];

  protected $execResult;

  function validateDirectory($firstdir, $dir) {
    return $this->validate;
  }

  function setValidate($validate) {
    $this->validate = $validate;
  }

  function execCommands($command, $save_output = TRUE) {
    $this->commands[] = $command;
    $output = [];
    $result = $this->execResult;
    return new CommandResult();
  }
  function execRequiredCommands($command, $failure_message, $save_output = TRUE) {
    $this->commands[] = $command;
    $output = [];
    $result = $this->execResult;
    return new CommandResult();
  }

  function getCommands() {
    return $this->commands;
  }

  function setExecResult($exec_result) {
    $this->execResult = $exec_result;
  }

}
