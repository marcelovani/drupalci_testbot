<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test a fatal during --list discovery.
 *
 * NOTE: This test assumes you have followed the setup instructions in
 * TESTING.md
 *
 * @group Application
 *
 * @see TESTING.md
 */
class CoreD8ListFatalTest extends DrupalCIFunctionalTestBase {

  public function testFatalWhileMakingList() {

    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];

    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.CoreD8ListFatalTest.yml',
    ], $options);
    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getCommand('run')->getBuild();
    $this->assertRegExp('/.*PHP Fatal error:  Trait \'Drupal\\\\Tests\\\\rest\\\\Functional\\\\BcTimestampNormalizerUnixTestTrait\' not found.*/', $app_tester->getDisplay());

    // Make sure that no tests were run.
    $this->assertNotRegExp('/Drupal test run/', $app_tester->getDisplay());
    $this->assertNotRegExp('/Tests to be run:/', $app_tester->getDisplay());
    // The testbot should return 2 if there was an error.
    $this->assertEquals(2, $app_tester->getStatusCode());

    $this->assertBuildOutputJson($build, 'buildLabel', 'Unable to generate test groups');
    $this->assertBuildOutputJsonContains($build, 'buildDetails', 'PHP Fatal error:  Trait \'Drupal\\Tests\\rest\\Functional\\BcTimestampNormalizerUnixTestTrait\' not found');
  }

}
