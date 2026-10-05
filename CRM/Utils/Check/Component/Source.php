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
    $root = rtrim(Civi::paths()->getPath('[civicrm.root]/'), '/');
    $dirCache = [];
    foreach ($this->getRemovedFiles() as $file) {
      $cleanRelPath = rtrim($file, '/*');
      // On case-insensitive filesystems we need to verify every path segment
      $actualPath = $this->findCorrectCaseForPath($root, $cleanRelPath, $dirCache);
      if ($actualPath !== NULL) {
        $orphans[] = [
          'name' => $file,
          'path' => $actualPath,
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
   * Linux is case sensitive, but Windows is case insensitive and Mac is usually
   * case insensitive.
   *
   * Note that realpath() will return the real casing for a file on Windows,
   * but not on Mac. To ensure exact case matching on all filesystems, we
   * verify each path segment against the directory listing.
   *
   * @param string $basePath
   * @param string $relativePath
   * @param array $dirCache
   * @return string|null
   */
  private function findCorrectCaseForPath(string $basePath, string $relativePath, array &$dirCache = []): ?string {
    $current = rtrim($basePath, '/');
    $segments = array_filter(explode('/', $relativePath), 'strlen');

    foreach ($segments as $segment) {
      if (!is_dir($current)) {
        return NULL;
      }
      if (!isset($dirCache[$current])) {
        $entries = scandir($current);
        $dirCache[$current] = $entries !== FALSE ? array_flip($entries) : [];
      }
      if (!isset($dirCache[$current][$segment])) {
        return NULL;
      }
      $current .= '/' . $segment;
    }

    return file_exists($current) ? $current : NULL;
  }

}
