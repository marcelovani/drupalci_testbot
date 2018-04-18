<?php

namespace DrupalCI\Tests;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Providers\ConsoleIOServiceProvider;
use DrupalCI\Providers\DrupalCIServiceProvider;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class DrupalCITestCase extends TestCase {

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $output;

  /**
   * @var \DrupalCI\Build\BuildInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $build;

  public function setUp() {
    $this->output = $this->createMock(OutputInterface::class);
    $this->build = $this->createMock(BuildInterface::class);
  }

  /**
   * Get an application service container.
   *
   * @param string[] $services
   *   Services to inject into the container.
   * @return \Pimple\Container
   *   The container.
   */
  protected function getContainer($services = []) {
    $container = new Container();
    $container->register(new DrupalCIServiceProvider());
    $io_provider = new ConsoleIOServiceProvider(new ArrayInput([]), new NullOutput());
    $container->register($io_provider);
    foreach ($services as $name => $service) {
      $container[$name] = $service;
    }
    return $container;
  }

}
