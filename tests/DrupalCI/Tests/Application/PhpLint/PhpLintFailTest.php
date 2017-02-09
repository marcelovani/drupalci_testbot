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
    'DCI_PHPVersion=php-7.0-apache:production',
    'DCI_Fetch=https://www.drupal.org/files/issues/2809565_actually_fails_linting.patch,.',
    'DCI_Patch=2809565_actually_fails_linting.patch,.',
  ];

  public function testPhpLintFailTest() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.PhpLint.yml',
    ], $options);
    $foo = $app_tester->getDisplay();
    $this->assertRegExp('/Parse error:  syntax error, unexpected end of file/', $app_tester->getDisplay());
    $this->assertRegExp('/PHPLint Failed/', $app_tester->getDisplay());
    $this->assertEquals(2, $app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $app->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'PHPLint Failed');
    $this->assertBuildOutputJson($build, 'buildDetails', '

EXECUTING: cd /var/www/html && xargs -P 4 -a /var/lib/drupalci/workdir/phplint/lintable_files.txt -I {} php -l \'{}\'

PHP Parse error:  syntax error, unexpected end of file in /var/www/html/core/IWillFailLinting.php on line 3
xargs: php: exited with status 255; aborting
');
  }

}
