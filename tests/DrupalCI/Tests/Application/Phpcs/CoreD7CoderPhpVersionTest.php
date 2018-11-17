<?php

namespace DrupalCI\Tests\Application\Phpcs;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;

/**
 * Test that default version config for drupal/coder is compatible with PHP 5.3.
 *
 * @group Application
 * @group phpcs
 *
 * @see TESTING.md
 */
class CoreD7CoderPhpVersionTest extends DrupalCIFunctionalTestBase {

  public function testCoderD7() {

    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.CoreD7Coder.yml',
    ], $options);
    // Assert that Composer didn't tell us we can't install coder.
    $this->assertNotRegExp(
      '/Package drupal\/coder at version .+ has a PHP requirement incompatible with your PHP version/',
      $this->app_tester->getDisplay()
    );
  }

}
