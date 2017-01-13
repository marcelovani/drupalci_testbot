<?php

namespace DrupalCI\Tests\Application\Phpcs;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens when you find a php linting error.
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 * @group phpcs
 *
 * @see TESTING.md
 */
class PhpLintFailTest extends DrupalCIFunctionalTestBase {

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
    'DCI_Fetch=https://www.drupal.org/files/issues/2809565_actually_fails_linting.patch,.',
    'DCI_Patch=2809565_actually_fails_linting.patch,.',
  ];

  public function testCoderSniffOnlyChangedFailTest() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.PhpLint.yml',
    ], $options);
    $this->assertRegExp('/Parse error: syntax error, unexpected end of file/', $app_tester->getDisplay());
    $this->assertRegExp('/PHPLint Failed/', $app_tester->getDisplay());
    $this->assertEquals(2, $app_tester->getStatusCode());
  }
}
