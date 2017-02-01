<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens when a patch fails to apply properly.
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 *
 * @see TESTING.md
 */
class CorePatchFailTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_LocalBranch=8.1.x',
    'DCI_UseLocalCodebase=/var/lib/drupalci/drupal-checkout',
    'DCI_DBType=mysql',
    'DCI_DBVersion=5.5',
    'DCI_Fetch=http://drupal.org/files/issues/does_not_apply.patch',
    'DCI_JobType=simpletest',
    'DCI_PHPVersion=php-7.0-apache:production',
    'DCI_Patch=does_not_apply.patch',
    'DCI_TestItem=ban',
  ];

  public function testBadPatch() {

    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];

    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);
    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getCommand('run')->getBuild();
    $this->assertRegExp('/.*The patch attempt returned an error.*/', $app_tester->getDisplay());
    /* @todo: The testbot should return 2 if there was an error
     * https://www.drupal.org/node/2846398 goes in.
    // Make sure that no tests were run.
    $this->assertNotRegExp('/Drupal test run/', $app_tester->getDisplay());
    $this->assertNotRegExp('/Tests to be run:/', $app_tester->getDisplay());
    // The testbot should return 2 if there was an error.
    $this->assertEquals(2, $app_tester->getStatusCode());
     */
    $this->assertEquals(0, $app_tester->getStatusCode());
    $output_file = $build->getXmlDirectory() . "/patchfailure.xml";
    $this->assertFileExists($output_file);
    // Compare our fail to the fixture output.
    $this->assertXmlFileEqualsXmlFile(__DIR__ . '/Fixtures/CorePatchFailTestpatchfailure.xml', $output_file);

    $this->assertBuildOutputJson($build, 'buildLabel', 'Build Successful');
    $this->assertBuildOutputJson($build, 'buildDetails', '');
  }

}
