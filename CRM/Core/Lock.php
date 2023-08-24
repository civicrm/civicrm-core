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
class CRM_Core_Lock implements \Civi\Core\Lock\LockInterface {

  /**
   * This variable (despite it's name) roughly translates to 'lock that we actually care about'.
   *
   * Prior to version 5.7.5 mysql only supports a single named lock. This variable is
   * part of the skullduggery involved in 'say it's no so Frank'.
   *
   * See further comments on the acquire function.
   *
   * @var bool
   */
  public static $jobLog = FALSE;

  /**
   * lets have a 3 second timeout for now
   */
  const TIMEOUT = 3;

  /**
   * @var bool
   */
  protected $_hasLock = FALSE;

  protected $_name;

  protected $_id;

  /**
   * Lock Timeout
   * @var int
   */
  protected $_timeout;

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
    $dsn = CRM_Utils_SQL::autoSwitchDSN($config->dsn);
    $dsnArray = DB::parseDSN($dsn);
    $database = $dsnArray['database'];
    $domainID = CRM_Core_Config::domainID();
    if ($serverWideLock) {
      $this->_name = $name;
    }
    else {
      $this->_name = $database . '.' . $domainID . '.' . $name;
    }
    // MySQL 5.7 doesn't like long lock names so creating a lock id
    $this->_id = sha1($this->_name);
    if (defined('CIVICRM_LOCK_DEBUG')) {
      \Civi::log()->debug('trying to construct lock for ' . $this->_name . '(' . $this->_id . ')');
    }
    $this->_timeout = $timeout !== NULL ? $timeout : self::TIMEOUT;
  }

  public function __destruct() {
    $this->release();
  }

  /**
   * Acquire lock.
   *
   * The advantage of mysql locks is that they can be used across processes. However, only one
   * can be used at once within a process. An attempt to use a second one within a process
   * prior to mysql 5.7.5 results in the first being released.
   *
   * The process here is
   *  1) first attempt to grab a lock for a mailing job - self::jobLog will be populated with the
   * lock id & a mysql lock will be created for the ID.
   *
   * If a second function in the same process attempts to grab the lock it will enter the hackyHandleBrokenCode routine
   * which says 'I won't break a mailing lock for you but if you are not a civimail send process I'll let you
   * pretend you have a lock already and you can go ahead with whatever you were doing under the delusion you
   * have a lock.
   *
   * @todo bypass hackyHandleBrokenCode for mysql version 5.7.5+
   *
   * If a second function in a separate process attempts to grab the lock already in use it should be rejected,
   * but it appears it IS allowed to grab a different lock & unlike in the same process the first lock won't be released.
   *
   * All this means CiviMail locks are first class citizens & any other process gets a 'best effort lock'.
   *
   * @todo document naming convention for CiviMail locks as this is key to ensuring they work properly.
   *
   * @param int|null $timeout
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function acquire($timeout = NULL) {
    if (!$this->_hasLock) {

      $query = "SELECT GET_LOCK( %1, %2 )";
      $params = [
        1 => [$this->_id, 'String'],
        2 => [$timeout ?: $this->_timeout, 'Integer'],
      ];
      $res = CRM_Core_DAO::singleValueQuery($query, $params);
      if ($res) {
        if (defined('CIVICRM_LOCK_DEBUG')) {
          \Civi::log()->debug('acquire lock for ' . $this->_name . '(' . $this->_id . ')');
        }
        $this->_hasLock = TRUE;
        if (stristr($this->_name, 'data.mailing.job.')) {
          self::$jobLog = $this->_id;
        }
      }
      else {
        if (defined('CIVICRM_LOCK_DEBUG')) {
          \Civi::log()->debug('failed to acquire lock for ' . $this->_name . '(' . $this->_id . ')');
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
        \Civi::log()->debug('release lock for ' . $this->_name . '(' . $this->_id . ')');
      }
      $this->_hasLock = FALSE;

      if (self::$jobLog == $this->_id) {
        self::$jobLog = FALSE;
      }

      $query = "SELECT RELEASE_LOCK( %1 )";
      $params = [1 => [$this->_id, 'String']];
      if (CRM_Core_Transaction::isActive()) {
        CRM_Core_Transaction::addCallback(CRM_Core_Transaction::PHASE_POST_COMMIT, function ($query, $params) {
          return CRM_Core_DAO::singleValueQuery($query, $params);
        }, [$query, $params]);
        CRM_Core_Transaction::addCallback(CRM_Core_Transaction::PHASE_POST_ROLLBACK, function ($query, $params) {
          return CRM_Core_DAO::singleValueQuery($query, $params);
        }, [$query, $params]);
      }
      else {
        return CRM_Core_DAO::singleValueQuery($query, $params);
      }
    }
  }

  /**
   * @return null|string
   */
  public function isFree() {
    $query = "SELECT IS_FREE_LOCK( %1 )";
    $params = [1 => [$this->_id, 'String']];
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * @return bool
   */
  public function isAcquired() {
    return $this->_hasLock;
  }

}
