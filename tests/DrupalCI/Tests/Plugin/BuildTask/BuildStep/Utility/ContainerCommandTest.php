<?php

namespace DrupalCI\Tests\Plugin\BuildTask\BuildStep\Utility;

use DrupalCI\Tests\DrupalCITestCase;
use DrupalCI\Plugin\BuildTask\BuildStep\Utility\ContainerCommand;

/**
 * Exercise the container_command task.
 *
 * @group Utility
 * @coversDefaultClass \DrupalCI\Plugin\BuildTask\BuildStep\Utility\ContainerCommand
 */
class ContainerCommandTest extends DrupalCITestCase {

  public function provideHaltOnFail() {
    return [
      [TRUE],
      [FALSE]
    ];
  }

  /**
   * For unit testing, we only really care about halt-on-fail.
   *
   * @dataProvider provideHaltOnFail
   */
  public function testHaltOnFail($halt_on_fail) {
    // Set up the mock with our halt-on-fail config.
    $container_command = $this->getMockBuilder(ContainerCommand::class)
      ->setConstructorArgs([[
        'halt-on-fail' => $halt_on_fail,
        'commands' => [],
      ]])
      ->setMethods(['execEnvironmentCommands', 'execRequiredEnvironmentCommands'])
      ->getMock();
    $container_command->inject($this->getContainer());

    // Make sure we call the appropriate method given halt-on-fail config.
    $container_command->expects($halt_on_fail ? $this->never() : $this->once())
      ->method('execEnvironmentCommands');
    $container_command->expects($halt_on_fail ? $this->once() : $this->never())
      ->method('execRequiredEnvironmentCommands');

    $ref_execute = new \ReflectionMethod($container_command, 'execute');
    $ref_execute->setAccessible(TRUE);

    $ref_execute->invokeArgs($container_command, [['test -f foo.txt'], $halt_on_fail]);
  }

}
