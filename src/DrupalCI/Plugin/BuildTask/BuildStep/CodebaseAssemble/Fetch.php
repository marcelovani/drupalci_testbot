<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Build\Codebase\CodebaseInterface;
use DrupalCI\Build\Environment\EnvironmentInterface;
use DrupalCI\Console\DrupalCIStyle;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\FileHandlerTrait;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use GuzzleHttp\ClientInterface;
use Pimple\Container;

/**
 * @PluginID("fetch")
 */
class Fetch extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface {

  use FileHandlerTrait;

  /**
   * The Guzzle client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  public static function create(Container $container, array $configuration_overrides = array(), $plugin_id = '', $plugin_definition = array()) {
    return new static(
      $container['http.client'],
      $container['build'],
      $container['codebase'],
      $container['environment'],
      $container['console.io'],
      $container,
      $configuration_overrides,
      $plugin_id,
      $plugin_definition
    );
  }

  public function __construct(
    ClientInterface $http_client,
    BuildInterface $build,
    CodebaseInterface $codebase,
    EnvironmentInterface $environment,
    DrupalCIStyle $io,
    Container $container,
    array $configuration_overrides = array(),
    $plugin_id = '', $plugin_definition = array()
  ) {
    $this->httpClient = $http_client;
    parent::__construct($build, $codebase, $environment, $io, $container, $configuration_overrides, $plugin_id, $plugin_definition);
  }

  /**
   * @inheritDoc
   */
  public function configure() {
    // @TODO make into a test
    // putenv('DCI_Fetch=https://www.drupal.org/files/issues/2796581-region-136.patch,.;https://www.drupal.org/files/issues/another.patch,.');
    if (FALSE !== getenv('DCI_Fetch')) {
      $this->configuration['files'] = $this->process(getenv('DCI_Fetch'));
    }

  }

  /**
   * @inheritDoc
   */
  public function run() {

    $files = $this->configuration['files'];

    if (empty($files)) {
      $this->io->writeln('No files to fetch.');
      return 0;
    }
    foreach ($files as $details) {
      // URL and target directory
      // TODO: Ensure $details contains all required parameters
      if (empty($details['from'])) {
        $this->terminateBuild("Fetch error", "No valid target file provided for fetch command.");
      }
      $url = $details['from'];

      $directory = $this->build->getAncillaryWorkDirectory();
      $info = pathinfo($url);
      try {
        $destination_file = $directory . "/" . $info['basename'];
        $this->httpClient
          ->get($url, ['save_to' => $destination_file]);
      }
      catch (\Exception $e) {
        $this->terminateBuild("Fetch save error", "An error was encountered while attempting to write <info>$url</info> to <info>$destination_file</info>");

      }
      $this->io->writeln("<comment>Fetch of <options=bold>$url</> to <options=bold>$destination_file</> complete.</comment>");
    }
    return 0;
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'files' => [],
    ];
  }

}
