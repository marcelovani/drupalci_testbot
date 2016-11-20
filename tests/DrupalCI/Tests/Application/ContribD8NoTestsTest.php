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
 */
class ContribD8NoTestsTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_AdditionalRepositories=git,https://git.drupal.org/sandbox/Mile23/2683655.git,8.x-1.x,modules/drupalci_d8_module_no_tests,1;',
    'DCI_ComposerInstall=true',
    'DCI_CoreBranch=8.1.x',
    'DCI_CoreRepository=file:///var/lib/drupalci/drupal-checkout',
    'DCI_DBType=mysql',
    'DCI_DBVersion=5.5',
    'DCI_JobType=simpletest',
    'DCI_PHPVersion=7',
    'DCI_TestItem=directory:modules/drupalci_d8_module_no_tests',
  ];

  public function testD8ContribNoTests() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);
    $this->assertRegExp('/ERROR: No valid tests were specified./', $app_tester->getDisplay());
    $this->assertEquals(0, $app_tester->getStatusCode());
  }
}
