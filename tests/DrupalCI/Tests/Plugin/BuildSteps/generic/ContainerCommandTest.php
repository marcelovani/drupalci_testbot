<?php

namespace DrupalCI\Tests\Plugin\BuildSteps\generic;

use Docker\API\Model\ExecCreateResult;
use Docker\API\Model\ExecStartConfig;
use Docker\API\Model\ExecCommand;
use Docker\Docker;
use Docker\Manager\ExecManager;
use Docker\Stream\DockerRawStream;
use DrupalCI\Plugin\BuildSteps\generic\ContainerCommand;
use DrupalCI\Build\BuildInterface;
use DrupalCI\Tests\DrupalCITestCase;

/**
 * @coversDefaultClass DrupalCI\Plugin\BuildSteps\generic\ContainerCommand
 */
class ContainerCommandTest extends DrupalCITestCase {

  /**
   * @covers ::run
   */
  public function testRun() {
    $manager_id = 'abcdef';
    $cmd = 'test_command test_argument';

    $docker = $this->getMock(Docker::class);

    $job = $this->getMockBuilder(BuildInterface::class)
      ->getMockForAbstractClass();
    $job->expects($this->once())
      ->method('getDocker')
      ->will($this->returnValue($docker));
    $job->expects($this->once())
      ->method('getExecContainers')
      ->will($this->returnValue(['php' => [['id' => 'drupalci/php-5.4']]]));

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

    $command = new ContainerCommand();
    $command->run($job, $cmd);
  }

}
