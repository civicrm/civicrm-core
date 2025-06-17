<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Api4\Action\RiverleaStream;

/**
 * @inheritDoc
 */
class Render extends \Civi\Api4\Generic\BasicBatchAction {

  /**
   * @var bool
   *
   */
  protected bool $isFrontend = FALSE;

  /**
   * @var string
   *
   * How to handle dark mode when rendering
   *
   * By default this will be set based on isFrontend and the stream/global
   * dark mode settings
   *
   * However it can set explicitly to 'dark', 'light', 'inherit' when previewing
   */
  protected string $darkMode = '';

  /**
   * @inheritdoc
   */
  protected function getSelect(): array {
    return [
      'name', 'label',
      'extension', 'file_prefix',
      'css_file', 'css_file_dark',
      'vars', 'vars_dark',
      'custom_css', 'custom_css_dark',
      'dark_frontend', 'dark_backend',
    ];
  }

  /**
   * @inheritdoc
   *
   * Compile the dynamic css for a stream
   */
  protected function doTask($stream): array {
    $content = [];

    // if dark mode is not set explicitly, we derive it
    // based on frontend/backend settings, stream level settings, global dark mode settings
    if (!$this->darkMode) {
      $this->darkMode = $this->getDarkModeDefault($stream);
    }

    $content[] = self::concatStreamCss($stream);

    switch ($this->darkMode ?? NULL) {
      case 'light':
        // tell OS we want light for system elements
        $content[] = ":root { color-scheme: light; }";
        break;

      case 'dark':
        // tell OS we want dark for system elements
        $content[] = ":root { color-scheme: dark; }";
        // add stream dark unconditionally
        $content[] = self::concatStreamCss($stream, TRUE);
        break;

      case 'inherit':
      default:
        // tell OS we are happy with light or dark for system elements
        $content[] = ":root { color-scheme: light dark; }";
        // add stream dark vars wrapped inside a media query
        $content[] = '@media (prefers-color-scheme: dark) {';
        $content[] = self::concatStreamCss($stream, TRUE);
        $content[] = '}';
        break;
    }

    return [
      'content' => implode("\n", $content),
    ];
  }

  private function getDarkModeDefault(array $stream) {
    if ($this->isFrontend) {
      return $stream['dark_frontend'] ?? \Civi::settings()->get('riverlea_dark_mode_frontend');
    }
    else {
      return $stream['dark_backend'] ?? \Civi::settings()->get('riverlea_dark_mode_backend');
    }
  }

  /**
   * This replaces stream meta with its dark version, ready to pass
   * to concatStreamCss / getStreamCssFromFile
   */
  private static function getDarkStream(array $stream): array {
    $stream['label'] = $stream['label'] . " Dark Styles";
    $stream['vars'] = $stream['vars_dark'];
    $stream['css_file'] = $stream['css_file_dark'];
    $stream['custom_css'] = $stream['custom_css_dark'];

    return $stream;
  }

  /**
   * @param array $stream the stream meta
   * @param bool $darkMode whether to use the regular stream meta, or dark version
   *
   * @return string
   */
  private static function concatStreamCss(array $stream, bool $darkMode = FALSE): string {
    if ($darkMode) {
      $stream = self::getDarkStream($stream);
    }

    $content = [];

    if ($stream['css_file']) {
      $content[] = "/* {$stream['label']} file: {$stream['css_file']} */";
      $content[] = self::getStreamCssFromFile($stream);
    }

    if ($stream['vars']) {
      $content[] = "/* {$stream['label']} vars */";

      $content[] = ":root {";
      foreach ($stream['vars'] as $var => $value) {
        $content[] = "{$var}: {$value};";
      }
      $content[] = "}";
    }

    if ($stream['custom_css']) {
      $content[] = "/* {$stream['label']} custom css */";
      $content[] = $stream['custom_css'];
    }

    return implode("\n", $content);
  }

  /**
   * @return string
   */
  private static function getStreamCssFromFile(array $stream): string {
    return GetWithFileContent::getFileContent($stream['css_file'], $stream['extension'], $stream['file_prefix']);
  }

}
