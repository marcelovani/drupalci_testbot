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
 * @group Xml
 *
 * @see TESTING.md
 */
class CoreD8CloneMySqlPassingTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */

  protected $dciConfig = [
    'DCI_CoreRepository=git://git.drupal.org/project/drupal.git',
    'DCI_CoreBranch=8.3.x',
    'DCI_GitCommitHash=f4a242f',
    'DCI_JobType=simpletest',
    'DCI_TestItem=Url',
    'DCI_PHPVersion=php-7.0-apache:production',
    'DCI_DBType=mysql',
    'DCI_DBVersion=5.5',
    'DCI_CS_SkipCodesniff=TRUE',
  ];

  public function testBasicTest() {

    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
    ], $options);
    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getCommand('run')->getBuild();
    $display = $this->app_tester->getDisplay();
    $this->assertNotRegExp('/.*simpletestlegacy7*/', $this->app_tester->getDisplay());
    $this->assertRegExp('/.*Drupal\\\\KernelTests\\\\Core\\\\Routing\\\\UrlIntegrationTest*/', $this->app_tester->getDisplay());
    // Look for junit xml results file
    $output_file = $build->getXmlDirectory() . "/standard.testresults.xml";
    $this->assertFileExists($output_file);
    // create a test fixture that contains the xml output results.
    $this->assertXmlFileEqualsXmlFile(__DIR__ . '/Fixtures/CoreD8PassingTest_testresults.xml', $output_file);
    $this->assertEquals(0, $this->app_tester->getStatusCode());

    $this->assertBuildOutputJson($build, 'buildLabel', 'Build Successful');
    $this->assertBuildOutputJson($build, 'buildDetails', '');
  }

}
