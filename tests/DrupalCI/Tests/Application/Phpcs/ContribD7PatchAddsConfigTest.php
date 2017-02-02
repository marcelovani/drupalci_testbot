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
class ContribD7PatchAddsConfigTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_Fetch=https://www.drupal.org/files/issues/2839170_add_phpcsxmldist_to_examples_7.patch,sites/all/modules/examples',
    'DCI_Patch=2839170_add_phpcsxmldist_to_examples_7.patch,sites/all/modules/examples',
  ];

  public function testExamplesD7PatchPhpcsXml() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.ContribD7Examples.yml',
    ], $options);
    // Assert output text and status code.No modified files. Sniffing all files.
    $this->assertRegExp('/Checking for phpcs\.xml\(\.dist\) file/', $app_tester->getDisplay());
    $this->assertRegExp('/Using existing PHPCS config file/', $app_tester->getDisplay());
    $this->assertRegExp('/PHPCS config file modified, sniffing entire project./', $app_tester->getDisplay());
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
