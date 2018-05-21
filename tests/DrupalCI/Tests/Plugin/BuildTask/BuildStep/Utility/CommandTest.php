<?php

namespace DrupalCI\Tests\Plugin\BuildTask\BuildStep\Utility;

use DrupalCI\Tests\DrupalCITestCase;
use DrupalCI\Plugin\BuildTask\BuildStep\Utility\Command;

/**
 * Exercise the host_command task.
 *
 * @group Utility
 * @coversDefaultClass \DrupalCI\Plugin\BuildTask\BuildStep\Utility\Command
 */
class CommandTest extends DrupalCITestCase {

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
    $host_command = $this->getMockBuilder(Command::class)
      ->setConstructorArgs([[
        'halt-on-fail' => $halt_on_fail,
        'commands' => [],
      ]])
      ->setMethods(['execCommands', 'execRequiredCommands'])
      ->getMock();
    $host_command->inject($this->getContainer());

    // Make sure we call the appropriate method given halt-on-fail config.
    $host_command->expects($halt_on_fail ? $this->never() : $this->once())
      ->method('execCommands');
    $host_command->expects($halt_on_fail ? $this->once() : $this->never())
      ->method('execRequiredCommands');

    $ref_execute = new \ReflectionMethod($host_command, 'execute');
    $ref_execute->setAccessible(TRUE);

    $ref_execute->invokeArgs($host_command, [['test -f foo.txt'], $halt_on_fail]);
  }

}
