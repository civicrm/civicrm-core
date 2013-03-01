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
class CRM_Core_Lock {

  // lets have a 3 second timeout for now
  CONST TIMEOUT = 3;

  protected $_hasLock = FALSE;

  protected $_name;

  /**
   * Initialize the constants used during lock acquire / release
   *
   * @param string  $name name of the lock. Please prefix with component / functionality
   *                      e.g. civimail.cronjob.JOB_ID
   * @param int     $timeout the number of seconds to wait to get the lock. 1 if not set
   * @param boolean $serverWideLock should this lock be applicable across your entire mysql server
   *                                this is useful if you have mutliple sites running on the same
   *                                mysql server and you want to limit the number of parallel cron
   *                                jobs - CRM-91XX
   *
   * @return object the lock object
   *
   */
 function __construct($name, $timeout = NULL, $serverWideLock = FALSE) {
    $config   = CRM_Core_Config::singleton();
    $dsnArray = DB::parseDSN($config->dsn);
    $database = $dsnArray['database'];
    $domainID = CRM_Core_Config::domainID();
    if ($serverWideLock) {
      $this->_name = $name;
    }
    else {
      $this->_name = $database . '.' . $domainID . '.' . $name;
    }
    $this->_timeout = $timeout !== NULL ? $timeout : self::TIMEOUT;

    $this->acquire();
  }

  function __destruct() {
    $this->release();
  }

  function acquire() {
    if (!$this->_hasLock) {
      $query = "SELECT GET_LOCK( %1, %2 )";
      $params = array(1 => array($this->_name, 'String'),
        2 => array($this->_timeout, 'Integer'),
      );
      $res = CRM_Core_DAO::singleValueQuery($query, $params);
      if ($res) {
        $this->_hasLock = TRUE;
      }
    }
    return $this->_hasLock;
  }

  function release() {
    if ($this->_hasLock) {
      $this->_hasLock = FALSE;

      $query = "SELECT RELEASE_LOCK( %1 )";
      $params = array(1 => array($this->_name, 'String'));
      return CRM_Core_DAO::singleValueQuery($query, $params);
    }
  }

  function isFree() {
    $query = "SELECT IS_FREE_LOCK( %1 )";
    $params = array(1 => array($this->_name, 'String'));
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  function isAcquired() {
    return $this->_hasLock;
  }
}

