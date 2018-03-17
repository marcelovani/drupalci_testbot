<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;

/**
 * Test that a patched drupalci.yml file overrides the assessment stage.
 *
 * @group Application
 *
 * @see TESTING.md
 */
class CoreD8AddDrupalciYmlTest extends DrupalCIFunctionalTestBase {

  public function testAddsDrupalCi() {
    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.CoreD8AddDrupalciYmlTest.yml',
    ], $options);

    // Make sure we did all the things we want.
    $this->assertRegExp('/2951843_2_adds_drupalci_yml.patch/', $this->app_tester->getDisplay());
    $this->assertRegExp('/Starting update_build/', $this->app_tester->getDisplay());
    $this->assertRegExp('/Replacing build:assessment stage with/', $this->app_tester->getDisplay());
    $this->assertRegExp('/Starting phplint/', $this->app_tester->getDisplay());

    // Make sure that no tests were run.
    $this->assertNotRegExp('/Drupal test run/', $this->app_tester->getDisplay());
    $this->assertNotRegExp('/Tests to be run:/', $this->app_tester->getDisplay());

    $this->assertEquals(0, $this->app_tester->getStatusCode());
  }

}
