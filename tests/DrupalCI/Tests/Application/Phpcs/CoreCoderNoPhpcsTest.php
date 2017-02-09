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
    'DCI_UseLocalCodebase=/var/lib/drupalci/drupal-checkout',
    'DCI_LocalCommitHash=e1c5a1e',
    'DCI_DBType=sqlite',
    'DCI_PHPVersion=php-7.0-apache:production',
  ];

  public function testCoderSniffWithNoPhpcs() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.CoreSniffNoPhpcs.yml',
    ], $options);

    $this->assertRegExp('/Checking for phpcs tool in codebase./', $app_tester->getDisplay());
    $this->assertRegExp('`Attempting to install drupal/coder`', $app_tester->getDisplay());
    $this->assertRegExp('/No modified files. Sniffing all files./', $app_tester->getDisplay());
    $this->assertNotRegExp('/Running PHP Code Sniffer review on modified files./', $app_tester->getDisplay());

    $this->assertEquals(0, $app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $app->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'Build Successful');
    $this->assertBuildOutputJson($build, 'buildDetails', '');
  }

}
