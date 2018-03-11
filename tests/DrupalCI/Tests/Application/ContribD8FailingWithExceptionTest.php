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
    'DCI_Composer_Project=flag',
    'DCI_Composer_Branch=8.x-4.x#9daaa90d82fe580d2b5c64633a50d60593068d91',
    'DCI_CoreRepository=git://git.drupal.org/project/drupal.git',
    'DCI_CoreBranch=8.3.x',
    'DCI_DBType=mysql',
    'DCI_DBVersion=5.5',
    'DCI_Fetch=https://www.drupal.org/files/issues/2716613-69.flag_.permissions-author.patch,modules/flag',
    'DCI_GitCommitHash=24343f9',
    'DCI_JobType=simpletest',
    'DCI_Patch=2716613-69.flag_.permissions-author.patch,modules/flag',
    'DCI_PHPVersion=php-5.6-apache:production',
    'DCI_ProjectType=module',
    'DCI_TestItem=directory:modules/flag',
    'DCI_CS_SkipCodesniff=TRUE',
  ];

  public function testD8Contrib() {

    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
    ], $options);
    $build = $this->getCommand('run')->getBuild();
    $output_file = $build->getXmlDirectory() . "/testresults.xml";
    $this->assertContains('FATAL Drupal\Tests\flag_follower\Kernel\FlagFollowerInstallUninstallTest: test runner returned a non-zero error code (2).', $this->app_tester->getDisplay());
    $this->assertContains('Drupal\flag\Tests\UserFlagTypeTest                            38 passes   6 fails   2 exceptions', $this->app_tester->getDisplay());
    $this->assertEquals(0, $this->app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'Build Successful');
    $this->assertBuildOutputJson($build, 'buildDetails', '');
  }

}
