<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens for contrib when a patch fails coding standards.
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 * @group phpcs
 *
 * @see TESTING.md
 */
class ContribD8SniffFailsTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_CS_SniffFailsTest=TRUE',
    'DCI_Fetch=https://www.drupal.org/files/issues/2842832_2-fail-phpcs.patch,.',
    'DCI_Patch=2842832_2-fail-phpcs.patch,modules/examples',
  ];

  public function testD8Contrib() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.ContribD8SniffFailsTest.yml',
    ], $options);
    // Assert output text and status code.
    $this->assertRegExp('/Checking for PHPCS config file/', $app_tester->getDisplay());
    $this->assertRegExp('/Executing PHPCS./', $app_tester->getDisplay());
    $this->assertEquals(1, $app_tester->getStatusCode());

    // Assert report.
    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $app->getContainer()['build'];
    $artifact_file = $build->getArtifactDirectory() . '/phpcs/checkstyle.xml';
    $this->assertTrue(file_exists($artifact_file));
  }

}
