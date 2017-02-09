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

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_AdditionalRepositories=git,git://git.drupal.org/project/block_field.git,8.x-1.x,modules/block_field,1;',
    'DCI_UseLocalCodebase=/var/lib/drupalci/drupal-checkout',
    'DCI_LocalBranch=8.3.x',
    'DCI_DBType=sqlite',
    'DCI_DBVersion=3.8',
    'DCI_LocalCommitHash=469d128',
    'DCI_JobType=simpletest',
    'DCI_PHPVersion=php-7.0-apache:production',
    'DCI_TestItem=directory:modules/block_field',
    'DCI_CS_SkipCodesniff=TRUE',
  ];

  public function testD8Contrib() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);
    $this->assertRegExp('/.*Drupal\\\\block_field\\\\Tests.*/', $app_tester->getDisplay());
    $this->assertEquals(0, $app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $app->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'Build Successful');
    $this->assertBuildOutputJson($build, 'buildDetails', '');
  }

}
