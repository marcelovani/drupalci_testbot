<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Basic test that proves that drupalci can execute a simpletest and generate a result
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 * @group docker
 *
 * @see TESTING.md
 */
class CoreD8MySqlPassingTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */

  protected $dciConfig = [
    'DCI_UseLocalCodebase=/var/lib/drupalci/drupal-checkout',
    'DCI_LocalBranch=8.3.x',
    'DCI_LocalCommitHash=c187f1d',
    'DCI_JobType=simpletest',
    'DCI_TestItem=Url',
    'DCI_PHPVersion=php-7.0-apache:production',
    'DCI_DBType=mysql',
    'DCI_DBVersion=5.5',
  ];



  public function testBasicTest() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);
    $build = $this->getCommand('run')->getBuild();
    $display = $app_tester->getDisplay();
    $this->assertNotRegExp('/.*simpletestlegacy7*/', $app_tester->getDisplay());
    $this->assertRegExp('/.*Drupal\\\\KernelTests\\\\Core\\\\Routing\\\\UrlIntegrationTest*/', $app_tester->getDisplay());
    // Look for junit xml results file
    $output_file = $build->getXmlDirectory() . "/standard.testresults.xml";
    $this->assertFileExists($output_file);
    // create a test fixture that contains the xml output results.
    $this->assertXmlFileEqualsXmlFile(__DIR__ . '/Fixtures/CoreD8PassingTest_testresults.xml', $output_file);
    $this->assertEquals(0, $app_tester->getStatusCode());
  }
}
