<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;


use DrupalCI\Build\BuildInterface;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\FileHandlerTrait;
use DrupalCI\Plugin\PluginBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use GuzzleHttp\Client;
use Pimple\Container;

/**
 * @PluginID("fetch")
 */
class Fetch extends PluginBase implements BuildStepInterface, BuildTaskInterface, Injectable {

  use FileHandlerTrait;

  public function inject(Container $container) {
    parent::inject($container);
  }

  /**
   * @inheritDoc
   */
  public function configure() {
    // @TODO make into a test
     // $_ENV['DCI_Fetch']='https://www.drupal.org/files/issues/2796581-region-136.patch,.;https://www.drupal.org/files/issues/another.patch,.';
    if (isset($_ENV['DCI_Fetch'])) {
      $this->configuration['files'] = $this->process($_ENV['DCI_Fetch']);
    }
  }

  /**
   * @inheritDoc
   */
  public function run() {

    $files = $this->configuration['files'];

    if (empty($files)) {
      $this->io->writeln('No files to fetch.');
    }
    foreach ($files as $details) {
      // URL and target directory
      // TODO: Ensure $details contains all required parameters
      if (empty($details['from'])) {
        $this->io->drupalCIError("Fetch error", "No valid target file provided for fetch command.");

        return;
      }
      $url = $details['from'];
      $source_dir = $this->build->getSourceDirectory();
      $fetchdir = (!empty($details['to'])) ? $details['to'] : $source_dir;
      if (!($directory = $this->validateDirectory($source_dir, $fetchdir))) {
        // Invalid checkout directory
        $this->io->drupalCIError("Fetch error", "The fetch directory <info>$directory</info> is invalid.");

        return;
      }
      $info = pathinfo($url);
      try {
        $destination_file = $directory . "/" . $info['basename'];
        $this->httpClient()
          ->get($url, ['save_to' => $destination_file]);
      }
      catch (\Exception $e) {
        $this->io->drupalCIError("Write error", "An error was encountered while attempting to write <info>$url</info> to <info>$destination_file</info>");

        return;
      }
      $this->io->writeln("<comment>Fetch of <options=bold>$url</options=bold> to <options=bold>$destination_file</options=bold> complete.</comment>");
    }
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'files' => [],
    ];
  }

  /**
   * @inheritDoc
   */
  public function getArtifacts() {
    // TODO: Implement getArtifacts() method.
  }

  /**
   * @return \GuzzleHttp\ClientInterface
   */
  protected function httpClient() {
    if (!isset($this->httpClient)) {
      $this->httpClient = new Client();
    }
    return $this->httpClient;
  }


}
