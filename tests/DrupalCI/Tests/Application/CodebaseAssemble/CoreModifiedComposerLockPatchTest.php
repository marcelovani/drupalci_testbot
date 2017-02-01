<?php

namespace DrupalCI\Tests\Application\CodebaseAssembly;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Basic test that proves that drupalci can execute a simpletest and generate a result
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 * @group docker
 *
 * @see TESTING.md
 */
class CoreModifiedComposerLockPatchTest extends DrupalCIFunctionalTestBase {

  public function testBasicTest() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.CoreModifiedComposerLockPatchTest.yml',
    ], $options);
    $build = $this->getCommand('run')->getBuild();

    $codebase = $this->getContainer()['codebase'];
    $this->assertContains('composer.lock',
      $codebase->getModifiedFiles()
    );

    // TODO: [0] is very brittle. We need to make build artifacts have labels.
    // See: https://www.drupal.org/node/2842547

    $build_artifacts = $build->getBuildArtifacts();
    $installed_json = json_decode(file_get_contents($build_artifacts[0]->getArtifactPath()), TRUE);
    foreach ($installed_json as $package) {
      if ($package['name'] == "symfony/class-loader") {
        $this->assertEquals('v2.8.15', $package['version']);
      }
    }

    $this->assertEquals(0, $app_tester->getStatusCode());
  }

}
