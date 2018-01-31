<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test a fatal error during testing, but not during --list discovery.
 *
 * NOTE: This test assumes you have followed the setup instructions in
 * TESTING.md
 *
 * @group Application
 *
 * @see TESTING.md
 */
class ContribD8FatalTest extends DrupalCIFunctionalTestBase {

  public function testFatalWhileMakingList() {
    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.ContribD8FatalTest.yml',
    ], $options);
    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getCommand('run')->getBuild();
    $this->assertRegExp('/.*PHP Fatal error:  Trait \'Drupal\\\\Tests\\\\NodeCreationTrait\' not found.*/', $this->app_tester->getDisplay());

    // Make sure that no tests were run.
    $this->assertNotRegExp('/Drupal test run/', $this->app_tester->getDisplay());
    $this->assertNotRegExp('/Tests to be run:/', $this->app_tester->getDisplay());
    // The testbot should return 2 if there was an error.
    $this->assertEquals(2, $this->app_tester->getStatusCode());

    $this->assertBuildOutputJson($build, 'buildLabel', 'Simpletest fatal error');
    $this->assertBuildOutputJsonContains($build, 'buildDetails', "PHP Fatal error:  Trait 'Drupal\Tests\NodeCreationTrait' not found");
  }

}
