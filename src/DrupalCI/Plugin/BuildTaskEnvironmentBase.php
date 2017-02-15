<?php

namespace DrupalCI\Plugin;

use DrupalCI\Build\Environment\EnvironmentInterface;
use DrupalCI\Plugin\BuildTaskBase;
use Pimple\Container;

/**
 * Base class for plugins that need access to the environment service.
 *
 * This base class is only really useful after the containers have been started.
 */
abstract class BuildTaskEnvironmentBase extends BuildTaskBase {

  /**
   * The testing environment.
   *
   * @var \DrupalCI\Build\Environment\EnvironmentInterface
   */
  protected $environment;

  public function inject(Container $container) {
    parent::inject($container);
    $this->environment = $container['environment'];
  }

}
