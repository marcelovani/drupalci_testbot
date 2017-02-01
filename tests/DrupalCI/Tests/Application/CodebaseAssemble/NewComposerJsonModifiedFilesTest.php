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
 *
 * @see TESTING.md
 */
class NewComposerJsonModifiedFilesTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */

  public function testD8Contrib() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.NewComposerJsonModifiedFiles.yml',
    ], $options);
    // This test applies a patch which adds a composer.json file to the contrib
    // module under test. Verify that codebase counts the added file as having
    // been modified.
    $codebase = $this->getContainer()['codebase'];
    $this->assertContains(
      $codebase->getTrueExtensionDirectory('modules') . '/composer.json',
      $codebase->getModifiedFiles()
    );
    // Verify the output.
    $display = $app_tester->getDisplay();
    $this->assertRegExp('/.*composer.json changed by patch: recalculating depenendices.*/', $app_tester->getDisplay());
    $this->assertEquals(0, $app_tester->getStatusCode());
  }

}
