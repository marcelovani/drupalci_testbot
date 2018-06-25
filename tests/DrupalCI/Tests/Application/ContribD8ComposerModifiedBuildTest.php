<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Basic test that proves that drupalci can execute a simpletest and generate a result
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 * @group docker
 * @group Xml
 *
 * @see TESTING.md
 */
class ContribD8ComposerModifiedBuildTest extends DrupalCIFunctionalTestBase {

  public function testBasicTest() {

    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.ContribD8ComposerModifiedBuildTest.yml',

    ], $options);
    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getCommand('run')->getBuild();
    $display = $this->app_tester->getDisplay();
    $this->assertRegExp('/.*Drupal\\\\Tests\\\\monolog\\\\Unit\\\\Logger\\\\LoggerTest*/', $this->app_tester->getDisplay());
    // Drupal\Tests\monolog\Unit\Logger\LoggerTest
    // Look for junit xml results file
    $output_file = $build->getArtifactDirectory() . "/run_tests.standard/junitxml/run_tests_results.xml";
    $this->assertFileExists($output_file);
    // create a test fixture that contains the xml output results.
    $this->assertXmlFileEqualsXmlFile(__DIR__ . '/Fixtures/ContribD8ComposerBuildTest_testresults.xml', $output_file);
    $this->assertEquals(0, $this->app_tester->getStatusCode());

    $this->assertBuildOutputJson($build, 'buildLabel', 'Build Successful');
    $this->assertBuildOutputJson($build, 'buildDetails', '');
  }

}
