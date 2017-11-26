<?php

namespace DrupalCI\Tests\Plugin\BuildTask\BuildStep\Testing;

use DrupalCI\Build\Codebase\CodebaseInterface;
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
        'cd exec-container-source-dir && sudo -u www-data php exec-container-source-dir/core/scripts/run-tests.sh --flag-value --values=value --all',
        [],
      ],
      'contrib' => [
        'cd exec-container-source-dir && sudo -u www-data php exec-container-source-dir/core/scripts/run-tests.sh --flag-value --values=value --directory true-extension-subdirectory',
        ['extension_test' => TRUE],
      ],
      'core-8.4.x' => [
        'cd exec-container-source-dir && sudo -u www-data php exec-container-source-dir/core/scripts/run-tests.sh --flag-value --values=value --directory true-extension-subdirectory',
        ['extension_test' => TRUE, 'core_branch' => '8.4.x'],
      ],
      'core-8.5.x' => [
        'cd exec-container-source-dir && sudo -u www-data php exec-container-source-dir/core/scripts/run-tests.sh --flag-value --values=value --directory true-extension-subdirectory --suppress-deprecations',
        ['extension_test' => TRUE, 'core_branch' => '8.5.x'],
      ],
    ];
  }

  /**
   * @dataProvider providerGetRunTestsCommand
   * @covers ::getRunTestsCommand
   */
  public function testGetRunTestsCommand($expected, $configuration) {
    $environment = $this->getMockBuilder(EnvironmentInterface::class)
      ->setMethods([
        'getExecContainerSourceDir'
      ])
      ->getMockForAbstractClass();
    $environment->expects($this->any())
      ->method('getExecContainerSourceDir')
      ->willReturn('exec-container-source-dir');

    $codebase = $this->getMockBuilder(CodebaseInterface::class)
      ->setMethods(['getTrueExtensionSubDirectory'])
      ->getMockForAbstractClass();
    // Always check core for this test.
    $codebase->expects($this->any())
      ->method('getTrueExtensionSubDirectory')
      ->willReturn('true-extension-subdirectory');

    $container = $this->getContainer([
      'environment' => $environment,
      'codebase' => $codebase,
    ]);

    $simpletest = $this->getMockBuilder(Simpletest::class)
      ->setMethods([
        'getRunTestsFlagValues',
        'getRunTestsValues',
      ])
      ->getMock();
    $simpletest->expects($this->once())
      ->method('getRunTestsFlagValues')
      ->willReturn('--flag-value');
    $simpletest->expects($this->once())
      ->method('getRunTestsValues')
      ->willReturn('--values=value');

    // Use our mocked services.
    $simpletest->inject($container);

    // Set up config.
    $ref_configuration = new \ReflectionProperty($simpletest, 'configuration');
    $ref_configuration->setAccessible(TRUE);
    $ref_configuration->setValue(
      $simpletest,
      array_merge(
        $simpletest->getDefaultConfiguration(),
        $configuration
      )
    );

    // Run getRunTestsCommand().
    $ref_get_run_tests_command = new \ReflectionMethod($simpletest, 'getRunTestsCommand');
    $ref_get_run_tests_command->setAccessible(TRUE);
    $command = $ref_get_run_tests_command->invoke($simpletest);
    $this->assertEquals($expected, $command);
  }

}
