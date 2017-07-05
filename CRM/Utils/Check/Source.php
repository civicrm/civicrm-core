<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
 */
class CRM_Utils_Check_Source {

  public function checkAll() {
    $messages = $this->checkOrphans();
    return $messages;
  }

  public function getRemovedFiles() {
    global $civicrm_root;
    $files[] = array('path' => $civicrm_root . 'packages/Auth/SASL', 'name' => 'Auth/SASL');
    $files[] = array('path' => $civicrm_root . 'packages/Auth/SASL.php', 'name' => 'Auth/SASL.php');
    $files[] = array('path' => $civicrm_root . 'packages/Net/SMTP.php', 'name' => 'Net/SMTP.php');
    $files[] = array('path' => $civicrm_root . 'packages/Net/Socket.php', 'name' => 'Net/Socket.php');
    $files[] = array('path' => $civicrm_root . 'packages/_ORIGINAL_/Net/SMTP.php', 'name' => '_ORIGINAL_/Net/SMTP.php');
    $files[] = array('path' => $civicrm_root . 'vendor/pear/net_smtp/examples', 'name' => 'pear/net_smtp/examples');
    $files[] = array('path' => $civicrm_root . 'vendor/pear/net_smtp/tests', 'name' => 'pear/net_smtp/tests');
    $files[] = array('path' => $civicrm_root . 'vendor/pear/net_smtp/phpdoc.sh', 'name' => 'pear/net_smtp/phpdoc.sh');

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
      if (file_exists($file['path'])) {
        $orphans[] = array(
          'name' => $file['name'],
          'path' => $file['path'],
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
      ts('Old files')
    );

    return $messages;
  }

}
