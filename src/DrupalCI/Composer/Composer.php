<?php

namespace DrupalCI\Composer;

use Composer\Script\Event;
use PHP_CodeSniffer;

/**
 * Provides static functions for composer script events.
 *
 * @see https://getcomposer.org/doc/articles/scripts.md
 */
class Composer {

  /**
   * Configures phpcs if present.
   *
   * @param \Composer\Script\Event $event
   */
  public static function configurePhpcs(Event $event) {
    // Grab the local repo which tells us what's been installed.
    $local_repository = $event->getComposer()
      ->getRepositoryManager()
      ->getLocalRepository();
    // Make sure both phpcs and coder are installed.
    $phpcs_package = $local_repository->findPackage('squizlabs/php_codesniffer', '*');
    $coder_package = $local_repository->findPackage('drupal/coder', '*');
    if (!empty($phpcs_package) && !empty($coder_package)) {
      $config = $event->getComposer()->getConfig();
      $vendor_dir = $config->get('vendor-dir');
      // Set phpcs' installed_paths config to point to our coder_sniffer
      // directory.
      PHP_CodeSniffer::setConfigData('installed_paths', $vendor_dir . '/drupal/coder/coder_sniffer');
    }
  }

}
