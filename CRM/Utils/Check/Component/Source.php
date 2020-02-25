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
    $files[] = '[civicrm.packages]/Auth/SASL';
    $files[] = '[civicrm.packages]/Auth/SASL.php';
    $files[] = '[civicrm.packages]/Net/SMTP.php';
    $files[] = '[civicrm.packages]/Net/Socket.php';
    $files[] = '[civicrm.packages]/_ORIGINAL_/Net/SMTP.php';
    $files[] = '[civicrm.packages]/jquery/plugins/DataTables/Readme.md';
    $files[] = '[civicrm.packages]/jquery/plugins/DataTables/license.txt';
    $files[] = '[civicrm.packages]/jquery/plugins/DataTables/media/css/jquery.dataTables.css';
    $files[] = '[civicrm.packages]/jquery/plugins/DataTables/media/css/jquery.dataTables.min.css';
    $files[] = '[civicrm.packages]/jquery/plugins/DataTables/media/css/jquery.dataTables_themeroller.css';
    $files[] = '[civicrm.packages]/jquery/plugins/DataTables/media/js/jquery.dataTables.js';
    $files[] = '[civicrm.packages]/jquery/plugins/DataTables/media/js/jquery.dataTables.min.js';
    $files[] = '[civicrm.packages]/jquery/plugins/DataTables/media/js/jquery.js';
    $files[] = '[civicrm.vendor]/pear/net_smtp/examples';
    $files[] = '[civicrm.vendor]/pear/net_smtp/tests';
    $files[] = '[civicrm.vendor]/pear/net_smtp/phpdoc.sh';
    $files[] = '[civicrm.vendor]/phpoffice/phpword/samples';
    $files[] = '[civicrm.root]/templates/CRM/common/version.tpl';
    $files[] = '[civicrm.packages]/Log.php';
    $files[] = '[civicrm.packages]/_ORIGINAL_/Log.php';
    $files[] = '[civicrm.packages]/Log/composite.php';
    $files[] = '[civicrm.packages]/Log/console.php';
    $files[] = '[civicrm.packages]/Log/daemon.php';
    $files[] = '[civicrm.packages]/Log/display.php';
    $files[] = '[civicrm.packages]/Log/error_log.php';
    $files[] = '[civicrm.packages]/Log/file.php';
    $files[] = '[civicrm.packages]/Log/firebug.php';
    $files[] = '[civicrm.packages]/Log/mail.php';
    $files[] = '[civicrm.packages]/Log/mcal.php';
    $files[] = '[civicrm.packages]/Log/mdb2.php';
    $files[] = '[civicrm.packages]/Log/null.php';
    $files[] = '[civicrm.packages]/Log/observer.php';
    $files[] = '[civicrm.packages]/Log/sql.php';
    $files[] = '[civicrm.packages]/Log/sqlite.php';
    $files[] = '[civicrm.packages]/Log/syslog.php';
    $files[] = '[civicrm.packages]/Log/win.php';

    return $files;
  }

  /**
   * @return array
   *   Each item is an array with keys:
   *     - name: string, an abstract name
   *     - path: string, a full file path
   *   Files are returned in deletable order (ie children before parents).
   */
  public function findOrphanedFiles() {
    $orphans = [];

    foreach ($this->getRemovedFiles() as $file) {
      $path = Civi::paths()->getPath($file);
      if (empty($path) || strpos('[civicrm', $path) !== FALSE) {
        Civi::log()->warning('Failed to resolve path of old file \"{file}\" ({path})', [
          'file' => $file,
          'path' => $path,
        ]);
      }
      if (file_exists($path)) {
        $orphans[] = [
          'name' => $file,
          'path' => $path,
        ];
      }
    }

    usort($orphans, function ($a, $b) {
      // Children first, then parents.
      $diff = strlen($b['name']) - strlen($a['name']);
      if ($diff !== 0) {
        return $diff;
      }
      if ($a['name'] === $b['name']) {
        return 0;
      }
      return $a['name'] < $b['name'] ? -1 : 1;
    });

    return $orphans;
  }

  /**
   * @return array
   */
  public function checkOrphans() {
    $orphans = $this->findOrphanedFiles();
    if (empty($orphans)) {
      return [];
    }

    $messages = [];
    $messages[] = new CRM_Utils_Check_Message(
      __FUNCTION__,
      ts('The local system includes old files which should not exist: "%1"',
        [
          1 => implode('", "', CRM_Utils_Array::collect('path', $orphans)),
        ]),
      ts('Old files'),
      \Psr\Log\LogLevel::WARNING,
      'fa-server'
    );

    return $messages;
  }

}
