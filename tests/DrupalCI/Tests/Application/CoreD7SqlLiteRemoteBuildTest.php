<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens a contrib module has no tests.
 *
 * This test depends on the drupalci_d8_no_tests module which you can find here:
 * https://www.drupal.org/sandbox/mile23/2683655
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 *
 * @see TESTING.md
 */
class CoreD7SqlLiteRemoteBuildTest extends DrupalCIFunctionalTestBase {

  public function testCoreD7SqlLiteRemoteBuildTest() {
    $this->markTestIncomplete('Remote build definition file is now 404.');
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
      'definition' => 'https://dispatcher.drupalci.org/job/default/306055/artifact/jenkins-default-306055/artifacts/build.jenkins-default-306055.yml',
    ], $options);
    $display = $app_tester->getDisplay();
    $this->assertRegExp('!Build downloaded to /var/lib/drupalci/workspace/build.jenkins-default-306055.yml!', $app_tester->getDisplay());
    $this->assertRegExp('!cd /var/www/html && sudo -u www-data DRUSH_NO_MIN_PHP=1 /usr/local/bin/drush -r /var/www/html si -y --db-url=sqlite://sites/default/files/db.sqlite --clean-url=0 --account-name=admin --account-pass=drupal --account-mail=admin@example.com!', $app_tester->getDisplay());
    $this->assertRegExp('!Enable/disable hidden submodules and dependencies 120 passes, 4 fails, and 0 exceptions!', $app_tester->getDisplay());
    $this->assertEquals(0, $app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $app->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'Build Successful');
    $this->assertBuildOutputJson($build, 'buildDetails', '');
  }

}
