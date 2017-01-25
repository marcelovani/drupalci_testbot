<?php

namespace DrupalCI\Tests\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Build\Codebase\CodebaseInterface;
use DrupalCI\Tests\DrupalCITestCase;
use DrupalCI\Plugin\BuildTask\BuildStep\CodebaseValidate\Phpcs;

/**
 * @group phpcs
 * @coversDefaultClass \DrupalCI\Plugin\BuildTask\BuildStep\CodebaseValidate\Phpcs
 */
class PhpcsTest extends DrupalCITestCase {

  /**
   * Get a phpcs plugin from the factory.
   *
   * @param array $configuration
   *   Configuration to pass to the phpcs object.
   *
   * @return \DrupalCI\Plugin\BuildTask\BuildStep\CodebaseValidate\Phpcs
   */
  protected function getPhpcsPlugin($configuration = []) {
    $plugin_factory = $this->getContainer()['plugin.manager.factory']->create('BuildTask');
    return $plugin_factory->getPlugin('BuildStep', 'phpcs', $configuration);
  }

  /**
   *
   * @return bool[]
   * Outcomes:
   *   - Whether we should install generic Coder.
   *   - Whether we should sniff only changed.
   * Circumstances:
   *   - Config sniff_only_changed.
   *   - Whether phpcs was already installed through Composer.
   *   - Whether phpcs.xml is present.
   *   - Whether phpcs.xml was modified. ('Modified' covers removed.)
   *   - Whether any files were modfied.
   */
  public function provideUseCase() {
    return [
      'phpcs_config_patch' =>
      [FALSE, TRUE, TRUE, TRUE, TRUE, FALSE, TRUE],
      'phpcs_no_config_patch' =>
      [FALSE, TRUE, TRUE, TRUE, FALSE, FALSE, TRUE],
      'config_patch' =>
      [TRUE, TRUE, TRUE, FALSE, TRUE, FALSE, TRUE],
      'no_config_patch' =>
      [TRUE, TRUE, TRUE, FALSE, FALSE, FALSE, TRUE],
      'patch_changes_config' =>
      [TRUE, FALSE, TRUE, FALSE, TRUE, TRUE, TRUE],
      'phcps_patch_changes_config' =>
      [FALSE, FALSE, TRUE, TRUE, TRUE, TRUE, TRUE],
      'patch_removes_config' =>
      [TRUE, TRUE, TRUE, FALSE, FALSE, TRUE, TRUE],
      'patch_adds_config' =>
      [TRUE, FALSE, TRUE, FALSE, TRUE, TRUE, TRUE],
      'phpcs_branch_with_config' =>
      [FALSE, FALSE, TRUE, TRUE, TRUE, FALSE, FALSE],
      'branch_with_config' =>
      [TRUE, FALSE, TRUE, FALSE, TRUE, FALSE, FALSE],
      'phpcs_branch_no_config' =>
      [FALSE, FALSE, TRUE, TRUE, FALSE, FALSE, FALSE],
      'branch_no_config' =>
      [TRUE, FALSE, TRUE, FALSE, FALSE, FALSE, FALSE],
    ];
  }

  /**
   * Test many use-cases for phpcs plugin.
   *
   * The e_ means expected.
   *
   * @dataProvider provideUseCase
   * @covers ::adjustForUseCase
   */
  public function testAdjustForUseCase (
    $e_should_install_generic,
    $e_sniff_only_changed,
    $sniff_only_changed,
    $phpcs_already_installed,
    $config_present,
    $config_modified,
    $files_were_modified
  ) {
    $artifact_directory = '/test/';

    $codebase = $this->getMockBuilder(CodebaseInterface::class)
      ->setMethods(['getProjectName', 'getModifiedPhpFiles'])
      ->getMockForAbstractClass();
    // Always check core for this test.
    $codebase->expects($this->once())
      ->method('getProjectName')
      ->willReturn('');
    // Generate some modified files if needed.
    $modified = [];
    if ($files_were_modified) {
      $modified = ['afile.txt', 'another.file'];
    }
    $codebase->expects($this->any())
      ->method('getModifiedPhpFiles')
      ->willReturn($modified);

    $build = $this->getMockBuilder(BuildInterface::class)
      ->setMethods(['getArtifactDirectory'])
      ->getMockForAbstractClass();
    $build->expects($this->any())
      ->method('getArtifactDirectory')
      ->willReturn($artifact_directory);

    $container = $this->getContainer([
      'codebase' => $codebase,
      'build' => $build,
    ]);

    $phpcs = $this->getMockBuilder(Phpcs::class)
      ->setMethods([
        'projectHasPhpcsConfig',
        'phpcsConfigFileIsModified',
        'getPhpcsExecutable',
        // We just mock writeSniffableFiles() so it does nothing.
        'writeSniffableFiles',
      ])
      ->setConstructorArgs([['sniff_only_changed' => $sniff_only_changed]])
      ->getMock();
    $phpcs->expects($this->once())
      ->method('projectHasPhpcsConfig')
      ->willReturn($config_present);
    $phpcs->expects($this->once())
      ->method('phpcsConfigFileIsModified')
      ->willReturn($config_modified);
    $get_phpcs_executable = $phpcs->expects($this->once())
      ->method('getPhpcsExecutable');
    if ($phpcs_already_installed) {
      $get_phpcs_executable->willReturn('/not/a/real/path/phpcs');
    }
    else {
      $get_phpcs_executable->willThrowException(new \RuntimeException());
    }

    // Use our mocked codebase and build.
    $phpcs->inject($container);

    // Run adjustForUseCase().
    $ref_adjust = new \ReflectionMethod($phpcs, 'adjustForUseCase');
    $ref_adjust->setAccessible(TRUE);
    $ref_adjust->invoke($phpcs);

    // Now we test for state.
    $ref_shouldInstallGenericCoder = new \ReflectionProperty($phpcs, 'shouldInstallGenericCoder');
    $ref_shouldInstallGenericCoder->setAccessible(TRUE);
    $this->assertEquals($e_should_install_generic, $ref_shouldInstallGenericCoder->getValue($phpcs));

    $ref_configuration = new \ReflectionProperty($phpcs, 'configuration');
    $ref_configuration->setAccessible(TRUE);
    $configuration = $ref_configuration->getValue($phpcs);
    $this->assertEquals($e_sniff_only_changed, $configuration['sniff_only_changed']);
  }

}
