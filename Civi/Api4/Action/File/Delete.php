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

namespace Civi\Api4\Action\File;

/**
 * @inheritDoc
 */
class Delete extends \Civi\Api4\Generic\DAODeleteAction {

  /**
   * Whether to remove the file from the filesystem.
   *
   * If FALSE, only the row in the `civicrm_file` table will be deleted and the filesystem will not be touched.
   *
   * @var bool
   */
  protected $deleteFile = TRUE;

  protected function deleteObjects($items): array {
    $result = [];
    foreach (\CRM_Core_BAO_File::deleteRecords($items) as $instance) {
      $result[] = ['id' => $instance->id];
    }
    if ($this->deleteFile) {
      foreach ($items as $item) {
        $path = \CRM_Core_Config::singleton()->customFileUploadDir . $item['uri'];
        if ($item['uri'] && file_exists($path)) {
          unlink($path);
        }
      }
    }
    return $result;
  }

  protected function getSelect(): array {
    return ['id', 'uri'];
  }

}
