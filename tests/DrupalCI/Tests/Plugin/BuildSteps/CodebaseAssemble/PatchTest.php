<?php

namespace DrupalCI\Tests\Plugin\CodebaseAssemble;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Build\Codebase\PatchFactoryInterface;
use DrupalCI\Build\Codebase\PatchInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskException;
use DrupalCI\Tests\DrupalCITestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble\Patch
 */
class PatchTest extends DrupalCITestCase {

  /**
   * Mock a build object that has a vfs ancillary directory.
   */
  protected function mockBuild() {
    vfsStream::setup('ancillary_directory');
    // Make a build service that will output files to the place we like.
    $build = $this->getMockBuilder(BuildInterface::class)
      ->setMethods(['getAncillaryWorkDirectory'])
      ->getMockForAbstractClass();
    $build->expects($this->once())
      ->method('getAncillaryWorkDirectory')
      ->willReturn(vfsStream::url('ancillary_directory'));
    return $build;
  }

  /**
   * Mock a patch factory service object.
   *
   * @param $patch_worker
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   */
  protected function mockPatchFactory($patch_worker) {
    // Have the factory 'generate' the mock patch object.
    $patch_factory = $this->getMockBuilder(PatchFactoryInterface::class)
      ->setMethods(['getPatch'])
      ->getMockForAbstractClass();
    $patch_factory->expects($this->once())
      ->method('getPatch')
      ->willReturn($patch_worker);
    return $patch_factory;
  }

  /**
   * @covers ::run
   */
  public function testNoFromConfig() {
    $this->expectException(BuildTaskException::class);
    $this->expectExceptionMessage('Invalid Patch');

    $container = $this->getContainer();

    // Make a config that does not have a 'from' key.
    $configuration = [
      'patches' => [
        ['not-from' => 'value'],
      ]
    ];

    // Use the plugin factory to get a patch plugin to test.
    $plugin_factory = $container['plugin.manager.factory']->create('BuildTask');
    $patch_plugin = $plugin_factory->getPlugin('BuildStep', 'patch', $configuration);

    $patch_plugin->run();
  }

  /**
   * Test behavior when the patch fails validation.
   *
   * @covers ::run
   */
  public function testFailPatchValidate() {
    $this->expectException(BuildTaskException::class);
    $this->expectExceptionMessage('Patch Validation Error');

    // Make a patch file object that refuses to validate.
    $patch_worker = $this->getMockBuilder(PatchInterface::class)
      ->setMethods(['validate'])
      ->getMockForAbstractClass();
    $patch_worker->expects($this->once())
      ->method('validate')
      ->willReturn(FALSE);

    $container = $this->getContainer([
      'patch_factory' => $this->mockPatchFactory($patch_worker),
      'build' => $this->mockBuild(),
    ]);

    $configuration = [
      'patches' => [
        ['from' => 'frommage', 'to' => 'tutelage'],
      ]
    ];
    // Use the plugin factory to get a patch plugin to test.
    $plugin_factory = $container['plugin.manager.factory']->create('BuildTask');
    $patch_plugin = $plugin_factory->getPlugin('BuildStep', 'patch', $configuration);

    $patch_plugin->run();
  }

  /**
   * Test behavior when the patch is unable to apply.
   *
   * @covers ::run
   */
  public function testFailPatchApply() {
    $this->expectException(BuildTaskException::class);
    $this->expectExceptionMessage('Patch Failed to Apply');

    // Make a patch file object that validates.
    $patch_worker = $this->getMockBuilder(PatchInterface::class)
      ->setMethods(['validate', 'apply', 'getPatchApplyResults'])
      ->getMockForAbstractClass();
    $patch_worker->expects($this->once())
      ->method('validate')
      ->willReturn(TRUE);
    // Ensure that the mocked patch object fails to apply the patch.
    $patch_worker->expects($this->once())
      ->method('apply')
      ->willReturn(1);
    $patch_worker->expects($this->once())
      ->method('getPatchApplyResults')
      ->willReturn(['Arbitrary command output.']);

    $container = $this->getContainer([
      'patch_factory' => $this->mockPatchFactory($patch_worker),
      'build' => $this->mockBuild(),
    ]);

    // We need valid config with a 'to' and a 'from'.
    $configuration = [
      'patches' => [
        ['from' => 'frommage', 'to' => 'tutelage'],
      ]
    ];
    // Use the plugin factory to get a patch plugin to test.
    $plugin_factory = $container['plugin.manager.factory']->create('BuildTask');
    $patch_plugin = $plugin_factory->getPlugin('BuildStep', 'patch', $configuration);

    $patch_plugin->run();
  }

}
