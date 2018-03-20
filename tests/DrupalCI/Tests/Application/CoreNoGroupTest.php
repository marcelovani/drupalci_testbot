<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens when running a test that does not have @group set.
 *
 * This test comes from:
 * https://dispatcher.drupalci.org/job/default/92908/
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 *
 * @see TESTING.md
 */
class CoreNoGroupTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_LocalBranch=8.3.x',
    'DCI_UseLocalCodebase=/var/lib/drupalci/drupal-checkout',
    'DCI_DBType=mysql',
    'DCI_DBVersion=5.5',
    'DCI_Fetch=https://www.drupal.org/files/issues/2827218-2-field_denormalize.patch,.',
    'DCI_LocalCommitHash=5d97345',
    'DCI_JobType=simpletest',
    'DCI_PHPVersion=php-5.5.38-apache:production',
    'DCI_Patch=2827218-2-field_denormalize.patch,.',
    'DCI_CS_SkipCodesniff=TRUE',
  ];

  public function testCoreNoGroup() {

    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
    ], $options);
    $foo = $this->app_tester->getDisplay();
    $this->assertRegExp('/.*MissingGroupException.*/', $this->app_tester->getDisplay());
    $this->assertEquals(2, $this->app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'Unable to generate test groups');
    $this->assertBuildOutputJson($build, 'buildDetails', '--- Commands Executed ---
sudo -u www-data php /var/www/html/core/scripts/run-tests.sh --list > /var/lib/drupalci/workdir/simpletest.standard/testgroups.txt
Return Code: 2
--- Output ---
--- Errors ---
exception \'Drupal\simpletest\Exception\MissingGroupException\' with message \'Missing @group annotation in Drupal\Tests\serialization\Kernel\FieldItemSerializationTest\' in /var/www/html/core/modules/simpletest/src/TestDiscovery.php:351
Stack trace:
#0 /var/www/html/core/modules/simpletest/src/TestDiscovery.php(177): Drupal\simpletest\TestDiscovery::getTestInfo(\'Drupal\\\\Tests\\\\se...\', \'/**\n * Test fie...\')
#1 /var/www/html/core/modules/simpletest/simpletest.module(578): Drupal\simpletest\TestDiscovery->getTestClasses(NULL, Array)
#2 /var/www/html/core/scripts/run-tests.sh(76): simpletest_test_get_all(NULL)
#3 {main}
');
  }

}
