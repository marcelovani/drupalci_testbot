<?php

/**
 * @file
 * Contains \DrupalCI\Tests\Plugin\BuildSteps\setup\CheckoutTest.
 */

namespace DrupalCI\Tests\Plugin\BuildSteps\setup;


use DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble\Checkout;
use DrupalCI\Tests\DrupalCITestCase;

class CheckoutTest extends DrupalCITestCase {

  public function testRunGitCheckout() {
    $dir = 'test/dir';
    $data = [
      'repositories' => [
        [
          'protocol' => 'git',
          'repo' => 'https://git.drupal.org/project/drupal.git',
          'branch' => '8.0.x',
          'checkout_dir' => $dir,
          'depth' => 1,
        ]
      ],
    ];
    $checkout = new TestCheckout($data);
    $checkout->inject($this->getContainer());
    $checkout->setValidate($dir);
    $checkout->setExecResult(0);
    $checkout->run();
    $this->assertSame(['git clone -b 8.0.x --depth 1 https://git.drupal.org/project/drupal.git \'test/dir\'','cd \'test/dir\' && git log --oneline -n 1 --decorate'], $checkout->getCommands());
  }
}

class TestCheckout extends Checkout {
  use TestSetupBaseTrait;
}
