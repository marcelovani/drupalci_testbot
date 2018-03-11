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
  protected $dciConfig = [
    'DCI_LocalBranch=8.1.x',
    'DCI_UseLocalCodebase=/var/lib/drupalci/drupal-checkout',
    'DCI_LocalCommitHash=bdb434a',
    'DCI_DBType=mysql',
    'DCI_DBVersion=5.5',
    'DCI_Fetch=https://www.drupal.org/files/issues/2572307-30.patch,.',
    'DCI_Patch=2572307-30.patch,.',
    'DCI_JobType=simpletest',
    'DCI_PHPVersion=php-5.5.38-apache:production',
    'DCI_TestGroups=Url',
    'DCI_CS_SkipCodesniff=TRUE',
  ];

  public function testCorePatchApplied() {

    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
    ], $options);
    $this->assertRegExp('/.*2572307-30.patch applied.*/', $this->app_tester->getDisplay());
    $this->assertRegExp('/.*Drupal\\\\system\\\\Tests\\\\Routing\\\\UrlIntegrationTest*/', $this->app_tester->getDisplay());
    $this->assertEquals(0, $this->app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'Build Successful');
    $this->assertBuildOutputJson($build, 'buildDetails', '');
  }

}
