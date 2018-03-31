<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens when a patch is applied to core.
 *
 * This test comes from:
 * https://dispatcher.drupalci.org/job/default/122151/consoleFull
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 *
 * @see TESTING.md
 */
class CorePatchAppliedTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */

  public function testCorePatchApplied() {

    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.CorePatchAppliedTest.yml',
    ], $options);
    $this->assertRegExp('/.*2572307-30.patch applied.*/', $this->app_tester->getDisplay());
    $this->assertEquals(0, $this->app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'Build Successful');
    $this->assertBuildOutputJson($build, 'buildDetails', '');
  }

}
