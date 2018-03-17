<?php

namespace DrupalCI\Tests\Plugin\BuildTask\BuildStep\Testing;

use DrupalCI\Build\Codebase\CodebaseInterface;
use DrupalCI\Build\Environment\CommandResultInterface;
use DrupalCI\Build\Environment\EnvironmentInterface;
use DrupalCI\Tests\DrupalCITestCase;
use DrupalCI\Plugin\BuildTask\BuildStep\Testing\Simpletest;

/**
 * @group Simpletest
 * @coversDefaultClass \DrupalCI\Plugin\BuildTask\BuildStep\Testing\Simpletest
 */
class SimpletestTest extends DrupalCITestCase {

  public function providerGetRunTestsCommand() {
    return [
      'core' => [
        'cd exec-container-source-dir && sudo MINK_DRIVER_ARGS_WEBDRIVER=\'["chrome", {"browserName":"chrome","chromeOptions":{"args":["--disable-gpu","--headless"]}}, "http://chromecontainer-host:9515"]\' -u www-data php exec-container-source-dir/core/scripts/run-tests.sh --color --keep-results --values=value --all',
        'core',
      ],
      'contrib-default' => [
        'cd exec-container-source-dir && sudo MINK_DRIVER_ARGS_WEBDRIVER=\'["chrome", {"browserName":"chrome","chromeOptions":{"args":["--disable-gpu","--headless"]}}, "http://chromecontainer-host:9515"]\' -u www-data php exec-container-source-dir/core/scripts/run-tests.sh --color --keep-results --suppress-deprecations --values=value --directory true-extension-subdirectory',
        'contrib',
      ],
    ];
  }

  /**
   * @dataProvider providerGetRunTestsCommand
   * @covers ::getRunTestsCommand
   */
  public function testGetRunTestsCommand($expected, $configuration) {
    $command_result = $this->getMockBuilder(CommandResultInterface::class)
      ->setMethods([
        'getSignal',
      ])
      ->getMockForAbstractClass();
    $command_result->expects($this->any())
      ->method('getSignal')
      ->willReturn(0);

    $environment = $this->getMockBuilder(EnvironmentInterface::class)
      ->setMethods([
        'getExecContainerSourceDir',
        'executeCommands',
        'getChromeContainerHostname'
      ])
      ->getMockForAbstractClass();
    $environment->expects($this->any())
      ->method('getExecContainerSourceDir')
      ->willReturn('exec-container-source-dir');
    $environment->expects($this->any())
      ->method('getChromeContainerHostname')
      ->willReturn('chromecontainer-host');
    $environment->expects($this->any())
      ->method('executeCommands')
      ->willReturn($command_result);

    $codebase = $this->getMockBuilder(CodebaseInterface::class)
      ->setMethods(['getTrueExtensionSubDirectory'])
      ->getMockForAbstractClass();
    // Always check core for this test.
    $codebase->expects($this->any())
      ->method('getTrueExtensionSubDirectory')
      ->willReturn('true-extension-subdirectory');
    $codebase->expects($this->any())
      ->method('getProjectType')
      ->willReturn($configuration);

    $container = $this->getContainer([
      'environment' => $environment,
      'codebase' => $codebase,
    ]);

    $simpletest = $this->getMockBuilder(Simpletest::class)
      ->setMethods([
        'getRunTestsValues',
      ])
      ->getMock();
    $simpletest->expects($this->once())
      ->method('getRunTestsValues')
      ->willReturn('--values=value');

    // Use our mocked services.
    $simpletest->inject($container);


    // Run getRunTestsCommand().
    $ref_get_run_tests_command = new \ReflectionMethod($simpletest, 'getRunTestsCommand');
    $ref_get_run_tests_command->setAccessible(TRUE);
    $command = $ref_get_run_tests_command->invoke($simpletest);
    $this->assertEquals($expected, $command);
  }

}
