<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;


use DrupalCI\Build\BuildInterface;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskException;
use DrupalCI\Plugin\BuildTask\FileHandlerTrait;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * @PluginID("checkout_core")
 */
class DrupalCoreCheckout extends Checkout implements BuildStepInterface, BuildTaskInterface, Injectable {

  /**
   * @inheritDoc
   */
  public function configure() {

    if (isset($_ENV['DCI_CoreRepository'])) {
      $repo['repo'] = $_ENV['DCI_CoreRepository'];

      if (isset($_ENV['DCI_CoreBranch'])) {
        $repo['branch'] = $_ENV['DCI_CoreBranch'];
      }
      if (isset($_ENV['DCI_GitCommitHash'])) {
        $repo['commit_hash'] = $_ENV['DCI_GitCommitHash'];
      }
      $this->configuration['repositories'][0] = $repo;
    }
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {

    return [
      'repositories' => [
        [
          'repo' => '',
          'branch' => '',
          'commit_hash' => '',
          'checkout_dir' => '',
        ]
      ],
    ];
  }

  public function run() {

    if (!empty($this->configuration['repositories'][0]['repo'])) {
      $core_dir = $this->configuration['repositories'][0]['checkout_dir'] = $this->codebase->getSourceDirectory();
      parent::run();
    }
    $this->codebase->setExtensionPaths($this->discoverExentionPaths());
  }

  protected function discoverExentionPaths(){
    $extension_paths = [];
    $core_dir = $this->codebase->getSourceDirectory();

    $composer_json = $core_dir . '/composer.json';
    if (file_exists($composer_json)) {
      $composer_config = json_decode(file_get_contents($composer_json), TRUE);
      if (isset($composer_config['extra']['installer-paths'])) {
        $paths = $composer_config['extra']['installer-paths'];
        foreach ($paths as $path => $config){
          // Special case for core.
          if ($path == 'core') {
            continue;
          }
          $pathcomponents = explode("/", $path);
          array_pop($pathcomponents);
          $extension_paths[$pathcomponents[0]] = implode($pathcomponents, '/');
        }
      } else {
        // Older version of core (pre dec 6, 2016) that used the installer paths
        // from the composer/installers plugin.
        $extension_paths = ['modules' => 'modules',
                            'themes' => 'themes',
                            'profiles' => 'profiles',
                            ];
      }
    }
    return $extension_paths;

  }



}
