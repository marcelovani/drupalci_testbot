<?php

namespace DrupalCI\Tests\Plugin\BuildTask\BuildPhase;

use DrupalCI\Console\DrupalCIStyle;
use DrupalCI\Plugin\BuildTask\BuildStep\Utility\Command;
use DrupalCI\Tests\DrupalCITestCase;

/**
 * @coversDefaultClass \DrupalCI\Plugin\BuildTask\BuildPhase\PreAssessmentPhase
 */
class PreAssessmentPhaseTest extends DrupalCITestCase {

  protected function getPlugin($configuration = [], $services = []) {
    $plugin_factory = $this->getContainer($services)['plugin.manager.factory']->create('BuildTask');
    return $plugin_factory->getPlugin('BuildStep', 'host_command', $configuration);
  }

  public function testGetPlugin() {
    $this->assertEquals(Command::class, get_class($this->getPlugin()));
  }

  public function provideValidEnvironments() {
    return [
      ['host'],
      ['php-container'],
    ];
  }

  /**
   * @covers ::checkExecutionEnvironment
   * @dataProvider provideValidEnvironments
   */
  public function testCheckExecutionEnvironmentValid($environment) {
    $this->markTestSkipped('Needs updating to Command/Container_Command');
    $io = $this->getMockBuilder(DrupalCIStyle::class)
      ->disableOriginalConstructor()
      ->setMethods(['writeln'])
      ->getMock();
    $io->expects($this->once())
      ->method('writeln')
      ->with($this->equalTo('Using execution environment: ' . $environment));

    $plugin = $this->getPlugin(
      [
        'halt-on-failure' => TRUE,
        'execution-environment' => $environment,
      ],
      [
        'console.io' => $io,
      ]
    );

    $ref_check = new \ReflectionMethod($plugin, 'checkExecutionEnvironment');
    $ref_check->setAccessible(TRUE);
    $ref_check->invoke($plugin);
  }

  public function provideTestRun() {
    return [
      // environment, calls to executeOnHost(), calls to executeOnPhpContainer()
      ['host', 1, 0],
      ['php-container', 0, 1],
      ['bad-host', 0, 0],
    ];
  }

  /**
   * @covers ::run
   * @dataProvider provideTestRun
   */
  public function testRun($environment, $calls_to_host, $calls_to_php) {
    $this->markTestSkipped('Needs updating to Command/Container_Command');
    $plugin = $this->getMockBuilder(PreAssessmentPhase::class)
      ->disableOriginalConstructor()
      ->setMethods(['executeOnHost', 'executeOnPhpContainer'])
      ->getMock();
    $plugin->inject($this->getContainer());

    $ref_config = new \ReflectionProperty($plugin, 'configuration');
    $ref_config->setAccessible(TRUE);
    $ref_config->setValue($plugin, [
      'commands' => ['foo'],
      'execution-environment' => $environment,
      'halt-on-failure' => FALSE,
    ]);

    $plugin->expects($this->exactly($calls_to_host))
      ->method('executeOnHost');
    $plugin->expects($this->exactly($calls_to_php))
      ->method('executeOnPhpContainer');

    $this->assertEquals(0, $plugin->run());
  }

}
