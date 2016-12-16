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
class ContribD8PatchedPassingTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_AdditionalRepositories=git,git://git.drupal.org/project/config_readonly.git,8.x-1.x,modules/config_readonly,1;',
    'DCI_Fetch=https://www.drupal.org/files/issues/stop_block_placement-2728679-8.patch,modules/config_readonly',
    'DCI_Patch=stop_block_placement-2728679-8.patch,modules/config_readonly',
    'DCI_UseLocalCodebase=/var/lib/drupalci/drupal-checkout',
    'DCI_LocalBranch=8.3.x',
    'DCI_DBType=mysql',
    'DCI_DBVersion=5.5',
    'DCI_LocalCommitHash=765c10b',
    'DCI_JobType=simpletest',
    'DCI_PHPVersion=5.6',
    'DCI_TestItem=directory:modules/config_readonly',
  ];

  public function testD8Contrib() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);
    $this->assertRegExp('/.*Drupal\\\\config_readonly\\\\Tests.*/', $app_tester->getDisplay());
    $this->assertEquals(0, $app_tester->getStatusCode());
  }
}
