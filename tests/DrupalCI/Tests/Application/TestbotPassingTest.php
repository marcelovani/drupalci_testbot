<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test trying to run the testbot's tests under the testbot.
 *
 * Test what happens when a D8.1.x Contrib module has dependencies. *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 *
 * @see TESTING.md
 */
class TestbotPassingTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_Checkout_Repo=https://git.drupal.org/project/drupalci_testbot.git',
    'DCI_Checkout_Branch=dev',
    'DCI_JobType=phpunit',
    'DCI_PHPVersion=7',
  ];

  public function testTestbot() {
    $this->markTestIncomplete("This test fails because the testbot's test fails, probably because it has so many dependencies.");
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);
    $this->assertRegExp('/CHANGE THIS WHEN THINGS MIGHT PASS/', $app_tester->getDisplay());
    $this->assertEquals(0, $app_tester->getStatusCode());
  }
}
