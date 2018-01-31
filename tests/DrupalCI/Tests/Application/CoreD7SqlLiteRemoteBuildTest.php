<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens a contrib module has no tests.
 *
 * This test depends on the drupalci_d8_no_tests module which you can find here:
 * https://www.drupal.org/sandbox/mile23/2683655
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 *
 * @see TESTING.md
 *
 * @todo Fix this in
 *   https://www.drupal.org/project/drupalci_testbot/issues/2925400
 */
class CoreD7SqlLiteRemoteBuildTest extends DrupalCIFunctionalTestBase {

  public function testCoreD7SqlLiteRemoteBuildTest() {

    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
      'definition' => 'https://dispatcher.drupalci.org/job/drupal_contrib/150041/artifact/jenkins-drupal_contrib-150041/artifacts/build.jenkins-drupal_contrib-150041.yml',
    ], $options);
    $display = $this->app_tester->getDisplay();
    $this->assertRegExp('!Build downloaded to /var/lib/drupalci/workspace/build.jenkins-drupal_contrib-150041.yml!', $this->app_tester->getDisplay());
    $this->assertRegExp('!Drupal\\\\block_field\\\\Tests\\\\BlockFieldTest!', $this->app_tester->getDisplay());
    $this->assertEquals(0, $this->app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'Build Successful');
    $this->assertBuildOutputJson($build, 'buildDetails', '');
  }

}
