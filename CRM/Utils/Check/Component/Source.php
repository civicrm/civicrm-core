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
      $actualPath = $this->findCorrectCaseForFile($path);
      if ($actualPath !== NULL) {
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

  /**
   * Linux is case sensitive, so this will be a no-op.
   * Windows is case insensitive, Mac is usually insensitive but sometimes
   * sensitive.
   * Note that realpath() will return the real casing for a file on windows,
   * but not on mac, so we need a different method. glob returns the real
   * casing, but means we need to loop.
   *
   * @param string $path
   * @return string|null
   */
  private function findCorrectCaseForFile(string $path): ?string {
    $fileToFind = basename($path);
    foreach (glob(dirname($path) . '/*', GLOB_NOSORT) as $theRealFile) {
      if ($fileToFind === basename($theRealFile)) {
        return $theRealFile;
      }
    }
    return NULL;
  }

}
