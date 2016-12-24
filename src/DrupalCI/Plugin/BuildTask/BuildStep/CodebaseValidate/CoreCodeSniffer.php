<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseValidate;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Build\Environment\EnvironmentInterface;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskException;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * @PluginID("core_phpcs")
 */
class CoreCodeSniffer extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface {

  /**
   *
   * @var \DrupalCI\Build\Environment\EnvironmentInterface
   */
  protected $environment;

  /**
   *
   * @var \DrupalCI\Build\Codebase\CodebaseInterface
   */
  protected $codebase;

  protected $appRoot;

  public function inject(Container $container) {
    parent::inject($container);
    $this->environment = $container['environment'];
    $this->codebase = $container['codebase'];
    $this->appRoot = $container['app.root'];
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'start_directory' => 'core',
      'sniff_fails_test' => FALSE,
      'warning_fails_sniff' => FALSE,
    ];
  }

  /**
   * @inheritDoc
   */
  public function configure() {
    // The start directory is where the phpcs.xml file resides. Relative to the
    // source directory.
    if (isset($_ENV['DCI_CS_SniffStartDirectory'])) {
      $this->configuration['start_directory'] = $_ENV['DCI_CS_SniffStartDirectory'];
    }
    if (isset($_ENV['DCI_CS_SniffFailsTest'])) {
      $this->configuration['sniff_fails_test'] = $_ENV['DCI_CS_SniffFailsTest'];
    }
    if (isset($_ENV['DCI_CS_WarningFailsSniff'])) {
      $this->configuration['warning_fails_sniff'] = $_ENV['DCI_CS_WarningFailsSniff'];
    }
  }

  /**
   * @inheritDoc
   */
  public function run() {
    $this->io->writeln('<info>Running PHP Code Sniffer review on modified files.</info>');

    $modified_files = $this->codebase
      ->getModifiedFilesForNewPath($this->environment->getExecContainerSourceDir());

    if (empty($modified_files)) {
      $this->io->writeln('No modified files for PHPCS.');
      return 0;
    }

    // Note: Core's phpcs.xml should determine what file types get sniffed.
    $sniffable_file = $this->build->getArtifactDirectory() . '/sniffable_files.txt';
    $this->io->writeln("<info>Writing: " . $sniffable_file . "</info>");
    file_put_contents($sniffable_file, implode("\n", $modified_files));

    // Make sure the file was written.
    if (0 < filesize($sniffable_file)) {
      // Set up artifacts.
      $this->build->addArtifact($sniffable_file);
      $report_file = $this->build->getArtifactDirectory() . '/phpcs_checkstyle.xml';
      touch($report_file);
      $this->build->addArtifact($report_file);

      // Figure out executable path and sniff start directory.
      $source_dir = $this->environment->getExecContainerSourceDir();
      $phpcs_bin = $source_dir . '/vendor/bin/phpcs';
      $phpcs_start_dir = $source_dir;
      // Add the start directory from config.
      if (!empty($this->configuration['start_directory'])) {
        $phpcs_start_dir = $source_dir . '/' . $this->configuration['start_directory'];
      }

      // We have to configure phpcs to use drupal/coder. We can't do this during
      // code assemble time because config and path will change under the
      // container.
      // @todo: Remove this after https://www.drupal.org/node/2744463
      $cmd = [
        $phpcs_bin,
        '--config-set installed_paths ' . $this->environment->getExecContainerSourceDir() . '/vendor/drupal/coder/coder_sniffer/',
      ];
      $result = $this->environment->executeCommands(implode(' ', $cmd));
      // Verify that it worked.
      $result = $this->environment->executeCommands("$phpcs_bin -i");

      // Set minimum error level for fail. phpcs uses 1 for warning and 2 for
      // error.
      $minimum_error = 2;
      if ($this->configuration['warning_fails_sniff']) {
        $minimum_error = 1;
      }

      // Execute phpcs.
      $cmd = [
        'cd ' . $phpcs_start_dir . ' &&',
        $phpcs_bin,
        '-ps',
        '--warning-severity=' . $minimum_error,
        '--report-checkstyle=' . $this->environment->getContainerArtifactDir() . '/phpcs_checkstyle.xml',
        '--file-list=' . $this->environment->getContainerArtifactDir() . '/sniffable_files.txt',
      ];
      $this->io->writeln('Executing PHPCS.');
      $result = $this->environment->executeCommands(implode(' ', $cmd));

      // Allow for failing the test run if CS was bad.
      if ($this->configuration['sniff_fails_test']) {
        return $result->getSignal();
      }
    }
    return 0;
  }

}
