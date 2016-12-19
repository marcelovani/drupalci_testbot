<?php

/**
 * @file
 * Console application for Drupal CI.
 */

namespace DrupalCI\Console;

use DrupalCI\Injectable;
use DrupalCI\Providers\ConsoleCommandProvider;
use Symfony\Component\Console\Application;
use Pimple\Container;

class DrupalCIConsoleApp extends Application implements Injectable {

  /**
   * The service container.
   *
   * @var \Pimple\Container
   */
  protected $container;

  public function inject(Container $container) {
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
