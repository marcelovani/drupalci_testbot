<?php

namespace DrupalCI\Tests\Application\Phpcs;

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

    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.ContribD7Examples.yml',
    ], $options);
    // Assert output text and status code.No modifiedfiles. Sniffing all files.
    $this->assertRegExp('/Checking for phpcs\.xml\(\.dist\) file/', $this->app_tester->getDisplay());
    $this->assertRegExp('/No modified files. Sniffing all files/', $this->app_tester->getDisplay());
    $this->assertRegExp('/PHPCS config file not found. Using Drupal standard./', $this->app_tester->getDisplay());
    $this->assertRegExp('`Attempting to install drupal/coder`', $this->app_tester->getDisplay());
    $this->assertRegExp('/Config value "installed_paths" added successfully/', $this->app_tester->getDisplay());
    $this->assertRegExp('/The installed coding standards are .* Drupal/', $this->app_tester->getDisplay());
    $this->assertRegExp('/Executing PHPCS/', $this->app_tester->getDisplay());
    $this->assertEquals(0, $this->app_tester->getStatusCode());

    // Assert report.
    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getContainer()['build'];
    $artifact_file = $build->getArtifactDirectory() . '/phpcs/checkstyle.xml';
    $this->assertTrue(file_exists($artifact_file));
  }

}
