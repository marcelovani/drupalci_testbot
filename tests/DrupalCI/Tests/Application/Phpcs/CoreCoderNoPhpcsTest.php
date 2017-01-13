<?php

namespace DrupalCI\Tests\Application\Phpcs;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens when you try to sniff and there's no phpcs executable.
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 * @group phpcs
 *
 * @see TESTING.md
 */
class CoreCoderNoPhpcsTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_CoreRepository=git://git.drupal.org/project/drupal.git',
    'DCI_CoreBranch=8.3.x',
    'DCI_UseLocalCodebase=/var/lib/drupalci/drupal-checkout',
    'DCI_LocalBranch=8.3.x',
    'DCI_DBType=sqlite',
    'DCI_PHPVersion=php-7.0-apache:production',
    'DCI_DEBUG=TRUE',
    'DCI_Composer_ForceCoderInstall=',
  ];

  public function testCoderSniffWithNoPhpcs() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.CoreSniff.yml',
    ], $options);
    $this->assertRegExp('/Checking for phpcs tool in codebase./', $app_tester->getDisplay());
    $this->assertRegExp('/phpcs file does not exist/', $app_tester->getDisplay());
    $this->assertNotRegExp('/Executing phpcs./', $app_tester->getDisplay());
    $this->assertEquals(0, $app_tester->getStatusCode());
  }
}
