<?php

namespace DrupalCI\Tests\Build\Environment;

use Docker\API\Model\ExecCreateResult;
use Docker\API\Model\ExecStartConfig;
use Docker\API\Model\ExecCommand;
use Docker\Docker;
use Docker\Manager\ExecManager;
use Docker\Stream\DockerRawStream;
use DrupalCI\Build\BuildInterface;
use DrupalCI\Build\Environment\Environment;
use DrupalCI\Build\Environment\EnvironmentInterface;
use DrupalCI\Tests\DrupalCITestCase;

/**
 * @coversDefaultClass DrupalCI\Build\Environment\Environment
 */
class EnvironmentTest extends DrupalCITestCase {

  /**
   * @covers ::executeCommands
   */
  public function testExecuteCommands() {
    $manager_id = 'abcdef';
    $cmd = 'test_command test_argument';

    $docker = $this->getMockBuilder(Docker::class)
      ->setMethods(['getExecManager'])
      ->getMock();

    $exec_manager = $this->getMockBuilder(ExecManager::class)
      ->disableOriginalConstructor()
      ->setMethods(['create', 'start', 'find'])
      ->getMock();

    $docker->expects($this->once())
      ->method('getExecManager')
      ->will($this->returnValue($exec_manager));

    $exec_result = $this->getMock(ExecCreateResult::class);

    $exec_manager->expects($this->once())
      ->method('create')
      ->will($this->returnValue($exec_result));
    $exec_result->expects($this->once())
      ->method('getId')
      ->willReturn($manager_id);

    $exec_start_config = $this->getMock(ExecStartConfig::class);

    $stream = $this->getMockBuilder(DockerRawStream::class)
      ->disableOriginalConstructor()
      ->getMock();

    $exec_manager->expects($this->once())
      ->method('start')
      ->with($manager_id)
      ->will($this->returnValue($stream));

    $exec_command = $this->getMockBuilder(ExecCommand::class)
      ->setMethods(['getExitCode'])
      ->getMock();
    $exec_command->expects($this->once())
      ->method('getExitCode');
    $exec_manager->expects($this->once())
      ->method('find')
      ->will($this->returnValue($exec_command));

    $environment = $this->getMockBuilder(Environment::class)
      ->setMethods(['getExecContainer'])
      ->getMockForAbstractClass();

    $environment->expects($this->once())
      ->method('getExecContainer')
      ->will($this->returnValue(['id' => 'drupalci/php-5.4']));

    $environment->inject($this->getContainer(['docker' => $docker]));
    $environment->executeCommands([$cmd]);
  }

}
