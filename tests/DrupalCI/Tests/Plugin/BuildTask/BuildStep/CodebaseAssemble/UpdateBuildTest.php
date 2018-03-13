<?php

namespace DrupalCI\Tests\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Build\Codebase\CodebaseInterface;
use DrupalCI\Tests\DrupalCITestCase;

/**
 * @coversDefaultClass \DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble\UpdateBuild
 */
class UpdateBuildTest extends DrupalCITestCase {

  /**
   * Test that plugin is discoverable and can instantiate, and returns 0 for
   * no changed files.
   *
   * @covers ::run
   */
  public function testRun() {
    $plugin_factory = $this->getContainer()['plugin.manager.factory']->create('BuildTask');
    $plugin = $plugin_factory->getPlugin('BuildStep', 'update_build', []);
    $this->assertSame(0, $plugin->run());
  }

  public function provideShouldReplace() {
    return [
      // [expected outcome, path to drupalci.yml, is drupalci.yml modified?]
      'modified-drupalciyml' => [TRUE, 'core/drupalci.yml', TRUE],
      'unmodified-drupalciyml' => [FALSE, 'core/drupalci.yml', FALSE],
      'no-drupalciyml' => [FALSE, '', FALSE],
    ];
  }

  /**
   * @covers ::shouldReplaceAssessmentStage
   * @dataProvider provideShouldReplace
   */
  public function testShouldReplaceAssessmentStage($expected, $drupalci_yml_path, $modified) {
    $codebase = $this->getMockBuilder(CodebaseInterface::class)
      ->setMethods(['getModifiedFiles', 'getProjectType'])
      ->getMockForAbstractClass();
    $modified_files = [];
    if ($modified) {
      $modified_files = [$drupalci_yml_path];
    }
    $codebase->expects($this->once())
      ->method('getModifiedFiles')
      ->willReturn($modified_files);
    $codebase->expects($this->once())
      ->method('getProjectType')
      ->willReturn('core');

    $container = $this->getContainer(['codebase' => $codebase]);
    $plugin_factory = $container['plugin.manager.factory']->create('BuildTask');
    // Ensure that always-use-drupalci-yml is FALSE.
    $plugin = $plugin_factory->getPlugin('BuildStep', 'update_build', ['always-use-drupalci-yml' => FALSE]);

    $ref_should = new \ReflectionMethod($plugin, 'shouldReplaceAssessmentStage');
    $ref_should->setAccessible(TRUE);

    $this->assertEquals($expected, $ref_should->invoke($plugin));
  }

  /**
   * Test behavior of always-use-drupalci-yml config.
   *
   * @covers ::shouldReplaceAssessmentStage
   */
  public function testShouldReplaceAssessmentStageConfig() {
    foreach ([TRUE, FALSE] as $config) {
      // Codebase has no modified files, so we should never need to replace
      // assessment stage.
      $codebase = $this->getMockBuilder(CodebaseInterface::class)
        ->setMethods(['getModifiedFiles'])
        ->getMockForAbstractClass();
      $codebase->expects($this->any())
        ->method('getModifiedFiles')
        ->willReturn([]);
      $container = $this->getContainer(['codebase' => $codebase]);
      $plugin_factory = $container['plugin.manager.factory']->create('BuildTask');

      // Create a plugin using our config.
      $plugin = $plugin_factory->getPlugin('BuildStep', 'update_build', ['always-use-drupalci-yml' => $config]);

      $ref_should = new \ReflectionMethod($plugin, 'shouldReplaceAssessmentStage');
      $ref_should->setAccessible(TRUE);

      $this->assertSame($config, $ref_should->invoke($plugin));
    }
  }

  /**
   * All the documented return values for CodebaseInterface::getProjectType().
   */
  public function provideLocateDrupalCiYmlFile() {
    return [
      'core' => ['core/drupalci.yml', 'core'],
      'module' => ['drupalci.yml', 'module'],
      'theme' => ['drupalci.yml', 'theme'],
      'distribution' => ['drupalci.yml', 'distribution'],
      'library' => ['drupalci.yml', 'library'],
    ];
  }

  /**
   * @covers ::locateDrupalCiYmlFile
   * @dataProvider provideLocateDrupalCiYmlFile
   */
  public function testLocateDrupalCiYmlFile($expected, $project_type) {
    $codebase = $this->getMockBuilder(CodebaseInterface::class)
      ->setMethods(['getProjectType'])
      ->getMockForAbstractClass();
    $codebase->expects($this->once())
      ->method('getProjectType')
      ->willReturn($project_type);

    $container = $this->getContainer(['codebase' => $codebase]);
    $plugin_factory = $container['plugin.manager.factory']->create('BuildTask');
    $plugin = $plugin_factory->getPlugin('BuildStep', 'update_build', []);

    $ref_locate = new \ReflectionMethod($plugin, 'locateDrupalCiYmlFile');
    $ref_locate->setAccessible(TRUE);

    $this->assertEquals($expected, $ref_locate->invoke($plugin));
  }

}
