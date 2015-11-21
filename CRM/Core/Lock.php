<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Core_Lock implements \Civi\Core\Lock\LockInterface {

  static $jobLog = FALSE;

  // lets have a 3 second timeout for now
  const TIMEOUT = 3;

  protected $_hasLock = FALSE;

  protected $_name;

  /**
   * Use MySQL's GET_LOCK(). Locks are shared across all Civi instances
   * on the same MySQL server.
   *
   * @param string $name
   *   Symbolic name for the lock. Names generally look like
   *   "worker.mailing.EmailProcessor" ("{category}.{component}.{AdhocName}").
   *
   *   Categories: worker|data|cache|...
   *   Component: core|mailing|member|contribute|...
   * @return \Civi\Core\Lock\LockInterface
   */
  public static function createGlobalLock($name) {
    return new static($name, NULL, TRUE);
  }

  /**
   * Use MySQL's GET_LOCK(), but apply prefixes to the lock names.
   * Locks are unique to each instance of Civi.
   *
   * @param string $name
   *   Symbolic name for the lock. Names generally look like
   *   "worker.mailing.EmailProcessor" ("{category}.{component}.{AdhocName}").
   *
   *   Categories: worker|data|cache|...
   *   Component: core|mailing|member|contribute|...
   * @return \Civi\Core\Lock\LockInterface
   */
  public static function createScopedLock($name) {
    return new static($name);
  }

  /**
   * Use MySQL's GET_LOCK(), but conditionally apply prefixes to the lock names
   * (if civimail_server_wide_lock is disabled).
   *
   * @param string $name
   *   Symbolic name for the lock. Names generally look like
   *   "worker.mailing.EmailProcessor" ("{category}.{component}.{AdhocName}").
   *
   *   Categories: worker|data|cache|...
   *   Component: core|mailing|member|contribute|...
   * @return \Civi\Core\Lock\LockInterface
   * @deprecated
   */
  public static function createCivimailLock($name) {
    $serverWideLock = \Civi::settings()->get('civimail_server_wide_lock');
    return new static($name, NULL, $serverWideLock);
  }

  /**
   * Initialize the constants used during lock acquire / release
   *
   * @param string $name
   *   Symbolic name for the lock. Names generally look like
   *   "worker.mailing.EmailProcessor" ("{category}.{component}.{AdhocName}").
   *
   *   Categories: worker|data|cache|...
   *   Component: core|mailing|member|contribute|...
   * @param int $timeout
   *   The number of seconds to wait to get the lock. 1 if not set.
   * @param bool $serverWideLock
   *   Should this lock be applicable across your entire mysql server.
   *   this is useful if you have multiple sites running on the same
   *   mysql server and you want to limit the number of parallel cron
   *   jobs - CRM-91XX
   */
  public function __construct($name, $timeout = NULL, $serverWideLock = FALSE) {
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
    $this->_timeout = $timeout !== NULL ? $timeout : self::TIMEOUT;
  }

  public function __destruct() {
    $this->release();
  }

  /**
   * Acquire lock.
   *
   * @param int $timeout
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function acquire($timeout = NULL) {
    if (!$this->_hasLock) {
      if (self::$jobLog && CRM_Core_DAO::singleValueQuery("SELECT IS_USED_LOCK( '" . self::$jobLog . "')")) {
        return $this->hackyHandleBrokenCode(self::$jobLog);
      }

      $query = "SELECT GET_LOCK( %1, %2 )";
      $params = array(
        1 => array($this->_name, 'String'),
        2 => array($timeout ? $timeout : $this->_timeout, 'Integer'),
      );
      $res = CRM_Core_DAO::singleValueQuery($query, $params);
      if ($res) {
        if (defined('CIVICRM_LOCK_DEBUG')) {
          CRM_Core_Error::debug_log_message('acquire lock for ' . $this->_name);
        }
        $this->_hasLock = TRUE;
        if (stristr($this->_name, 'data.mailing.job.')) {
          self::$jobLog = $this->_name;
        }
      }
      else {
        if (defined('CIVICRM_LOCK_DEBUG')) {
          CRM_Core_Error::debug_log_message('failed to acquire lock for ' . $this->_name);
        }
      }
    }
    return $this->_hasLock;
  }

  /**
   * @return null|string
   */
  public function release() {
    if ($this->_hasLock) {
      if (defined('CIVICRM_LOCK_DEBUG')) {
        CRM_Core_Error::debug_log_message('release lock for ' . $this->_name);
      }
      $this->_hasLock = FALSE;

      if (self::$jobLog == $this->_name) {
        self::$jobLog = FALSE;
      }

      $query = "SELECT RELEASE_LOCK( %1 )";
      $params = array(1 => array($this->_name, 'String'));
      return CRM_Core_DAO::singleValueQuery($query, $params);
    }
  }

  /**
   * @return null|string
   */
  public function isFree() {
    $query = "SELECT IS_FREE_LOCK( %1 )";
    $params = array(1 => array($this->_name, 'String'));
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * @return bool
   */
  public function isAcquired() {
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
   * @return bool
   */
  public function hackyHandleBrokenCode($jobLog) {
    if (stristr($this->_name, 'job')) {
      CRM_Core_Error::debug_log_message('lock acquisition for ' . $this->_name . ' attempted when ' . $jobLog . ' is not released');
      throw new CRM_Core_Exception('lock acquisition for ' . $this->_name . ' attempted when ' . $jobLog . ' is not released');
    }
    if (defined('CIVICRM_LOCK_DEBUG')) {
      CRM_Core_Error::debug_log_message('(CRM-12856) faking lock for ' . $this->_name);
    }
    $this->_hasLock = TRUE;
    return TRUE;
  }

}
