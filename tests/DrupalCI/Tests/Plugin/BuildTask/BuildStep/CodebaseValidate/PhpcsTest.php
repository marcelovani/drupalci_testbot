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
      [FALSE, TRUE, TRUE, FALSE ],
      'phpcs_no_config_patch' =>
      [FALSE, TRUE, FALSE, FALSE ],
      'config_patch' =>
      [TRUE, FALSE, TRUE, FALSE ],
      'no_config_patch' =>
      [TRUE, FALSE, FALSE, FALSE ],
      'patch_changes_config' =>
      [TRUE, FALSE, TRUE, TRUE ],
      'phcps_patch_changes_config' =>
      [FALSE, TRUE, TRUE, TRUE ],
      'patch_removes_config' =>
      [TRUE, FALSE, FALSE, TRUE ],
      'patch_adds_config' =>
      [TRUE, FALSE, TRUE, TRUE ],
      'phpcs_branch_with_config' =>
      [FALSE, TRUE, TRUE, FALSE],
      'branch_with_config' =>
      [TRUE, FALSE, TRUE, FALSE],
      'phpcs_branch_no_config' =>
      [FALSE, TRUE, FALSE, FALSE],
      'branch_no_config' =>
      [TRUE, FALSE, FALSE, FALSE],
    ];
  }

  /**
   * Test many use-cases for phpcs plugin.
   *
   * The e_ means expected.
   *
   * @dataProvider provideUseCase
   * @covers ::adjustForUseCase
   *
   * @param $e_should_install_generic
   * @param $phpcs_already_installed
   * @param $config_present
   * @param $config_modified
   */
  public function testAdjustForUseCase(
    $e_should_install_generic,
    $phpcs_already_installed,
    $config_present,
    $config_modified ) {
    $artifact_directory = '/test/';

    $codebase = $this->getMockBuilder(CodebaseInterface::class)
      ->setMethods(['getProjectName'])
      ->getMockForAbstractClass();
    // Always check core for this test.
    $codebase->expects($this->once())
      ->method('getProjectName')
      ->willReturn('');

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
      ->getMock();
    $phpcs->expects($this->once())
      ->method('projectHasPhpcsConfig')
      ->willReturn($config_present);

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

  }

  /**
   *
   * @return bool[]
   * Outcomes:
   *   - Whether we should sniff all, none, or specific files.
   * Circumstances:
   *   - Config sniff_all_files.
   *   - Modified Files
   *   - Modified PHP Files
   */
  public function provideSniffScenarios() {
    return [
      'sniff_all_files' =>
        ['all', TRUE, [], []],
      'sniff_all_files_modified' =>
        ['all', TRUE, ['index.php'], ['index.php']],
      'no_modified_files' =>
        ['all', FALSE, [], []],
      'phpcs_config_modified' =>
        ['all', FALSE, ['core/phpcs.xml'], []],
      'phpcs_config_modified2' =>
        ['all', FALSE, ['core/phpcs.xml.dist'], []],
      'phpcs_config_modified3' =>
        ['all', FALSE, ['core/phpcs.xml.dist'], ['index.php']],
      'no_modified_php_files' =>
        ['none', FALSE, ['README.md'], []],
      'modified_php_files' =>
        [['index.php'], FALSE, ['index.php'], ['index.php']],
      'multiple_php_files' =>
        [['index.php', 'run-tests.php'], FALSE, ['index.php', 'run-tests.php'], ['index.php', 'run-tests.php']],
      'modified_php_etc_files' =>
        [['index.php'], FALSE, ['index.php', 'README.md'], ['index.php']],
      'php_and_config' =>
        ['all', FALSE, ['index.php', 'README.md', 'core/phpcs.xml.dist'], ['index.php']],
    ];
  }

  /**
   * Test filesniff possibilities of the phpcs plugin.
   *
   * The e_ means expected.
   *
   * @dataProvider provideSniffScenarios
   * @covers ::getSniffableFiles
   *
   * @param $e_sniffable_outcome
   * @param $sniff_all_files
   * @param $modified_files
   * @param $modified_php_files
   */
  public function testGetSniffableFiles(
    $e_sniffable_outcome,
    $sniff_all_files,
    $modified_files,
    $modified_php_files

  ) {
    $artifact_directory = '/test/';

    $codebase = $this->getMockBuilder(CodebaseInterface::class)
      ->setMethods(['getModifiedFiles', 'getModifiedPhpFiles'])
      ->getMockForAbstractClass();
    $codebase->expects($this->any())
      ->method('getModifiedFiles')
      ->willReturn($modified_files);
    $codebase->expects($this->any())
      ->method('getModifiedPhpFiles')
      ->willReturn($modified_php_files);

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
        'getPhpcsExecutable',
        // We just mock writeSniffableFiles() so it does nothing.
        'writeSniffableFiles',
      ])
      ->setConstructorArgs([['sniff-all-files' => $sniff_all_files]])
      ->getMock();

    // Use our mocked codebase and build.
    $phpcs->inject($container);

    // Run getSniffableFiles().
    $ref_adjust = new \ReflectionMethod($phpcs, 'getSniffableFiles');
    $ref_adjust->setAccessible(TRUE);
    $result = $ref_adjust->invoke($phpcs);
    $this->assertEquals($e_sniffable_outcome, $result);

  }

}
