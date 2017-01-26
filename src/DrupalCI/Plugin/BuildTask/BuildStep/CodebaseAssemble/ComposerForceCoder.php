<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;

/**
 * Special case for adding coder/phpcs to core's requirements.
 *
 * @PluginID("composer_force_coder")
 *
 * @todo We allow for forcing this requirement in order to prove that
 *   coder/phpcs works in the testbot. Remove this for
 *   https://www.drupal.org/node/2744463
 */
class ComposerForceCoder extends Composer implements BuildStepInterface, BuildTaskInterface {

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfiguration() {
    return array_merge(
      parent::getDefaultConfiguration(),
      [
        'force_coder_version' => '8.2.8',
        'force_coder_install' => TRUE,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function configure() {
    parent::configure();
    if (false !== getenv(('DCI_Composer_ForceCoderVersion'))) {
      $this->configuration['force_coder_version'] = getenv(('DCI_Composer_ForceCoderVersion'));
    }
    if (false !== getenv(('DCI_Composer_ForceCoderInstall'))) {
      $this->configuration['force_coder_install'] = getenv(('DCI_Composer_ForceCoderInstall'));
    }
  }

  /**
   * {@inheritDoc}
   */
  public function run() {
    // Parent runs composer install.
    parent::run();

    // Add coder/phpcs if required.
    if ($this->configuration['force_coder_install']) {
      // Note: We don't configure phpcs to use coder sniffs here because the
      // sniff paths will be different under the PHP container.
      $cmd = './bin/composer require --dev drupal/coder ' . $this->configuration['force_coder_version'] . ' --working-dir ' . $this->codebase->getSourceDirectory();
      $this->io->writeln('Adding drupal/coder ' . $this->configuration['force_coder_version']);
      $this->exec($cmd, $output, $return_var);
    }
  }

}
