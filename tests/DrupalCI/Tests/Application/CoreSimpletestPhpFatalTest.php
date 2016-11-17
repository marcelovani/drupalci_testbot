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
    'DCI_ComposerInstall=True',
    'DCI_Concurrency=2',
    'DCI_CoreBranch=8.1.x',
    'DCI_CoreRepository=file:///var/lib/drupalci/drupal-checkout',
    'DCI_DBType=mysql',
    'DCI_DBVersion=5.5',
    'DCI_Fetch=https://www.drupal.org/files/issues/2684095-2.patch,.',
    'DCI_GitCommitHash=6afe359',
    'DCI_JobType=simpletest',
    'DCI_PHPVersion=5.5',
    'DCI_Patch=2684095-2.patch,.',
    'DCI_RunScript=/var/www/html/core/scripts/run-tests.sh ',
    'DCI_TestGroups=--class "Drupal\comment\Tests\CommentItemTest"',
  ];

  public function testSimpletestPhpFatal() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);
    $this->assertRegExp('/Fatal error/', $app_tester->getDisplay());
    $this->assertEquals(255, $app_tester->getStatusCode());
  }
}
