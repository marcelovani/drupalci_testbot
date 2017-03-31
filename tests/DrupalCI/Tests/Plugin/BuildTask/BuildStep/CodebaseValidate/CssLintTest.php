<?php

namespace DrupalCI\Tests\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Build\Codebase\CodebaseInterface;
use DrupalCI\Tests\DrupalCITestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @group csslint
 * @coversDefaultClass \DrupalCI\Plugin\BuildTask\BuildStep\CodebaseValidate\CssLint
 */
class CssLintTest extends DrupalCITestCase {

  /**
   * Get a phpcs plugin from the factory.
   *
   * @param array $configuration
   *   Configuration to pass to the phpcs object.
   *
   * @return \DrupalCI\Plugin\BuildTask\BuildStep\CodebaseValidate\CssLint
   */
  protected function getCssLintPlugin($configuration = []) {
    $plugin_factory = $this->getContainer()['plugin.manager.factory']->create('BuildTask');
    return $plugin_factory->getPlugin('BuildStep', 'csslint', $configuration);
  }

  /**
   * Test discoverStartDirectoryAndContrib() for core use-case.
   *
   * @covers ::discoverStartDirectoryAndConfig
   */
  public function testDiscoverStartDirectoryAndConfigCore() {

    vfsStream::setup('css_test', NULL, ['source_dir' => ['.csslintrc' => 'configstuff']]);

    $codebase = $this->getMockBuilder(CodebaseInterface::class)
      ->setMethods(['getProjectName', 'getTrueExtensionSubDirectory', 'getSourceDirectory'])
      ->getMockForAbstractClass();
    // Always check core for this test.
    $codebase->expects($this->once())
      ->method('getProjectName')
      ->willReturn('drupal');
    $codebase->expects($this->never())
      ->method('getTrueExtensionSubDirectory')
      ->willReturn('something');
    $codebase->expects($this->once())
      ->method('getSourceDirectory')
      ->willReturn(vfsStream::url('css_test/source_dir'));

    $csslint_plugin = $this->getCssLintPlugin();

    $container = $this->getContainer(['codebase' => $codebase]);
    $csslint_plugin->inject($container);

    $ref_discover = new \ReflectionMethod($csslint_plugin, 'discoverStartDirectoryAndConfig');
    $ref_discover->setAccessible(TRUE);
    $ref_discover->invoke($csslint_plugin);

    $ref_config_file = new \ReflectionProperty($csslint_plugin, 'configFile');
    $ref_config_file->setAccessible(TRUE);

    $this->assertEquals('.csslintrc', $ref_config_file->getValue($csslint_plugin));

    $ref_configuration = new \ReflectionProperty($csslint_plugin, 'configuration');
    $ref_configuration->setAccessible(TRUE);
    $config = $ref_configuration->getValue($csslint_plugin);

    $this->assertEquals('core', $config['start_directory']);
  }

  /**
   * Test discoverStartDirectoryAndContrib() for core use-case.
   *
   * @covers ::discoverStartDirectoryAndConfig
   */
  public function testDiscoverStartDirectoryAndConfigContrib() {

    $filesystem = [
      'source_dir' => [
        'modules' => [
          'mymodule' => ['.csslintrc' => 'configstuff'],
        ],
      ],
    ];

    vfsStream::setup('css_test', NULL, $filesystem);

    $codebase = $this->getMockBuilder(CodebaseInterface::class)
      ->setMethods(['getProjectName', 'getTrueExtensionSubDirectory', 'getSourceDirectory'])
      ->getMockForAbstractClass();
    // Always check core for this test.
    $codebase->expects($this->once())
      ->method('getProjectName')
      ->willReturn('contrib');
    $codebase->expects($this->once())
      ->method('getTrueExtensionSubDirectory')
      ->willReturn('modules/mymodule');
    $codebase->expects($this->once())
      ->method('getSourceDirectory')
      ->willReturn(vfsStream::url('css_test/source_dir'));

    $csslint_plugin = $this->getCssLintPlugin();

    $container = $this->getContainer(['codebase' => $codebase]);
    $csslint_plugin->inject($container);

    $ref_discover = new \ReflectionMethod($csslint_plugin, 'discoverStartDirectoryAndConfig');
    $ref_discover->setAccessible(TRUE);
    $ref_discover->invoke($csslint_plugin);

    $ref_config_file = new \ReflectionProperty($csslint_plugin, 'configFile');
    $ref_config_file->setAccessible(TRUE);

    $this->assertEquals('modules/mymodule/.csslintrc', $ref_config_file->getValue($csslint_plugin));

    $ref_configuration = new \ReflectionProperty($csslint_plugin, 'configuration');
    $ref_configuration->setAccessible(TRUE);
    $config = $ref_configuration->getValue($csslint_plugin);

    $this->assertEquals('modules/mymodule', $config['start_directory']);
  }

}
