<?php

namespace DrupalCI\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DrupalCI\Console\DrupalCIStyle;

class ConsoleIOServiceProvider implements ServiceProviderInterface {

  /**
   * The style object to inject.
   *
   * @var \DrupalCI\Console\DrupalCIStyle
   */
  protected $style;

  public function __construct(InputInterface $input, OutputInterface $output) {
    $this->style = new DrupalCIStyle($input, $output);
  }

  public function register(Container $container) {
    $container['console.io'] = $this->style;
  }

}
