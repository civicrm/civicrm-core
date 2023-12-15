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
      if (file_exists(rtrim($path, '/*'))) {
        $orphans[] = [
          'name' => $file,
          'path' => $path,
        ];
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
