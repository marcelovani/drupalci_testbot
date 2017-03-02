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
 * @Xml
 *
 * @see TESTING.md
 */
class ContribD8ComposerBuildTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */

  protected $dciConfig = [
    'DCI_CoreRepository=git://git.drupal.org/project/drupal.git',
    'DCI_CoreBranch=8.3.x',
    'DCI_JobType=simpletest',
    'DCI_TestItem=directory:modules/monolog',
    'DCI_AdditionalRepositories=git,git://git.drupal.org/project/monolog.git,8.x-1.x,modules/monolog,1;',
    'DCI_PHPVersion=php-5.5.38-apache:production',
    'DCI_DBType=mysql',
    'DCI_DBVersion=5.5',
    'DCI_CS_SkipCodesniff=TRUE',
  ];

  public function testBasicTest() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);
    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getCommand('run')->getBuild();
    $display = $app_tester->getDisplay();
    $this->assertRegExp('/.*Drupal\\\\Tests\\\\monolog\\\\Unit\\\\Logger\\\\LoggerTest*/', $app_tester->getDisplay());
    // Drupal\Tests\monolog\Unit\Logger\LoggerTest
    // Look for junit xml results file
    $output_file = $build->getXmlDirectory() . "/standard.testresults.xml";
    $this->assertFileExists($output_file);
    // create a test fixture that contains the xml output results.
    $this->assertXmlFileEqualsXmlFile(__DIR__ . '/Fixtures/ContribD8ComposerBuildTest_testresults.xml', $output_file);
    $this->assertEquals(0, $app_tester->getStatusCode());

    $this->assertBuildOutputJson($build, 'buildLabel', 'Build Successful');
    $this->assertBuildOutputJson($build, 'buildDetails', '');
  }

}
