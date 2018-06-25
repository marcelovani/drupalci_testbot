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
class CoreD7PassingTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */

  public function testCoreD7Passes() {

    $this->setUp();

    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.CoreD7PassingTest.yml',
    ], $options);
    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getCommand('run')->getBuild();
    $this->assertRegExp('/.*run_tests_d7*/', $this->app_tester->getDisplay());
    $this->assertRegExp('/.*Syslog functionality 17 passes, 0 fails, and 0 exceptions*/', $this->app_tester->getDisplay());
    // Look for junit xml results file
    $output_file = $build->getArtifactDirectory() . "/run_tests_d7/junitxml/run_tests_results.xml";
    $this->assertFileExists($output_file);
    // create a test fixture that contains the xml output results.
    $this->assertXmlFileEqualsXmlFile(__DIR__ . '/Fixtures/CoreD7PassingTest_testresults.xml', $output_file);
    $this->assertEquals(0, $this->app_tester->getStatusCode());

    $this->assertBuildOutputJson($build, 'buildLabel', 'Build Successful');
    $this->assertBuildOutputJson($build, 'buildDetails', '');
  }

}
