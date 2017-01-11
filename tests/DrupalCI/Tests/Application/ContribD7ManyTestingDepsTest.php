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
class ContribD7ManyTestingDepsTest extends DrupalCIFunctionalTestBase {


  public function testContribD7ManyTestingDepsTest() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.ContribD7ManyTestingDepsTest.yml',
    ], $options);
    $this->assertRegExp('/Adding testing \(require-dev\) dependencies./', $app_tester->getDisplay());
    $this->assertEquals(0, $app_tester->getStatusCode());
  }
}
