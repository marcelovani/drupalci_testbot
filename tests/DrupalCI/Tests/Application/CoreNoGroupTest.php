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

  public function testCoreNoGroup() {

    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.CoreNoGroupTest.yml',
    ], $options);
    $foo = $this->app_tester->getDisplay();
    $this->assertRegExp('/.*MissingGroupException.*/', $this->app_tester->getDisplay());
    $this->assertEquals(2, $this->app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'Unable to generate test groups');
    $this->assertBuildOutputJson($build, 'buildDetails', '--- Commands Executed ---
sudo -u www-data php /var/www/html/core/scripts/run-tests.sh --list > /var/lib/drupalci/workdir/run_tests.standard/testgroups.txt
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
