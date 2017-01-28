<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test a D7 contrib module with no phpcs.xml file.
 *
 * @group Application
 * @group phpcs
 *
 * @see TESTING.md
 */
class ContribD7NoConfigTest extends DrupalCIFunctionalTestBase {

  public function testExamplesD7() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.ContribD7Examples.yml',
    ], $options);
    // Assert output text and status code.No modified PHP files. Sniffing all files.
    $this->assertRegExp('/Checking for phpcs\.xml\(\.dist\) file/', $app_tester->getDisplay());
    $this->assertRegExp('/No modified PHP files. Sniffing all files/', $app_tester->getDisplay());
    $this->assertRegExp('/PHPCS config file not found. Using Drupal standard./', $app_tester->getDisplay());
    $this->assertRegExp('`Attempting to install drupal/coder`', $app_tester->getDisplay());
    $this->assertRegExp('/Config value "installed_paths" added successfully/', $app_tester->getDisplay());
    $this->assertRegExp('/DrupalPractice and Drupal/', $app_tester->getDisplay());
    $this->assertRegExp('/Executing PHPCS/', $app_tester->getDisplay());
    $this->assertEquals(0, $app_tester->getStatusCode());

    // Assert report.
    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $app->getContainer()['build'];
    $artifact_file = $build->getArtifactDirectory() . '/phpcs/checkstyle.xml';
    $this->assertTrue(file_exists($artifact_file));
  }
}
