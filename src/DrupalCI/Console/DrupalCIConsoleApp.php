<?php

namespace DrupalCI\Console;

use DrupalCI\Providers\ConsoleCommandProvider;
use Symfony\Component\Console\Application;
use Pimple\Container;

class DrupalCIConsoleApp extends Application {

  /**
   * The service container.
   *
   * @var \Pimple\Container
   */
  protected $container;

  public function __construct(Container $container, $name = 'UNKNOWN', $version = 'UNKNOWN') {
    parent::__construct($name, $version);
    $this->container = $container;
    $container->register(new ConsoleCommandProvider());
    $this->addCommands($container['commands']);
    // Explicitly catch exceptions.
    $this->setCatchExceptions(TRUE);
  }

  /**
   * Access the application object's container.
   *
   * @return \Pimple\Container
   */
  public function getContainer() {
    return $this->container;
  }

}
