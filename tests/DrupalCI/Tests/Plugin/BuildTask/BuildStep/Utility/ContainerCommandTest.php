<?php

namespace DrupalCI\Tests\Plugin\BuildTask\BuildStep\Utility;

use DrupalCI\Plugin\BuildTask\BuildStep\Utility\ContainerCommand;
use DrupalCI\Tests\DrupalCITestCase;

/**
 * @group Plugin
 * @coversDefaultClass \DrupalCI\Plugin\BuildTask\BuildStep\Utility\ContainerCommand
 */
class ContainerCommandTest extends DrupalCITestCase {

  public function testGetPlugin() {
    $plugin_factory = $this->getContainer()['plugin.manager.factory']->create('BuildTask');
    $this->assertEquals(
      ContainerCommand::class,
      get_class($plugin_factory->getPlugin('BuildStep', 'container_command'))
    );
  }

  /**
   * @covers ::execute
   */
  public function testExecute() {
    foreach ([TRUE, FALSE] as $halt_on_fail) {
      $plugin = $this->getMockBuilder(ContainerCommand::class)
        ->disableOriginalConstructor()
        ->setMethods(['execRequiredEnvironmentCommands', 'execEnvironmentCommands'])
        ->getMock();
      $plugin->inject($this->getContainer());

      // If $commands is empty, we never get to execute().
      $calls_to_execute = empty($commands) ? 0 : 1;

      // 'halt-on-fail' tells us whether to require the exec or not.
      $plugin->expects($this->exactly($halt_on_fail ? 1 : 0))
        ->method('execRequiredEnvironmentCommands');
      $plugin->expects($this->exactly($halt_on_fail ? 0 : 1))
        ->method('execEnvironmentCommands');

      $ref_execute = new \ReflectionMethod($plugin, 'execute');
      $ref_execute->setAccessible(TRUE);

      $this->assertEquals(0, $ref_execute->invokeArgs($plugin, [['command'], $halt_on_fail]));
    }
  }

}
