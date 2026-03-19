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

trait FileSaveTrait {

  /**
   * @inheritDoc
   */
  protected function write(array $items) {
    foreach ($items as &$file) {
      // In update mode, the uri cannot be changed.
      if (!empty($file['id']) && isset($file['uri'])) {
        $existingUri = \CRM_Core_DAO_File::getDbVal('uri', $file['id']);
        if ($existingUri !== $file['uri']) {
          throw new \CRM_Core_Exception("Uri of existing file cannot be changed.");
        }
        unset($file['uri']);
      }
      // In create mode, uri cannot be set.
      if (isset($file['uri'])) {
        throw new \CRM_Core_Exception("Setting file URI is not permitted. Use file_name instead.");
      }
      if (empty($file['id']) && !empty($file['file_name'])) {
        $file['uri'] = $this->makeFileUri($file['file_name']);
        if (!empty($file['move_file'])) {
          if ($this->getCheckPermissions()) {
            throw new \CRM_Core_Exception("The move_file option is only allowed in trusted operations. Set checkPermissions=0 to enable move_file.");
          }
          $path = \CRM_Core_BAO_File::getFilePath($file);
          if (!copy($file['move_file'], $path)) {
            throw new \CRM_Core_Exception("Cannot copy uploaded file {$file['move_file']} to $path");
          }
          unlink($file['move_file']);
        }
      }
      if (!empty($file['content'])) {
        $path = \CRM_Core_BAO_File::getFilePath($file);
        file_put_contents($path, $file['content']);
      }
    }
    return \CRM_Core_BAO_File::writeRecords($items);
  }

  private function makeFileUri($fileName) {
    if ($fileName != basename($fileName) || preg_match(':[/\\\\]:', $fileName)) {
      throw new \CRM_Core_Exception('Malformed name');
    }
    return \CRM_Utils_File::makeFileName($fileName);
  }

}
