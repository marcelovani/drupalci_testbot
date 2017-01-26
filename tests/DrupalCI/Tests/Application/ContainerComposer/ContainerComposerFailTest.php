<?php

namespace DrupalCI\Tests\Application\ContainerComposer;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens when Composer can't install under the container.
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 * @group phpcs
 *
 * @see TESTING.md
 */
class ContainerComposerFailTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    // Specify core 8.3.x and PHP 5.3.29, which have platform incompatibilities.
    'DCI_LocalBranch=8.3.x',
    'DCI_LocalCommitHash=3af6a3e',
    'DCI_PHPVersion=php-5.3.29-apache:production',
    'DCI_UseLocalCodebase=/var/lib/drupalci/drupal-checkout',
    'DCI_DBType=sqlite',
    'DCI_DEBUG=TRUE',
    ];

  public function testContainerComposerWithPlatformMismatch() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.ComposerContainer.yml',
    ], $options);
    $this->assertRegExp('/Your requirements could not be resolved to an installable set of packages./', $app_tester->getDisplay());
    $this->assertRegExp('/Composer error. Unable to continue./', $app_tester->getDisplay());
    $this->assertEquals(2, $app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $app->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'Composer error. Unable to continue.');
    $this->assertBuildOutputJson($build, 'buildDetails', '

EXECUTING: /usr/local/bin/composer install --prefer-dist --no-suggest --no-progress --working-dir /var/www/html

Loading composer repositories with package information
Installing dependencies (including require-dev) from lock file
Your requirements could not be resolved to an installable set of packages.

  Problem 1
    - This package requires php >=5.5.9 but your PHP version (5.3.29) does not satisfy that requirement.
  Problem 2
    - guzzlehttp/promises 1.2.0 requires php >=5.5.0 -> your PHP version (5.3.29) does not satisfy that requirement.
    - guzzlehttp/promises 1.2.0 requires php >=5.5.0 -> your PHP version (5.3.29) does not satisfy that requirement.
    - Installation request for guzzlehttp/promises 1.2.0 -> satisfiable by guzzlehttp/promises[1.2.0].

');
  }
}
