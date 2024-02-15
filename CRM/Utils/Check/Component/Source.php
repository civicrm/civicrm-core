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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Check_Component_Source extends CRM_Utils_Check_Component {

  public function getRemovedFiles() {
    $dataSource = Civi::paths()->getPath('[civicrm.root]/deleted-files-list.json');
    return json_decode(file_get_contents($dataSource), TRUE);
  }

  /**
   * @return array
   *   Each item is an array with keys:
   *     - name: string, an abstract name
   *     - path: string, a full file path
   */
  public function findOrphanedFiles() {
    $orphans = [];
    foreach ($this->getRemovedFiles() as $file) {
      $path = Civi::paths()->getPath("[civicrm.root]/$file");
      $path = rtrim($path, '/*');
      // On case-insensitive filesystems we need to do some more work
      $actualPath = realpath($path);
      if ($actualPath !== FALSE) {
        $actualPath = str_replace(DIRECTORY_SEPARATOR, '/', $actualPath);
        // At this point we know the file/dir exists because otherwise realpath would have returned false. So we can compare $file to the ending of $actualPath to get a case-sensitive match because realpath has the same sense (sensitivity? bigness of the letters?) as the file in the filesystem.
        $fileWithoutStar = rtrim($file, '/*');
        if (substr($actualPath, -1 * strlen($fileWithoutStar)) === $fileWithoutStar) {
          $orphans[] = [
            'name' => $file,
            'path' => $path,
          ];
        }
      }
    }

    return $orphans;
  }

  /**
   * @return CRM_Utils_Check_Message[]
   */
  public function checkOrphans() {
    $orphans = $this->findOrphanedFiles();
    if (empty($orphans)) {
      return [];
    }

    $messages = [];
    $messages[] = new CRM_Utils_Check_Message(
      __FUNCTION__,
      ts('The local system includes old files which should not exist:') .
        '<ul><li>' . implode('</li><li>', array_column($orphans, 'path')) . '</li></ul>',
      ts('Old files'),
      \Psr\Log\LogLevel::WARNING,
      'fa-server'
    );

    return $messages;
  }

}
