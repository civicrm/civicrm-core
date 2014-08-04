<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
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
   * @param string $name name of the lock. Please prefix with component / functionality
   *                      e.g. civimail.cronjob.JOB_ID
   * @param int $timeout the number of seconds to wait to get the lock. 1 if not set
   * @param boolean $serverWideLock should this lock be applicable across your entire mysql server
   *                                this is useful if you have multiple sites running on the same
   *                                mysql server and you want to limit the number of parallel cron
   *                                jobs - CRM-91XX
   *
   * @return \CRM_Core_Lock the lock object
   */
  function __construct($name, $timeout = NULL, $serverWideLock = FALSE) {
    $config = CRM_Core_Config::singleton();
    $dsnArray = DB::parseDSN($config->dsn);
    $database = $dsnArray['database'];
    $domainID = CRM_Core_Config::domainID();
    if ($serverWideLock) {
      $this->_name = $name;
    }
    else {
      $this->_name = $database . '.' . $domainID . '.' . $name;
    }
    if (defined('CIVICRM_LOCK_DEBUG')) {
      CRM_Core_Error::debug_log_message('trying to construct lock for ' . $this->_name);
    }
    static $jobLog = FALSE;
    if ($jobLog && CRM_Core_DAO::singleValueQuery("SELECT IS_USED_LOCK( '{$jobLog}')")) {
      return $this->hackyHandleBrokenCode($jobLog);
    }
    if (stristr($name, 'civimail.job.')) {
      $jobLog = $this->_name;
    }
    //if (defined('CIVICRM_LOCK_DEBUG')) {
    //CRM_Core_Error::debug_var('backtrace', debug_backtrace());
    //}
    $this->_timeout = $timeout !== NULL ? $timeout : self::TIMEOUT;

    $this->acquire();
  }

  function __destruct() {
    $this->release();
  }

  /**
   * @return bool
   */
  function acquire() {
    if (defined('CIVICRM_LOCK_DEBUG')) {
      CRM_Core_Error::debug_log_message('acquire lock for ' . $this->_name);
    }
    if (!$this->_hasLock) {
      $query = "SELECT GET_LOCK( %1, %2 )";
      $params = array(
        1 => array($this->_name, 'String'),
        2 => array($this->_timeout, 'Integer'),
      );
      $res = CRM_Core_DAO::singleValueQuery($query, $params);
      if ($res) {
        $this->_hasLock = TRUE;
      }
    }
    return $this->_hasLock;
  }

  /**
   * @return null|string
   */
  function release() {
    if ($this->_hasLock) {
      $this->_hasLock = FALSE;

      $query = "SELECT RELEASE_LOCK( %1 )";
      $params = array(1 => array($this->_name, 'String'));
      return CRM_Core_DAO::singleValueQuery($query, $params);
    }
  }

  /**
   * @return null|string
   */
  function isFree() {
    $query = "SELECT IS_FREE_LOCK( %1 )";
    $params = array(1 => array($this->_name, 'String'));
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * @return bool
   */
  function isAcquired() {
    return $this->_hasLock;
  }

  /**
   * CRM-12856 locks were originally set up for jobs, but the concept was extended to caching & groups without
   * understanding that would undermine the job locks (because grabbing a lock implicitly releases existing ones)
   * this is all a big hack to mitigate the impact of that - but should not be seen as a fix. Not sure correct fix
   * but maybe locks should be used more selectively? Or else we need to handle is some cool way that Tim is yet to write :-)
   * if we are running in the context of the cron log then we would rather die (or at least let our process die)
   * than release that lock - so if the attempt is being made by setCache or something relatively trivial
   * we'll just return TRUE, but if it's another job then we will crash as that seems 'safer'
   *
   * @param string $jobLog
   * @throws CRM_Core_Exception
   * @return boolean
   */
  function hackyHandleBrokenCode($jobLog) {
    if (stristr($this->_name, 'job')) {
      throw new CRM_Core_Exception('lock aquisition for ' . $this->_name . 'attempted when ' . $jobLog . 'is not released');
    }
    if (defined('CIVICRM_LOCK_DEBUG')) {
      CRM_Core_Error::debug_log_message('(CRM-12856) faking lock for ' . $this->_name);
    }
    $this->_hasLock = TRUE;
    return TRUE;
  }
}

