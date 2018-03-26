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
    $plugin = $plugin_factory->getPlugin('BuildStep', 'update_build');

    $ref_should = new \ReflectionMethod($plugin, 'shouldReplaceAssessmentStage');
    $ref_should->setAccessible(TRUE);

    $this->assertEquals($expected, $ref_should->invoke($plugin));
  }

  /**
   * All the documented return values for CodebaseInterface::getProjectType().
   */
  public function provideLocateDrupalCiYmlFile() {
    return [
      'core' => ['core/drupalci.yml', 'core'],
      'module' => ['contrib/drupalci.yml', 'module'],
      'theme' => ['contrib/drupalci.yml', 'theme'],
      'distribution' => ['contrib/drupalci.yml', 'distribution'],
      'library' => ['contrib/drupalci.yml', 'library'],
    ];
  }

  /**
   * @covers ::locateDrupalCiYmlFile
   * @dataProvider provideLocateDrupalCiYmlFile
   */
  public function testLocateDrupalCiYmlFile($expected, $project_type) {
    $codebase = $this->getMockBuilder(CodebaseInterface::class)
      ->setMethods(['getProjectType', 'getTrueExtensionSubDirectory'])
      ->getMockForAbstractClass();
    $codebase->expects($this->once())
      ->method('getProjectType')
      ->willReturn($project_type);
    $codebase->expects($this->any())
      ->method('getTrueExtensionSubDirectory')
      ->willReturn('contrib');

    $container = $this->getContainer(['codebase' => $codebase]);
    $plugin_factory = $container['plugin.manager.factory']->create('BuildTask');
    $plugin = $plugin_factory->getPlugin('BuildStep', 'update_build', []);

    $ref_locate = new \ReflectionMethod($plugin, 'locateDrupalCiYmlFile');
    $ref_locate->setAccessible(TRUE);

    $this->assertEquals($expected, $ref_locate->invoke($plugin));
  }

}
