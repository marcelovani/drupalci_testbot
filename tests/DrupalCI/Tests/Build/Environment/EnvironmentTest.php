<?php

namespace DrupalCI\Tests\Build\Environment;

use DrupalCI\Build\Environment\CommandResultInterface;
use DrupalCI\Build\Environment\Environment;
use DrupalCI\Tests\DrupalCITestCase;

/**
 * @coversDefaultClass DrupalCI\Build\Environment\Environment
 */
class EnvironmentTest extends DrupalCITestCase {

  /**
   * Ensure we get a result when there are no commands.
   *
   * covers ::executeCommands
   */
  public function testExecuteCommandsNoCommands() {
    /* @var $environment \DrupalCI\Build\Environment\Environment */
    $environment = $this->getContainer()['environment'];
    $result = $environment->executeCommands([]);
    $this->assertInstanceOf(CommandResultInterface::class, $result);
    $this->assertEquals(0, $result->getSignal());
  }

  public function provideNoExistingContainer() {
    return [
      ['command'],
      [['array', 'of', 'commands']],
    ];
  }

  /**
   * No container? No problem!
   *
   * @dataProvider provideNoExistingContainer
   * @covers ::executeCommands
   */
  public function testExecuteCommandsNoExistingContainer($commands) {
    $environment = $this->getMockBuilder(Environment::class)
      ->disableOriginalConstructor()
      ->setMethods(['getExecContainer'])
      ->getMock();
    // getExecContainer() always returns no available container.
    $environment->expects($this->once())
      ->method('getExecContainer')
      ->willReturn([]);

    // Set up some services...
    $environment->inject($this->getContainer());

    // Container ID (second parameter to executeCommands()) must be empty.
    /* @var $result \DrupalCI\Build\Environment\CommandResultInterface */
    $result = $environment->executeCommands($commands, '');

    $this->assertInstanceOf(CommandResultInterface::class, $result);
    $this->assertEquals(1, $result->getSignal());
    $this->assertEquals(1, preg_match('/No existing container to run commands on./', $result->getOutput()));
    $this->assertEquals(1, preg_match('/No existing container to run commands on./', $result->getError()));
  }

}
