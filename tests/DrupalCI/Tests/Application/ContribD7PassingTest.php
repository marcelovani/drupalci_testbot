<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test a passing d7 contrib test.
 *
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 *
 * @see TESTING.md
 */
class ContribD7PassingTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_AdditionalRepositories=git,git://git.drupal.org/project/forena.git,7.x-4.x,sites/all/modules/forena,1;',
    'DCI_LocalBranch=7.x',
    'DCI_UseLocalCodebase=/var/lib/drupalci/drupal-checkout',
    'DCI_DBType=mysql',
    'DCI_DBVersion=5.5',
    'DCI_LocalCommitHash=5533335',
    'DCI_JobType=simpletestlegacy7',
    'DCI_PHPVersion=php-5.3.29-apache:production',
    'DCI_ProjectType=module',
    'DCI_ProjectName=forena',
    'DCI_CS_SkipCodesniff=TRUE',
  ];

  public function testD7Contrib() {

    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
    ], $options);
    $this->assertRegExp('/.*simpletestlegacy7*/', $this->app_tester->getDisplay());
    $this->assertRegExp('/Forena Reports 15 passes, 0 fails, and 0 exceptions/', $this->app_tester->getDisplay());
    $this->assertEquals(0, $this->app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'Build Successful');
    $this->assertBuildOutputJson($build, 'buildDetails', '');
  }

}
