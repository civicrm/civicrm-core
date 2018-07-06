<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
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
    $orphans = array();

    foreach ($this->getRemovedFiles() as $file) {
      $path = Civi::paths()->getPath($file);
      if (empty($path) || strpos('[civicrm', $path) !== FALSE) {
        Civi::log()->warning('Failed to resolve path of old file \"{file}\" ({path})', array(
          'file' => $file,
          'path' => $path,
        ));
      }
      if (file_exists($path)) {
        $orphans[] = array(
          'name' => $file,
          'path' => $path,
        );
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
      return array();
    }

    $messages = array();
    $messages[] = new CRM_Utils_Check_Message(
      __FUNCTION__,
      ts('The local system includes old files which should not exist: "%1"',
        array(
          1 => implode('", "', CRM_Utils_Array::collect('path', $orphans)),
        )),
      ts('Old files'),
      \Psr\Log\LogLevel::WARNING,
      'fa-server'
    );

    return $messages;
  }

}
