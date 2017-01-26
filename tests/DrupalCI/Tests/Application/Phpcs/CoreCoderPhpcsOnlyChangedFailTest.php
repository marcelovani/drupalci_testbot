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
class CoreCoderPhpcsOnlyChangedFailTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_UseLocalCodebase=/var/lib/drupalci/drupal-checkout',
    'DCI_LocalBranch=8.3.x',
    'DCI_DBType=sqlite',
    'DCI_PHPVersion=php-7.0-apache:production',
    'DCI_DEBUG=TRUE',
    'DCI_Fetch=https://www.drupal.org/files/issues/2839170-coder-phpcs-sniff-error.patch,.',
    'DCI_Patch=2839170-coder-phpcs-sniff-error.patch,.',
    'DCI_Composer_ForceCoderInstall=TRUE',
    'DCI_CS_SniffOnlyChanged=TRUE',
    'DCI_CS_ConfigInstalledPaths=/vendor/drupal/coder/coder_sniffer/',
    'DCI_CS_ConfigDirectory=core/',
    'DCI_CS_SniffFailsTest=TRUE',
  ];

  public function testCoderSniffOnlyChangedFailTest() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.CoreSniff.yml',
    ], $options);
    $this->assertRegExp('/Running PHP Code Sniffer review on modified files./', $app_tester->getDisplay());
    $this->assertEquals(1, $app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $app->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'Build Successful');
    $this->assertBuildOutputJson($build, 'buildDetails', '');
  }
}
