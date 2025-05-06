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
 *
 * This provides an API for getting the contents of stream css_files - which
 * is useful for the previewer
 */
class GetWithFileContent extends \Civi\Api4\Generic\BasicBatchAction {

  /**
   * @inheritdoc
   */
  protected function getSelect(): array {
    return [
      'name', 'label', 'description', 'is_reserved',
      'extension', 'file_prefix',
      'css_file', 'css_file_dark',
      'vars', 'vars_dark',
      'custom_css', 'custom_css_dark',
    ];
  }

  /**
   * @inheritdoc
   */
  protected function doTask($stream): array {
    $stream['css_file_content'] = self::getFileContent($stream['css_file'], $stream['extension'], $stream['file_prefix'] ?? NULL);
    $stream['css_file_dark_content'] = self::getFileContent($stream['css_file_dark'], $stream['extension'], $stream['file_prefix'] ?? NULL);
    return $stream;
  }

  /**
   * @return string
   */
  public static function getFileContent(?string $path, ?string $extension, ?string $filePrefix): string {
    if (!$path || !$extension) {
      return '';
    }
    if ($filePrefix) {
      $path = $filePrefix . '/' . $path;
    }

    // fix extra slashes
    $path = str_replace('//', '/', $path);
    // guard against path traversal
    $path = str_replace('..', '', $path);

    // get the full path based on the stream extension
    $finalPath = \Civi::resources()->getPath($extension, $path);

    if (is_file($finalPath)) {
      // File exists and is a file? Return it!
      return file_get_contents($finalPath) ?? '';
    }
    return '';
  }

}
