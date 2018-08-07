<?php

namespace DrupalCI\Tests\Plugin;

use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Providers\ConsoleIOServiceProvider;
use DrupalCI\Providers\DrupalCIServiceProvider;
use DrupalCI\Tests\DrupalCITestCase;
use Pimple\Container;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @group Plugin
 *
 * @coversDefaultClass \DrupalCI\Plugin\BuildTaskBase
 */
class BuildTaskBaseTest extends DrupalCITestCase {

  /**
   * Make sure BuildTaskBase tells the user about invalid config.
   *
   * @covers ::override_config
   */
  public function testOverrideConfig() {
    // Get an output we can inspect later.
    $output = new BufferedOutput();

    // Set up the container.
    $container = new Container();
    $container->register(new DrupalCIServiceProvider());
    $io_provider = new ConsoleIOServiceProvider(new ArrayInput([]), $output);
    $container->register($io_provider);

    // Invalid config overrides.
    $config_overrides = [
      'invalid_config' => 'foo',
      'valid_config' => 'bar',
    ];

    $base = new BaseTestImplementation($config_overrides, 'test_plugin', [], $container);

    $this->assertContains('The following configuration for test_plugin are invalid: invalid_config', $output->fetch());
  }

}

/**
 * Stub build task implementation with valid config.
 */
class BaseTestImplementation extends BuildTaskBase {

  public function getDefaultConfiguration() {
    return [
      'valid_config' => 'something',
    ];
  }

}
