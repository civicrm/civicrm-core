<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * BAO object for civicrm_cache table. This is a database cache and is persisted across sessions. Typically we use
 * this to store meta data (like profile fields, custom fields etc).
 *
 * The group_name column is used for grouping together all cache elements that logically belong to the same set.
 * Thus all session cache entries are grouped under 'CiviCRM Session'. This allows us to delete all entries of
 * a specific group if needed.
 *
 * The path column allows us to differentiate between items in that group. Thus for the session cache, the path is
 * the unique form name for each form (per user)
 */
class CRM_Core_BAO_Cache extends CRM_Core_DAO_Cache {

  /**
   * Retrieve an item from the DB cache
   *
   * @param string $group (required) The group name of the item
   * @param string $path  (required) The path under which this item is stored
   * @param int    $componentID The optional component ID (so componenets can share the same name space)
   *
   * @return object The data if present in cache, else null
   * @static
   * @access public
   */
  static function &getItem($group, $path, $componentID = NULL) {
    $dao = new CRM_Core_DAO_Cache();

    $dao->group_name   = $group;
    $dao->path         = $path;
    $dao->component_id = $componentID;

    $data = NULL;
    if ($dao->find(TRUE)) {
      $data = unserialize($dao->data);
    }
    $dao->free();
    return $data;
  }

  /**
   * Retrieve all items in a group
   *
   * @param string $group (required) The group name of the item
   * @param int    $componentID The optional component ID (so componenets can share the same name space)
   *
   * @return object The data if present in cache, else null
   * @static
   * @access public
   */
  static function &getItems($group, $componentID = NULL) {
    $dao = new CRM_Core_DAO_Cache();

    $dao->group_name   = $group;
    $dao->component_id = $componentID;
    $dao->find();

    $result = array(); // array($path => $data)
    while ($dao->fetch()) {
      $result[$dao->path] = unserialize($dao->data);
    }
    $dao->free();
    return $result;
  }

  /**
   * Store an item in the DB cache
   *
   * @param object $data  (required) A reference to the data that will be serialized and stored
   * @param string $group (required) The group name of the item
   * @param string $path  (required) The path under which this item is stored
   * @param int    $componentID The optional component ID (so componenets can share the same name space)
   *
   * @return void
   * @static
   * @access public
   */
  static function setItem(&$data, $group, $path, $componentID = NULL) {
    $dao = new CRM_Core_DAO_Cache();

    $dao->group_name   = $group;
    $dao->path         = $path;
    $dao->component_id = $componentID;

    // get a lock so that multiple ajax requests on the same page
    // dont trample on each other
    // CRM-11234
    $lockName = "civicrm.cache.{$group}_{$path}._{$componentID}";
    $lock = new CRM_Core_Lock($lockName);
    if (!$lock->isAcquired()) {
      CRM_Core_Error::fatal();
    }

    $dao->find(TRUE);
    $dao->data = serialize($data);
    $dao->created_date = date('YmdHis');
    $dao->save();

    $lock->release();

    $dao->free();
  }

  /**
   * Delete all the cache elements that belong to a group OR
   * delete the entire cache if group is not specified
   *
   * @param string $group The group name of the entries to be deleted
   * @param string $path  path of the item that needs to be deleted
   * @param booleab $clearAll clear all caches
   *
   * @return void
   * @static
   * @access public
   */
  static function deleteGroup($group = NULL, $path = NULL, $clearAll = TRUE) {
    $dao = new CRM_Core_DAO_Cache();

    if (!empty($group)) {
      $dao->group_name = $group;
    }

    if (!empty($path)) {
      $dao->path = $path;
    }

    $dao->delete();

    if ($clearAll) {
      // also reset ACL Cache
      CRM_ACL_BAO_Cache::resetCache();

      // also reset memory cache if any
      CRM_Utils_System::flushCache();
    }
  }

  /**
   * The next two functions are internal functions used to store and retrieve session from
   * the database cache. This keeps the session to a limited size and allows us to
   * create separate session scopes for each form in a tab
   *
   */

  /**
   * This function takes entries from the session array and stores it in the cache.
   * It also deletes the entries from the $_SESSION object (for a smaller session size)
   *
   * @param array $names Array of session values that should be persisted
   *                     This is either a form name + qfKey or just a form name
   *                     (in the case of profile)
   * @param boolean $resetSession Should session state be reset on completion of DB store?
   *
   * @return void
   * @static
   * @access private
   */
  static function storeSessionToCache($names, $resetSession = TRUE) {
    foreach ($names as $key => $sessionName) {
      if (is_array($sessionName)) {
        $value = null;
        if (!empty($_SESSION[$sessionName[0]][$sessionName[1]])) {
          $value = $_SESSION[$sessionName[0]][$sessionName[1]];
        }
        self::setItem($value, 'CiviCRM Session', "{$sessionName[0]}_{$sessionName[1]}");
          if ($resetSession) {
            $_SESSION[$sessionName[0]][$sessionName[1]] = NULL;
            unset($_SESSION[$sessionName[0]][$sessionName[1]]);
          }
        }
      else {
        $value = null;
        if (!empty($_SESSION[$sessionName])) {
          $value = $_SESSION[$sessionName];
        }
        self::setItem($value, 'CiviCRM Session', $sessionName);
          if ($resetSession) {
            $_SESSION[$sessionName] = NULL;
            unset($_SESSION[$sessionName]);
          }
        }
      }

    self::cleanup();
  }

  /* Retrieve the session values from the cache and populate the $_SESSION array
     *
     * @param array $names Array of session values that should be persisted
     *                     This is either a form name + qfKey or just a form name
     *                     (in the case of profile)
     *
     * @return void
     * @static
     * @access private
     */

  static function restoreSessionFromCache($names) {
    foreach ($names as $key => $sessionName) {
      if (is_array($sessionName)) {
        $value = self::getItem('CiviCRM Session',
          "{$sessionName[0]}_{$sessionName[1]}"
        );
        if ($value) {
          $_SESSION[$sessionName[0]][$sessionName[1]] = $value;
        }
      }
      else {
        $value = self::getItem('CiviCRM Session',
          $sessionName
        );
        if ($value) {
          $_SESSION[$sessionName] = $value;
        }
      }
    }
  }

  /**
   * Do periodic cleanup of the CiviCRM session table. Also delete all session cache entries
   * which are a couple of days old. This keeps the session cache to a manageable size
   *
   * @return void
   * @static
   * @access private
   */
  static function cleanup($session = false, $table = false, $prevNext = false) {
    // clean up the session cache every $cacheCleanUpNumber probabilistically
    $cleanUpNumber = 757;

    // clean up all sessions older than $cacheTimeIntervalDays days
    $timeIntervalDays = 2;
    $timeIntervalMins = 30;

    if (mt_rand(1, 100000) % $cleanUpNumber == 0) {
      $session = $table = $prevNext = true;
    }

    if ( ! $session && ! $table && ! $prevNext ) {
      return;
    }

    if ( $prevNext ) {
      // delete all PrevNext caches
      CRM_Core_BAO_PrevNextCache::cleanupCache();
    }

    if ( $table ) {
      // also delete all the action temp tables
      // that were created the same interval ago
      $dao = new CRM_Core_DAO();
      $query = "
SELECT TABLE_NAME as tableName
FROM   INFORMATION_SCHEMA.TABLES
WHERE  TABLE_SCHEMA = %1
AND    ( TABLE_NAME LIKE 'civicrm_task_action_temp_%'
 OR      TABLE_NAME LIKE 'civicrm_export_temp_%'
 OR      TABLE_NAME LIKE 'civicrm_import_job_%' )
AND    CREATE_TIME < date_sub( NOW( ), INTERVAL $timeIntervalDays day )
";

      $params   = array(1 => array($dao->database(), 'String'));
      $tableDAO = CRM_Core_DAO::executeQuery($query, $params);
      $tables   = array();
      while ($tableDAO->fetch()) {
        $tables[] = $tableDAO->tableName;
      }
      if (!empty($tables)) {
        $table = implode(',', $tables);
        // drop leftover temporary tables
        CRM_Core_DAO::executeQuery("DROP TABLE $table");
      }
    }

    if ( $session ) {
      // first delete all sessions which are related to any potential transaction
      // page
      $transactionPages = array(
          'CRM_Contribute_Controller_Contribution',
          'CRM_Event_Controller_Registration',
        );

      $params = array(
        1 => array(date('Y-m-d H:i:s', time() - $timeIntervalMins * 60), 'String'),
      );
      foreach ($transactionPages as $trPage) {
        $params[] = array("%${trPage}%", 'String');
        $where[]  = 'path LIKE %' . sizeof($params);
      }

      $sql = "
DELETE FROM civicrm_cache
WHERE       group_name = 'CiviCRM Session'
AND         created_date <= %1
AND         ("  . implode(' OR ', $where) . ")";
      CRM_Core_DAO::executeQuery($sql, $params);

      $sql = "
DELETE FROM civicrm_cache
WHERE       group_name = 'CiviCRM Session'
AND         created_date < date_sub( NOW( ), INTERVAL $timeIntervalDays DAY )
";
      CRM_Core_DAO::executeQuery($sql);
    }
  }
}

