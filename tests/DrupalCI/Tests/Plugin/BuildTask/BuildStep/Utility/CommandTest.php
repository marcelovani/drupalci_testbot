<?php

namespace DrupalCI\Tests\Plugin\BuildTask\BuildStep\Utility;

use DrupalCI\Plugin\BuildTask\BuildStep\Utility\Command;
use DrupalCI\Tests\DrupalCITestCase;

/**
 * @group Plugin
 * @coversDefaultClass \DrupalCI\Plugin\BuildTask\BuildStep\Utility\Command
 */
class CommandTest extends DrupalCITestCase {

  public function testGetPlugin() {
    $plugin_factory = $this->getContainer()['plugin.manager.factory']->create('BuildTask');
    $this->assertEquals(
      Command::class,
      get_class($plugin_factory->getPlugin('BuildStep', 'host_command'))
    );
  }

  public function provideTestRun() {
    return [
      [[]],
      ['test -f something'],
      [['test -f something']],
    ];
  }

  /**
   * @covers ::run
   * @dataProvider provideTestRun
   */
  public function testRun($commands) {
    $plugin = $this->getMockBuilder(Command::class)
      ->disableOriginalConstructor()
      ->setMethods(['execute'])
      ->getMock();
    $plugin->inject($this->getContainer());

    $ref_config = new \ReflectionProperty($plugin, 'configuration');
    $ref_config->setAccessible(TRUE);
    $ref_config->setValue($plugin, [
      'commands' => $commands,
      'halt-on-fail' => FALSE,
    ]);

    // If $commands is empty, we never get to execute().
    $calls_to_execute = empty($commands) ? 0 : 1;

    $plugin->expects($this->exactly($calls_to_execute))
      ->method('execute');

    $this->assertEquals(0, $plugin->run());
  }

  /**
   * @covers ::execute
   */
  public function testExecute() {
    foreach ([TRUE, FALSE] as $halt_on_fail) {
      $plugin = $this->getMockBuilder(Command::class)
        ->disableOriginalConstructor()
        ->setMethods(['execRequiredCommands', 'execCommands'])
        ->getMock();
      $plugin->inject($this->getContainer());

      // 'halt-on-fail' tells us whether to require the exec or not.
      $plugin->expects($this->exactly($halt_on_fail ? 1 : 0))
        ->method('execRequiredCommands');
      $plugin->expects($this->exactly($halt_on_fail ? 0 : 1))
        ->method('execCommands');

      $ref_execute = new \ReflectionMethod($plugin, 'execute');
      $ref_execute->setAccessible(TRUE);

      $this->assertEquals(0, $ref_execute->invokeArgs($plugin, [['command'], $halt_on_fail]));
    }
  }

}
