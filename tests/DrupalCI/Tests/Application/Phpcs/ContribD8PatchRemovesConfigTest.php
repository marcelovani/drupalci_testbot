<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens when a patch removes phpcs.xml.dist from contrib.
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 * @group phpcs
 *
 * @see TESTING.md
 */
class ContribD8PatchRemovesConfigTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_DEBUG=TRUE',
    'DCI_Fetch=https://www.drupal.org/files/issues/2839170_remove_phpcs_from_examples.patch,.',
    'DCI_Patch=2839170_remove_phpcs_from_examples.patch,modules/examples',
  ];

  public function testRemovePhpcsXmlDists() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.ContribD8Examples.yml',
    ], $options);
    // Assert output text and status code.
    $this->assertRegExp('/Checking for PHPCS config file/', $app_tester->getDisplay());
    $this->assertRegExp('/PHPCS config file not found. Using Drupal standard./', $app_tester->getDisplay());
    $this->assertRegExp('`Attempting to install drupal/coder`', $app_tester->getDisplay());
    $this->assertRegExp('/Config value "installed_paths" added successfully/', $app_tester->getDisplay());
    $this->assertRegExp('/Executing PHPCS./', $app_tester->getDisplay());
    $this->assertEquals(0, $app_tester->getStatusCode());

    // Assert report.
    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $app->getContainer()['build'];
    $artifact_file = $build->getArtifactDirectory() . '/phpcs/checkstyle.xml';
    $this->assertTrue(file_exists($artifact_file));
  }
}
