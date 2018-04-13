<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens a test run encounters a fatal error.
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 *
 * @see TESTING.md
 */
class CoreSimpletestPhpFatalTest extends DrupalCIFunctionalTestBase {



  public function testSimpletestPhpFatal() {

    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.CoreSimpletestPhpFatalTest.yml',
    ], $options);
    $this->assertRegExp('/Fatal error/', $this->app_tester->getDisplay());
    // This scenario causes a PHP fatal during testing, which should result in a
    // status code of 2.
    $this->assertEquals(2, $this->app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'run_tests.standard fatal error');
    $this->assertBuildOutputJsonContains($build, 'buildDetails', 'PHP Fatal error:  Class \'Drupal\\KernelTests\\field\\FieldUnitTestBase\' not found');
  }

}
