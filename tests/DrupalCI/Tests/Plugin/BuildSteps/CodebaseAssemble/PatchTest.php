<?php

namespace DrupalCI\Tests\Plugin\CodebaseAssemble;

use DrupalCI\Build\Codebase\PatchFactoryInterface;
use DrupalCI\Build\Codebase\PatchInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskException;
use DrupalCI\Tests\DrupalCITestCase;

use DrupalCI\Build\BuildInterface;
use org\bovigo\vfs\vfsStream;

class PatchTest extends DrupalCITestCase {

  /**
   * Mock a build object that has a vfs xml directory.
   */
  protected function getMockBuild() {
    vfsStream::setup('xml_directory');
    // Make a build service that will output files to the place we like.
    $build = $this->getMockBuilder(BuildInterface::class)
      ->setMethods(['getXmlDirectory'])
      ->getMockForAbstractClass();
    $build->expects($this->once())
      ->method('getXmlDirectory')
      ->willReturn(vfsStream::url('xml_directory'));
    return $build;
  }

  protected function getPatchFactory($patch_worker) {
    // Have the factory 'generate' the mock patch object.
    $patch_factory = $this->getMockBuilder(PatchFactoryInterface::class)
      ->setMethods(['getPatch'])
      ->getMockForAbstractClass();
    $patch_factory->expects($this->once())
      ->method('getPatch')
      ->willReturn($patch_worker);
    return $patch_factory;
  }

  public function testNoFromConfig() {
    $this->setExpectedException(
      BuildTaskException::class,
      'No valid patch file provided for the patch command.'
    );

    $container = $this->getContainer();

    // Use the plugin factory to get a patch plugin to test.
    $plugin_factory = $container['plugin.manager.factory']->create('BuildTask');
    // We pass in incomplete configuration, so there are no 'from' files.
    $patch_plugin = $plugin_factory->getPlugin('BuildStep', 'patch', ['patches' => ['not-from' => 'value']]);

    $patch_plugin->run();
  }

  /**
   * Test behavior when the patch fails validation.
   */
  public function testFailPatchValidate() {
    $this->setExpectedException(
      BuildTaskException::class,
      'Failed to validate the patch source and/or target directory.'
    );

    // Make a patch file object that refuses to validate.
    $patch_worker = $this->getMockBuilder(PatchInterface::class)
      ->setMethods(['validate'])
      ->getMockForAbstractClass();
    $patch_worker->expects($this->once())
      ->method('validate')
      ->willReturn(FALSE);

    $container = $this->getContainer([
      'patch_factory' => $this->getPatchFactory($patch_worker),
      'build' => $this->getMockBuild(),
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
   */
  public function testFailPatchApply() {
    $this->setExpectedException(
      BuildTaskException::class,
      'Unable to apply the patch.'
    );

    // Make a patch file object that validates but refuses to patch.
    $patch_worker = $this->getMockBuilder(PatchInterface::class)
      ->setMethods(['validate', 'apply'])
      ->getMockForAbstractClass();
    $patch_worker->expects($this->once())
      ->method('validate')
      ->willReturn(TRUE);
    $patch_worker->expects($this->once())
      ->method('apply')
      ->willReturn(1);

    $container = $this->getContainer([
      'patch_factory' => $this->getPatchFactory($patch_worker),
      'build' => $this->getMockBuild(),
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