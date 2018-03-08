<?php

namespace DrupalCI\Plugin;

use DrupalCI\Plugin\BuildTaskBase;

/**
 * Almost-concrete abstract base class for displaying a message.
 *
 * Subclass this within each namespace where you need a message from the build
 * file.
 *
 * @todo Make it so message.foo: emits 'foo' as the text property.
 */
abstract class MessageBase extends BuildTaskBase {

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    // The only configuration is whether we should halt the build if there's any
    // problem.
    return [
      'text' => 'This is a message',
      'style' => 'comment'
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function run() {
    $this->io->block($this->configuration['text'], NULL, $this->configuration['style']);
  }

  public static function generateDefinition($message, $style = 'comment') {
    return [
      'message.' . md5($message) => [
        'text' => $message,
        'style' => $style,
      ],
    ];
  }

}
