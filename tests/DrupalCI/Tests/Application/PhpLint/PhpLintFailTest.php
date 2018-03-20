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
    'DCI_UseLocalCodebase=/var/lib/drupalci/drupal-checkout',
    'DCI_LocalBranch=8.3.x',
    'DCI_DBType=sqlite',
    'DCI_Concurrency=4',
    'DCI_PHPVersion=php-7.0-apache:production',
    'DCI_Fetch=https://www.drupal.org/files/issues/2809565_actually_fails_linting.patch,.',
    'DCI_Patch=2809565_actually_fails_linting.patch,.',
    'DCI_CS_SkipCodesniff=TRUE',

  ];

  public function testPhpLintFailTest() {

    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.PhpLint.yml',
    ], $options);
    $foo = $this->app_tester->getDisplay();
    $this->assertRegExp('/Parse error:  syntax error, unexpected end of file/', $this->app_tester->getDisplay());
    $this->assertRegExp('/PHPLint Failed/', $this->app_tester->getDisplay());
    $this->assertEquals(2, $this->app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'PHPLint Failed');
    $this->assertBuildOutputJson($build, 'buildDetails', '--- Commands Executed ---
cd /var/www/html && xargs -P 4 -a /var/lib/drupalci/workdir/phplint/lintable_files.txt -I {} php -l \'{}\'
Return Code: 124
--- Output ---
Errors parsing /var/www/html/core/IWillFailLinting.php
--- Errors ---
PHP Parse error:  syntax error, unexpected end of file in /var/www/html/core/IWillFailLinting.php on line 3
xargs: php: exited with status 255; aborting
');
  }

}
