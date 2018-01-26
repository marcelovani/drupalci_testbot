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

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_Color=True',
    'DCI_Concurrency=2',
    'DCI_LocalBranch=8.1.x',
    'DCI_UseLocalCodebase=/var/lib/drupalci/drupal-checkout',
    'DCI_DBType=mysql',
    'DCI_DBVersion=5.5',
    'DCI_Fetch=https://www.drupal.org/files/issues/2684095-2.patch,.',
    'DCI_LocalCommitHash=6afe359',
    'DCI_JobType=simpletest',
    'DCI_PHPVersion=php-5.5.38-apache:production',
    'DCI_Patch=2684095-2.patch,.',
    'DCI_TestItem=--class "Drupal\comment\Tests\CommentItemTest"',
    'DCI_CS_SkipCodesniff=TRUE',
  ];

  public function testSimpletestPhpFatal() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);
    $this->assertRegExp('/Fatal error/', $app_tester->getDisplay());
    // This scenario causes a PHP fatal during testing, which should result in a
    // status code of 2.
    $this->assertEquals(2, $app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $app->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'Simpletest fatal error');
    $this->assertBuildOutputJsonContains($build, 'buildDetails', 'PHP Fatal error:  Class \'Drupal\\KernelTests\\field\\FieldUnitTestBase\' not found');
  }

}
