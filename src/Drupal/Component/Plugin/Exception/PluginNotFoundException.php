<?php

namespace Drupal\Component\Plugin\Exception;

/**
 * Plugin exception class to be thrown when a plugin ID could not be found.
 */
class PluginNotFoundException extends PluginException {

  /**
   * Construct an PluginNotFoundException exception.
   *
   * @param string $plugin_id
   *   The plugin ID that was not found.
   *
   * @param string $message
   * @param int $code
   * @param \Exception $previous
   *
   * @see \Exception for remaining parameters.
   */
  public function __construct($plugin_id, $message = '', $code = 0, \Exception $previous = NULL) {
    if (empty($message)) {
      $message = sprintf("Plugin ID '%s' was not found.", $plugin_id);
    }
    parent::__construct($message, $code, $previous);
  }

}
