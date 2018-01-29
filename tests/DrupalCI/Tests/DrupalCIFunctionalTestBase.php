<?php

namespace DrupalCI\Tests;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Providers\DrupalCIServiceProvider;
use PHPUnit\Framework\TestCase;
use Pimple\Container;

/**
 * Framework for test-controlled runs of drupalci.
 *
 * You can specify DCI_* config values by overriding self::$dciConfig.
 */
abstract class DrupalCIFunctionalTestBase extends TestCase {

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
        putenv($variable);
      }
    }
    else {
      // TODO: if there isnt config *or* a build definition yml specified, then
      // we should complain
      // Complain if there is no config.
      // throw new \PHPUnit_Framework_Exception('You must provide ' . get_class($this) . '::$dciConfig.');
    }
    $build_id = putenv('BUILD_TAG');

    $app = $this->getConsoleApp();
    $app->setAutoExit(FALSE);
  }

  /**
   * Assert buildoutcome.json contains an attribute/value pair.
   *
   * @param \DrupalCI\Build\BuildInterface $build
   *   The build to look inside.
   * @param string $attribute
   *   The attribute to look for.
   * @param mixed $value
   *   The value for the attribute.
   */
  protected function assertBuildOutputJson(BuildInterface $build, $attribute, $value) {
    $buildoutcome_json = $build->getArtifactDirectory() . '/buildoutcome.json';
    $this->assertTrue(file_exists($buildoutcome_json));
    $buildoutcome = json_decode(file_get_contents($buildoutcome_json));
    $this->assertEquals($value, $buildoutcome->$attribute);
  }

  /**
   * Assert buildoutcome.json has an attribute containing some text.
   *
   * @param \DrupalCI\Build\BuildInterface $build
   *   The build to look inside.
   * @param string $attribute
   *   The attribute to look for.
   * @param mixed $fragment
   *   The fragment to find within the attribute.
   */
  protected function assertBuildOutputJsonContains(BuildInterface $build, $attribute, $fragment) {
    $buildoutcome_json = $build->getArtifactDirectory() . '/buildoutcome.json';
    $this->assertTrue(file_exists($buildoutcome_json));
    $buildoutcome = json_decode(file_get_contents($buildoutcome_json));
    $this->assertContains($fragment, $buildoutcome->$attribute);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();
    // Complain if there is no config.
    if (!empty($this->dciConfig)) {
      // Ensure anything set by this test doesnt leak into the next.
      foreach ($this->dciConfig as $variable) {
        list($env_var, $value) = explode('=', $variable);
        putenv($env_var);
      }
    }
  }

}
