<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens when a D8.1.x Contrib module has dependencies.
 * https://dispatcher.drupalci.org/job/default/63496/
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 *
 * @see TESTING.md
 */
class ContribD8PassingTest extends DrupalCIFunctionalTestBase {

  public function testD8Contrib() {

    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.ContribD8PassingTest.yml',
    ], $options);
    $this->assertRegExp('/.*Drupal\\\\Tests\\\\block_field\\\\Functional.*/', $this->app_tester->getDisplay());
    $this->assertEquals(0, $this->app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'Build Successful');
    $this->assertBuildOutputJson($build, 'buildDetails', '');
  }

}
