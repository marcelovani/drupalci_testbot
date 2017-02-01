<?php

namespace DrupalCI\Tests;

use DrupalCI\Providers\ConsoleIOServiceProvider;
use DrupalCI\Providers\DrupalCIServiceProvider;
use Pimple\Container;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DrupalCITestCase extends \PHPUnit_Framework_TestCase {

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $output;

  /**
   * @var \DrupalCI\Build\BuildInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $build;

  public function setUp() {
    $this->output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
    $this->build = $this->getMock('DrupalCI\Build\BuildInterface');
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
