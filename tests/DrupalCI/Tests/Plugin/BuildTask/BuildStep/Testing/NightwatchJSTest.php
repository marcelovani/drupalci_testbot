<?php

namespace DrupalCI\Tests\Plugin\BuildTask\BuildStep\Testing;

use DrupalCI\Build\Codebase\CodebaseInterface;
use DrupalCI\Build\Environment\DatabaseInterface;
use DrupalCI\Build\Environment\EnvironmentInterface;
use DrupalCI\Plugin\BuildTask\BuildStep\Testing\NightwatchJS;
use DrupalCI\Tests\DrupalCITestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @group NightwatchJS
 * @coversDefaultClass \DrupalCI\Plugin\BuildTask\BuildStep\Testing\NightwatchJS
 */
class NightwatchJSTest extends DrupalCITestCase {

  /**
   * We shouldn't run nightwatch if there is no configuration file.
   *
   * @covers ::run
   */
  public function testNightwatchNoConfig() {
    // Mock the filesystem. This must not contain a nightwatch config file. This
    // is for codebase::getSourceDirectory.
    vfsStream::setup('source_directory');

    // Mock our services. Only the codebase methods should ever run.
    $codebase = $this->getMockBuilder(CodebaseInterface::class)
      ->setMethods(['getSourceDirectory'])
      ->getMockForAbstractClass();
    $codebase->expects($this->once())
      ->method('getSourceDirectory')
      ->willReturn(vfsStream::url('source_directory'));

    $environment = $this->getMockBuilder(EnvironmentInterface::class)
      ->setMethods(['getChromeContainerHostname'])
      ->getMockForAbstractClass();
    $environment->expects($this->never())
      ->method('getChromeContainerHostname');

    $system_database = $this->getMockBuilder(DatabaseInterface::class)
      ->setMethods(['getUrl'])
      ->getMockForAbstractClass();
    $system_database->expects($this->never())
      ->method('getUrl');

    $container = $this->getContainer([
      'codebase' => $codebase,
      'environment' => $environment,
      'db.system' => $system_database,
    ]);

    // Mock the nightwatch plugin. We do this to ensure that prepareFilesystem
    // is never called and that no artifacts are generated.
    $nightwatch = $this->getMockBuilder(NightwatchJS::class)
      ->setMethods(['prepareFilesystem', 'saveContainerArtifact'])
      ->getMockForAbstractClass();
    $nightwatch->expects($this->never())
      ->method('prepareFilesystem');
    $nightwatch->expects($this->never())
      ->method('saveContainerArtifact');

    // Load up our service dependencies.
    $nightwatch->inject($container);

    // Run the step. We should get a 0 result code.
    $this->assertEquals(0, $nightwatch->run());
  }

}
