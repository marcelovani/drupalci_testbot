<?php

namespace DrupalCI\Tests\Plugin;

use DrupalCI\Tests\DrupalCITestCase;
use DrupalCI\Plugin\BuildTask\BuildTaskException;
use GuzzleHttp\ClientInterface;
use DrupalCI\Plugin\BuildTaskBase;
use Pimple\Container;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use DrupalCI\Providers\ConsoleIOServiceProvider;
use DrupalCI\Providers\DrupalCIServiceProvider;

/**
 * @group Plugin
 *
 * @coversDefaultClass \DrupalCI\Plugin\BuildTaskBase
 */
class BuildTaskBaseTest extends DrupalCITestCase {

  /**
   * The container.
   *
   * @var \Pimple\Container
   */
  protected $container;

  public function setUp() {
    parent::setUp();

    $container = new Container();
    $container->register(new DrupalCIServiceProvider());
    $io_provider = new ConsoleIOServiceProvider(new ArrayInput([]), new BufferedOutput());
    $container->register($io_provider);
    $this->container = $container;
  }

  /**
   * @covers ::override_config
   */
  public function testOverrideConfig() {
    $config_overrides = [
      'invalid_config' => 'foo',
      'valid_config' => 'bar',
    ];

    $base = new BaseTestImplementation($config_overrides, 'plugin_id', [], $this->container);
  }

}

class BaseTestImplementation extends BuildTaskBase {

  public function getDefaultConfiguration() {
    return [
      'valid_config' => 'something',
    ];
  }

}
