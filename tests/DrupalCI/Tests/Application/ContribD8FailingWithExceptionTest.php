<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens when a D8.1.x Contrib module has dependencies.
 * https://dispatcher.drupalci.org/job/default/63496/
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 * @group Xml
 *
 * @see TESTING.md
 */
class ContribD8FailingWithExceptionTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_AdditionalRepositories=git,git://git.drupal.org/project/flag.git,8.x-4.x,modules/flag,1;',
    'DCI_CoreRepository=file:///var/lib/drupalci/drupal-checkout',
    'DCI_CoreBranch=8.3.x',
    'DCI_DBType=mysql',
    'DCI_DBVersion=5.5',
    'DCI_Fetch=https://www.drupal.org/files/issues/2716613-69.flag_.permissions-author.patch,modules/flag',
    'DCI_GitCommitHash=24343f9',
    'DCI_JobType=simpletest',
    'DCI_Patch=2716613-69.flag_.permissions-author.patch,modules/flag',
    'DCI_PHPVersion=php-5.6-apache:production',
    'DCI_TestItem=directory:modules/flag',
    'DCI_CS_SkipCodesniff=TRUE',
  ];

  public function testD8Contrib() {
    // Skip this test. I used it to prove that when exeptions happen, that the
    // Testresults do not get cut off, however, the xml contains a ton of
    // run-specific data that I dont really know how to control for
    // (date time stamps, random strings etc)
    $this->markTestSkipped();
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);
    $build = $this->getCommand('run')->getBuild();
    $output_file = $build->getXmlDirectory() . "/testresults.xml";
    $this->assertContains('FATAL Drupal\Tests\flag_follower\Kernel\FlagFollowerInstallUninstallTest: test runner returned a non-zero error code (2).', $app_tester->getDisplay());
    $this->assertContains('Drupal\flag\Tests\UserFlagTypeTest                            38 passes   6 fails   2 exceptions', $app_tester->getDisplay());
    $this->assertXmlFileEqualsXmlFile(__DIR__ . '/Fixtures/ContribD8FailingWithExceptionTest_testresults.xml', $output_file);
    $this->assertEquals(0, $app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $app->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'Build Successful');
    $this->assertBuildOutputJson($build, 'buildDetails', '');
  }

}
