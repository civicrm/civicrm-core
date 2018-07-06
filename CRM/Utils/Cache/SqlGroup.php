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

/**
 * This caching provider stores all cached items as a "group" in the
 * "civicrm_cache" table. The entire 'group' may be prefetched when
 * instantiating the cache provider.
 */
class CRM_Utils_Cache_SqlGroup implements CRM_Utils_Cache_Interface {

  // 6*60*60
  const DEFAULT_TTL = 21600;

  const TS_FMT = 'Y-m-d H:i:s';
  use CRM_Utils_Cache_NaiveMultipleTrait; // TODO Consider native implementation.

  /**
   * The host name of the memcached server.
   *
   * @var string
   */
  protected $group;

  /**
   * @var int $componentID The optional component ID (so componenets can share the same name space)
   */
  protected $componentID;

  /**
   * @var array in-memory cache to optimize redundant get()s
   */
  protected $valueCache;

  /**
   * @var array in-memory cache to optimize redundant get()s
   *   Note: expiresCache[$key]===NULL means cache-miss
   */
  protected $expiresCache;

  /**
   * @var string
   */
  protected $table;

  /**
   * Constructor.
   *
   * @param array $config
   *   An array of configuration params.
   *   - group: string
   *   - componentID: int
   *   - prefetch: bool, whether to preemptively read the entire cache group; default: TRUE
   *
   * @throws RuntimeException
   * @return \CRM_Utils_Cache_SqlGroup
   */
  public function __construct($config) {
    $this->table = CRM_Core_DAO_Cache::getTableName();
    if (isset($config['group'])) {
      $this->group = $config['group'];
    }
    else {
      throw new RuntimeException("Cannot construct SqlGroup cache: missing group");
    }
    if (isset($config['componentID'])) {
      $this->componentID = $config['componentID'];
    }
    else {
      $this->componentID = NULL;
    }
    $this->valueCache = array();
    if (CRM_Utils_Array::value('prefetch', $config, TRUE)) {
      $this->prefetch();
    }
  }

  /**
   * @param string $key
   * @param mixed $value
   * @param null|int|\DateInterval $ttl
   * @return bool
   */
  public function set($key, $value, $ttl = NULL) {
    CRM_Utils_Cache::assertValidKey($key);

    $lock = Civi::lockManager()->acquire("cache.{$this->group}_{$key}._null");
    if (!$lock->isAcquired()) {
      throw new \CRM_Utils_Cache_CacheException("SqlGroup: Failed to acquire lock on cache key.");
    }

    if (is_int($ttl) && $ttl <= 0) {
      return $this->delete($key);
    }

    $dataExists = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM {$this->table} WHERE {$this->where($key)}");
    $expires = round(microtime(1)) + CRM_Utils_Date::convertCacheTtl($ttl, self::DEFAULT_TTL);

    $dataSerialized = CRM_Core_BAO_Cache::encode($value);

    // This table has a wonky index, so we cannot use REPLACE or
    // "INSERT ... ON DUPE". Instead, use SELECT+(INSERT|UPDATE).
    if ($dataExists) {
      $sql = "UPDATE {$this->table} SET data = %1, created_date = FROM_UNIXTIME(%2), expired_date = FROM_UNIXTIME(%3) WHERE {$this->where($key)}";
      $args = array(
        1 => array($dataSerialized, 'String'),
        2 => array(time(), 'Positive'),
        3 => array($expires, 'Positive'),
      );
      $dao = CRM_Core_DAO::executeQuery($sql, $args, FALSE, NULL, FALSE, FALSE);
    }
    else {
      $sql = "INSERT INTO {$this->table} (group_name,path,data,created_date,expired_date) VALUES (%1,%2,%3,FROM_UNIXTIME(%4),FROM_UNIXTIME(%5))";
      $args = array(
        1 => [$this->group, 'String'],
        2 => [$key, 'String'],
        3 => [$dataSerialized, 'String'],
        4 => [time(), 'Positive'],
        5 => [$expires, 'Positive'],
      );
      $dao = CRM_Core_DAO::executeQuery($sql, $args, FALSE, NULL, FALSE, FALSE);
    }

    $lock->release();

    $dao->free();

    $this->valueCache[$key] = CRM_Core_BAO_Cache::decode($dataSerialized);
    $this->expiresCache[$key] = $expires;
    return TRUE;
  }

  /**
   * @param string $key
   * @param mixed $default
   *
   * @return mixed
   */
  public function get($key, $default = NULL) {
    CRM_Utils_Cache::assertValidKey($key);
    if (!isset($this->expiresCache[$key]) || time() >= $this->expiresCache[$key]) {
      $sql = "SELECT path, data, UNIX_TIMESTAMP(expired_date) as expires FROM {$this->table} WHERE " . $this->where($key);
      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        $this->expiresCache[$key] = $dao->expires;
        $this->valueCache[$key] = CRM_Core_BAO_Cache::decode($dao->data);
      }
      $dao->free();
    }
    return (isset($this->expiresCache[$key]) && time() < $this->expiresCache[$key]) ? $this->reobjectify($this->valueCache[$key]) : $default;
  }

  private function reobjectify($value) {
    return is_object($value) ? unserialize(serialize($value)) : $value;
  }

  /**
   * @param $key
   * @param null $default
   *
   * @return mixed
   */
  public function getFromFrontCache($key, $default = NULL) {
    if (isset($this->expiresCache[$key]) && time() < $this->expiresCache[$key] && $this->valueCache[$key]) {
      return $this->reobjectify($this->valueCache[$key]);
    }
    else {
      return $default;
    }
  }

  public function has($key) {
    $this->get($key);
    return isset($this->expiresCache[$key]) && time() < $this->expiresCache[$key];
  }

  /**
   * @param string $key
   * @return bool
   */
  public function delete($key) {
    CRM_Utils_Cache::assertValidKey($key);
    CRM_Core_DAO::executeQuery("DELETE FROM {$this->table} WHERE {$this->where($key)}");
    unset($this->valueCache[$key]);
    unset($this->expiresCache[$key]);
    return TRUE;
  }

  public function flush() {
    CRM_Core_DAO::executeQuery("DELETE FROM {$this->table} WHERE {$this->where()}");
    $this->valueCache = array();
    $this->expiresCache = array();
    return TRUE;
  }

  public function clear() {
    return $this->flush();
  }

  public function prefetch() {
    $dao = CRM_Core_DAO::executeQuery("SELECT path, data, UNIX_TIMESTAMP(expired_date) AS expires FROM {$this->table} WHERE " . $this->where(NULL));
    $this->valueCache = array();
    $this->expiresCache = array();
    while ($dao->fetch()) {
      $this->valueCache[$dao->path] = CRM_Core_BAO_Cache::decode($dao->data);
      $this->expiresCache[$dao->path] = $dao->expires;
    }
    $dao->free();
  }

  protected function where($path = NULL) {
    $clauses = array();
    $clauses[] = ('group_name = "' . CRM_Core_DAO::escapeString($this->group) . '"');
    if ($path) {
      $clauses[] = ('path = "' . CRM_Core_DAO::escapeString($path) . '"');
    }
    return $clauses ? implode(' AND ', $clauses) : '(1)';
  }

}
