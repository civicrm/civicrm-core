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

use Civi\Api4\Generic\Result;

/**
 * @inheritDoc
 *
 * This provides an API for getting the contents of stream css_files - which
 * is useful for the previewer
 */
class GetWithFileContent extends \Civi\Api4\Generic\DAOGetAction {

  /**
   * @inheritdoc
   */
  protected function getSelect(): array {
    $selected = parent::getSelect();

    // if no select is specified, will default to * which includes
    // the fields we need
    if (!$selected) {
      return $selected;
    }
    // if caller has specified certain fields, we need to add the few
    // we always need
    return array_unique(array_merge([
      'extension', 'file_prefix',
      'css_file', 'css_file_dark',
    ], $selected));
  }

  /**
   * @inheritdoc
   */
  public function _run(Result $result): void {
    $getResult = \Civi\Api4\RiverleaStream::get($this->checkPermissions)
      ->setSelect($this->getSelect())
      ->setLimit($this->getLimit())
      ->execute();

    // for each upstream result, add the file content then add to our final result
    foreach ($getResult as $stream) {
      $stream['css_file_content'] = self::getFileContent($stream['css_file'], $stream['extension'], $stream['file_prefix'] ?? NULL);
      $stream['css_file_dark_content'] = self::getFileContent($stream['css_file_dark'], $stream['extension'], $stream['file_prefix'] ?? NULL);
      $result[] = $stream;
    }
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
