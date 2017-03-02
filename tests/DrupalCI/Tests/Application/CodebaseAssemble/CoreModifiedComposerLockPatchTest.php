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
    /* @var \DrupalCI\Build\BuildInterface $build */
    $build = $this->getCommand('run')->getBuild();

    $codebase = $this->getContainer()['codebase'];
    $this->assertContains('composer.lock',
      $codebase->getModifiedFiles()
    );

    $installed_json = json_decode(file_get_contents($build->getArtifactDirectory() . '/codebase/composer-installed.json'), TRUE);
    foreach ($installed_json as $package) {
      if ($package['name'] == "symfony/class-loader") {
        // Allow for different patchlevels by manipulating the version string.
        $this->assertEquals('v2.8.', substr($package['version'], 0, 5));
      }
    }

    $this->assertEquals(0, $app_tester->getStatusCode());
  }

}
