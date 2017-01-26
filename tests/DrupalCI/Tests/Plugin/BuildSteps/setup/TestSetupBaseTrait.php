<?php

/**
 * @file
 * Contains \DrupalCI\Tests\Plugin\BuildSteps\setup\TestSetupBase.
 */

namespace DrupalCI\Tests\Plugin\BuildSteps\setup;

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

  function exec($command, &$output, &$result) {
    $this->commands[] = $command;
    $output = [];
    $result = $this->execResult;
  }

  function getCommands() {
    return $this->commands;
  }

  function setExecResult($exec_result) {
    $this->execResult = $exec_result;
  }

}
