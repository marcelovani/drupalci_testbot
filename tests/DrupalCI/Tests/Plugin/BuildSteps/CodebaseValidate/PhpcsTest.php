<?php

namespace DrupalCI\Tests\Plugin\BuildSteps\CodebaseValidate;

use DrupalCI\Plugin\BuildTask\BuildStep\CodebaseValidate\Phpcs;

class PhpcsTest extends \PHPUnit_Framework_TestCase {

  /**
   * Ensure the defaults are the right ones according to policy.
   */
  public function testPolicy() {
    $phpcs = new Phpcs();
    $default_config = $phpcs->getDefaultConfiguration();
    // Ensure that, by default, phpcs will not interrupt testing.
    $this->assertEquals(FALSE, $default_config['sniff_fails_test']);
    // Ensure that by default phpcs will only sniff changed files or none at
    // all.
    $this->assertEquals(TRUE, $default_config['sniff_only_changed']);
  }
  
}
