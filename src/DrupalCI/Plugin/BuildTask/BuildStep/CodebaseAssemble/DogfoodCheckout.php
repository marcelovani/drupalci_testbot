<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;

/**
 * @PluginID("checkout_dogfood")
 *
 * @todo Give this a better name that doesn't mention dogs or food or dogfood.
 */
class DogfoodCheckout extends Checkout implements BuildStepInterface, BuildTaskInterface, Injectable {

  /**
   * {@inheritDoc}
   */
  public function configure() {
    // @todo Get rid of dogfood nomenclature.

    if (isset($_ENV['DCI_DogfoodRepository'])) {
      $repo['repo'] = $_ENV['DCI_DogfoodRepository'];

      if (isset($_ENV['DCI_DogfoodBranch'])) {
        $repo['branch'] = $_ENV['DCI_DogfoodBranch'];
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
  }

}
