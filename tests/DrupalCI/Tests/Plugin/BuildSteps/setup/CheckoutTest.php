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
    $checkout = TestCheckout::create($this->getContainer(), $data);
    $checkout->setValidate($dir);
    $checkout->setExecResult(0);
    $checkout->run();
    $commands = $checkout->getCommands();
    $this->assertSame(['git clone -b 8.0.x --depth 1 https://git.drupal.org/project/drupal.git \'test/dir\' 2>&1', 'cd \'test/dir\' && git log --oneline -n 1 --decorate'], $checkout->getCommands());
  }

  public function testEnvironmentalVariables() {
    // Load up some environmental variables.
    $env_variables = [
      'DCI_Checkout_Repo=repo',
      'DCI_Checkout_Branch=branch',
      'DCI_Checkout_Hash=commit_hash',
    ];
    foreach ($env_variables as $variable) {
      putenv($variable);
    }

    // Make a checkout plugin object. The constructor calls configure(), which
    // pulls in the env variables.
    $checkout = Checkout::create($this->getContainer());
    // Get access to the configuration.
    $ref_configuration = new \ReflectionProperty($checkout, 'configuration');
    $ref_configuration->setAccessible(TRUE);
    $configuration = $ref_configuration->getValue($checkout);
    $configuration = $configuration['repositories'];
    // Test.
    foreach ($env_variables as $value) {
      $value = substr($value,strpos($value,"=") + 1);
      $this->assertEquals($value, $configuration[0][$value]);
    }
    // Unset environmental variables.
    foreach ($env_variables as $key => $value) {
      unset($_ENV[$key]);
    }
  }

}

class TestCheckout extends Checkout {
  use TestSetupBaseTrait;

}
