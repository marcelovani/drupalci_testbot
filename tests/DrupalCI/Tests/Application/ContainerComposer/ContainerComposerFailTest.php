<?php

namespace DrupalCI\Tests\Application\ContainerComposer;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens when Composer can't install under the container.
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 * @group phpcs
 *
 * @see TESTING.md
 */
class ContainerComposerFailTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    // Specify core 8.3.x and PHP 5.3.29, which have platform incompatibilities.
    'DCI_PHPVersion=php-5.3.29-apache:production',
  ];

  public function testPlatformMismatch() {

    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.ComposerContainer.yml',
    ], $options);
    $this->assertRegExp('/Your requirements could not be resolved to an installable set of packages./', $this->app_tester->getDisplay());
    $this->assertRegExp('/Composer error. Unable to continue./', $this->app_tester->getDisplay());
    $this->assertEquals(2, $this->app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'Composer error. Unable to continue.');
    $this->assertBuildOutputJsonContains($build, 'buildDetails', 'This package requires php >=5.5.9 but your PHP version (5.3.29) does not satisfy that requirement.');
  }

}
