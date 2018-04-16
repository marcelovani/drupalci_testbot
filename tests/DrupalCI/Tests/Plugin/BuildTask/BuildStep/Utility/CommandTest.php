<?php

namespace DrupalCI\Tests\Plugin\BuildTask\BuildStep\Utility;

use DrupalCI\Console\DrupalCIStyle;
use DrupalCI\Plugin\BuildTask\BuildStep\Utility\Command;
use DrupalCI\Tests\DrupalCITestCase;

/**
 * @group Plugin
 * @coversDefaultClass \DrupalCI\Plugin\BuildTask\BuildStep\Utility\Command
 */
class CommandTest extends DrupalCITestCase {

  protected function getPlugin($configuration = [], $services = []) {
    $plugin_factory = $this->getContainer($services)['plugin.manager.factory']->create('BuildTask');
    return $plugin_factory->getPlugin('BuildStep', 'host_command', $configuration);
  }

  public function testGetPlugin() {
    $this->assertEquals(Command::class, get_class($this->getPlugin()));
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

}
