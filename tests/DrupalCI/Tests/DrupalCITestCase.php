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
