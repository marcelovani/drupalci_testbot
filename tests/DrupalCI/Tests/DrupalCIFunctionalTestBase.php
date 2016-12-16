<?php

namespace DrupalCI\Tests;

use DrupalCI\Providers\DrupalCIServiceProvider;
use Pimple\Container;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Framework for test-controlled runs of drupalci.
 *
 * You can specify DCI_* config values by overriding self::$dciConfig.
 */
abstract class DrupalCIFunctionalTestBase extends \PHPUnit_Framework_TestCase {

  /**
   * DCI_* configuration for this test run.
   *
   * These values will be initialized using the config:set command.
   *
   * Override this array with your own config sets and settings.
   *
   * @code
   * [
   *   'DCI_JobType=simpletest',
   *   'DCI_CoreBranch=8.1.x',
   * ]
   * @endcode
   *
   * @var string[]
   */
  protected $dciConfig;

  /**
   * The service container.
   *
   * @var \Pimple\Container
   */
  protected $container;

  /**
   * @return \Pimple\Container
   */
  protected function getContainer() {
    if (empty($this->container)) {
      $this->container = new Container();
      $this->container->register(new DrupalCIServiceProvider());
    }
    return $this->container;
  }

  /**
   * @return \DrupalCI\Console\DrupalCIConsoleApp
   */
  protected function getConsoleApp() {
    $container = $this->getContainer();
    return $container['console'];
  }

  /**
   * Find a console command.
   *
   * @param string $name
   *
   * @return \Symfony\Component\Console\Command\Command
   *   The command you seek.
   *
   * @throws \InvalidArgumentException When command name is incorrect or
   *   ambiguous.
   */
  protected function getCommand($name) {
    $app = $this->getConsoleApp();
    return $app->find($name);
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    if (!empty($this->dciConfig)) {
      foreach ($this->dciConfig as $variable) {
        list($env_var, $value) = explode('=', $variable);
        $_ENV[$env_var] = $value;
      }
    }
    else {
      // TODO: if there isnt config *or* a build definition yml specified, then
      // we should complain
      // Complain if there is no config.
      // throw new \PHPUnit_Framework_Exception('You must provide ' . get_class($this) . '::$dciConfig.');
    }

    $app = $this->getConsoleApp();
    $app->setAutoExit(FALSE);
  }

}
