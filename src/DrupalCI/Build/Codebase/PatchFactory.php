<?php

namespace DrupalCI\Build\Codebase;

use DrupalCI\Build\Codebase\Patch;
use Pimple\Container;

/**
 * A service which makes patch objects for you to use.
 */
class PatchFactory implements PatchFactoryInterface {

  /**
   * DrupalCI style object.
   *
   * @var DrupalCI\Console\DrupalCIStyleInterface
   */
  protected $io;

  /**
   * Guzzle client.
   *
   * @var GuzzleHttp\Client
   */
  protected $httpClient;

  public function __construct(Container $container) {
    $this->io = $container['console.io'];
    $this->httpClient = $container['http.client'];
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public function getPatch($patch_details, $ancillary_workspace) {
    return new Patch($this->io, $this->httpClient, $patch_details, $ancillary_workspace);
  }

}
