<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseValidate;

/**
 * @PluginID("core_coder_sniff")
 */
class CoreCoderSniff extends Phpcs {

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'start_directory' => 'core/',
      'installed_paths' => '/vendor/drupal/coder/coder_sniffer/',
      'sniff_fails_test' => FALSE,
      'warning_fails_sniff' => FALSE,
    ];
  }

}
