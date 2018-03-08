<?php

namespace DrupalCI\Tests\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Tests\DrupalCITestCase;
use DrupalCI\Build\Codebase\CodebaseInterface;

/**
 * @coversDefaultClass \DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble\Fetch
 */
class ReloadAssessmentTest extends DrupalCITestCase {

  /**
   * Get a fetch plugin from the factory.
   *
   * @param array $configuration
   *   Configuration to pass to the fetch object.
   *
   * @return \DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble\Fetch
   */
  protected function getFetchPlugin($configuration = []) {
    $plugin_factory = $this->getContainer()['plugin.manager.factory']->create('BuildTask');
    return $plugin_factory->getPlugin('BuildStep', 'reload_assessment', $configuration);
  }

  /**
   * Test that plugin is discoverable and can instantiate, and returns 0 for
   * no changed files.
   *
   * @covers ::run
   */
  public function testRun() {
    $this->assertSame(0, $this->getFetchPlugin([])->run());
  }

  /**
   * 
   */
  public function testShouldReplaceAssessmentPhase() {
    $this->markTestIncomplete('Does not yet mock the codebase to give a location for a changed drupalci.yml.');
    $codebase = $this->getMockBuilder(CodebaseInterface::class)
      ->setMethods(['getModifiedFiles'])
      ->getMockForAbstractClass();
    $codebase->expects($this->atMost(2))
      ->method('getModifiedFiles')
      ->willReturnOnConsecutiveCalls(['drupalci.yml'], []);

    $container = $this->getContainer(['codebase' => $codebase]);
    $plugin_factory = $container['plugin.manager.factory']->create('BuildTask');
    $plugin = $plugin_factory->getPlugin('BuildStep', 'reload_assessment', []);

    $ref_should = new \ReflectionMethod($plugin, 'shouldReplaceAssessmentStage');
    $ref_should->setAccessible(TRUE);

    $this->assertTrue($ref_should->invoke($plugin));
    $this->assertFalse($ref_should->invoke($plugin));
  }

}
