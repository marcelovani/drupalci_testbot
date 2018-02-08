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
class ContainerComposerPassTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    // Specify core 8.3.x and PHP 7, which are compatible.
    'DCI_LocalBranch=8.3.x',
    'DCI_LocalCommitHash=3af6a3e',
    'DCI_PHPVersion=php-7.0-apache:production',
    'DCI_UseLocalCodebase=/var/lib/drupalci/drupal-checkout',
    'DCI_DBType=sqlite',
    'DCI_CS_SkipCodesniff=TRUE',
  ];

  public function testPlatform() {

    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.ComposerContainer.yml',
    ], $options);
    $this->assertRegExp('/Running Composer within the environment./', $this->app_tester->getDisplay());
    $this->assertNotRegExp('/Your requirements could not be resolved to an installable set of packages./', $this->app_tester->getDisplay());
    $this->assertNotRegExp('/Composer error. Unable to continue./', $this->app_tester->getDisplay());
    $this->assertEquals(0, $this->app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'Build Successful');
    $this->assertBuildOutputJson($build, 'buildDetails', '');
  }

}
