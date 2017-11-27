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
        'cd exec-container-source-dir && sudo -u www-data php exec-container-source-dir/core/scripts/run-tests.sh --color --keep-results --values=value --all',
        [],
      ],
      'contrib-default' => [
        'cd exec-container-source-dir && sudo -u www-data php exec-container-source-dir/core/scripts/run-tests.sh --color --keep-results --values=value --directory true-extension-subdirectory',
        ['extension_test' => TRUE],
      ],
      'contrib-8.4.x' => [
        'cd exec-container-source-dir && sudo -u www-data php exec-container-source-dir/core/scripts/run-tests.sh --color --keep-results --values=value --directory true-extension-subdirectory',
        ['extension_test' => TRUE, 'core_branch' => '8.4.x'],
      ],
      'contrib-8.5.x' => [
        'cd exec-container-source-dir && sudo -u www-data php exec-container-source-dir/core/scripts/run-tests.sh --color --keep-results --suppress-deprecations --values=value --directory true-extension-subdirectory',
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
        'getRunTestsValues',
      ])
      ->getMock();
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

  public function provideCanAdd() {
    return [
      [TRUE, '8.5.x'],
      [TRUE, '8.5.0'],
      [TRUE, '8.6.0'],
      [FALSE, '8.4.x'],
      [FALSE, '8.4.6'],
    ];
  }

  /**
   * @covers ::canAddSuppressDeprecations
   * @dataProvider provideCanAdd
   */
  public function testCanAddSuppressDeprecations($expected, $core_version) {
    $simpletest = new Simpletest();

    $configuration = array_merge(
      $simpletest->getDefaultConfiguration(),
      ['core_branch' => $core_version]
    );

    $ref_configuration = new \ReflectionProperty($simpletest, 'configuration');
    $ref_configuration->setAccessible(TRUE);
    $ref_configuration->setValue($simpletest, $configuration);

    $ref_can_add = new \ReflectionMethod($simpletest, 'canAddSuppressDeprecations');
    $ref_can_add->setAccessible(TRUE);
    $this->assertSame($expected, $ref_can_add->invoke($simpletest));
  }

}
