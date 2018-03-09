<?php

namespace DrupalCI\Tests\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Build\Codebase\CodebaseInterface;
use DrupalCI\Tests\DrupalCITestCase;
use org\bovigo\vfs\vfsStream;

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

  /**
   * @covers ::shouldReplaceAssessmentStage
   */
  public function testShouldReplaceAssessmentStage() {
    vfsStream::setup('locate', NULL, ['source_dir' => ['drupalci.yml' => 'yml: goodness']]);

    $codebase = $this->getMockBuilder(CodebaseInterface::class)
      ->setMethods(['getModifiedFiles'])
      ->getMockForAbstractClass();

    // Set up getSourceDirectory so locateDrupalCiYmlFile() can consume it.
    $codebase->expects($this->atMost(2))
      ->method('getSourceDirectory')
      ->willReturn(vfsStream::url('locate/source_dir'));

    // Set up getModifiedFiles() so shouldReplaceAssessmentStage() can consume
    // it. First run says drupalci.yml is modified, second says no
    // modifications.
    $codebase->expects($this->atMost(2))
      ->method('getModifiedFiles')
      ->willReturnOnConsecutiveCalls([vfsStream::url('locate/source_dir/drupalci.yml')], []);

    $container = $this->getContainer(['codebase' => $codebase]);
    $plugin_factory = $container['plugin.manager.factory']->create('BuildTask');
    // Ensure that always-use-drupalci-yml is FALSE.
    $plugin = $plugin_factory->getPlugin('BuildStep', 'update_build', ['always-use-drupalci-yml' => FALSE]);

    $ref_should = new \ReflectionMethod($plugin, 'shouldReplaceAssessmentStage');
    $ref_should->setAccessible(TRUE);

    $this->assertTrue($ref_should->invoke($plugin));
    $this->assertFalse($ref_should->invoke($plugin));
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
   * @covers ::locateDrupalCiYmlFile
   * @todo Make this test contrib.
   */
  public function testLocateDrupalCiYmlFile() {
    vfsStream::setup('locate', NULL, ['source_dir' => ['drupalci.yml' => 'yml: goodness']]);

    $codebase = $this->getMockBuilder(CodebaseInterface::class)
      ->setMethods(['getSourceDirectory'])
      ->getMockForAbstractClass();
    $codebase->expects($this->atMost(2))
      ->method('getSourceDirectory')
      ->willReturn(vfsStream::url('locate/source_dir'));

    $container = $this->getContainer(['codebase' => $codebase]);
    $plugin_factory = $container['plugin.manager.factory']->create('BuildTask');
    $plugin = $plugin_factory->getPlugin('BuildStep', 'update_build', []);

    $ref_locate = new \ReflectionMethod($plugin, 'locateDrupalCiYmlFile');
    $ref_locate->setAccessible(TRUE);

    $this->assertEquals('vfs://locate/source_dir/drupalci.yml', $ref_locate->invoke($plugin));
  }

}
