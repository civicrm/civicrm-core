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

/**
 * This caching provider stores all cached items as a "group" in the
 * "civicrm_cache" table. The entire 'group' may be prefetched when
 * instantiating the cache provider.
 */
class CRM_Utils_Cache_SqlGroup implements CRM_Utils_Cache_Interface {

  // 6*60*60
  const DEFAULT_TTL = 21600;

  const TS_FMT = 'Y-m-d H:i:s';
  // TODO Consider native implementation.
  use CRM_Utils_Cache_NaiveMultipleTrait;

  /**
   * Name of the cache group.
   *
   * @var string
   */
  protected $group;

  /**
   * @var int
   */
  protected $componentID;

  /**
   * In-memory cache to optimize redundant get()s.
   *
   * @var array
   */
  protected $valueCache;

  /**
   * In-memory cache to optimize redundant get()s.
   *
   * @var array
   *   Note: expiresCache[$key]===NULL means cache-miss
   */
  protected $expiresCache;

  /**
   * Table.
   *
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
    $this->table = 'civicrm_cache';
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
    $this->valueCache = [];
    if ($config['prefetch'] ?? TRUE) {
      $this->prefetch();
    }
  }

  /**
   * @param string $key
   * @param mixed $value
   * @param null|int|\DateInterval $ttl
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   * @throws \CRM_Utils_Cache_CacheException
   * @throws \CRM_Utils_Cache_InvalidArgumentException
   */
  public function set($key, $value, $ttl = NULL) {
    CRM_Utils_Cache::assertValidKey($key);

    $lock = Civi::lockManager()->acquire("cache.{$this->group}_{$key}._null");
    if (!$lock->isAcquired()) {
      throw new \CRM_Utils_Cache_CacheException("SqlGroup: Failed to acquire lock on cache key.");
    }

    if (is_int($ttl) && $ttl <= 0) {
      $result = $this->delete($key);
      $lock->release();
      return $result;
    }

    $dataExists = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM {$this->table} WHERE {$this->where($key)}");
    $expires = round(microtime(1)) + CRM_Utils_Date::convertCacheTtl($ttl, self::DEFAULT_TTL);

    $dataSerialized = CRM_Core_BAO_Cache::encode($value);

    // This table has a wonky index, so we cannot use REPLACE or
    // "INSERT ... ON DUPE". Instead, use SELECT+(INSERT|UPDATE).
    if ($dataExists) {
      $sql = "UPDATE {$this->table} SET data = %1, created_date = FROM_UNIXTIME(%2), expired_date = FROM_UNIXTIME(%3) WHERE {$this->where($key)}";
      $args = [
        1 => [$dataSerialized, 'String'],
        2 => [time(), 'Positive'],
        3 => [$expires, 'Positive'],
      ];
      CRM_Core_DAO::executeQuery($sql, $args, TRUE, NULL, FALSE, FALSE);
    }
    else {
      $sql = "INSERT INTO {$this->table} (group_name,path,data,created_date,expired_date) VALUES (%1,%2,%3,FROM_UNIXTIME(%4),FROM_UNIXTIME(%5))";
      $args = [
        1 => [(string) $this->group, 'String'],
        2 => [$key, 'String'],
        3 => [$dataSerialized, 'String'],
        4 => [time(), 'Positive'],
        5 => [$expires, 'Positive'],
      ];
      CRM_Core_DAO::executeQuery($sql, $args, TRUE, NULL, FALSE, FALSE);
    }

    $lock->release();

    $this->valueCache[$key] = CRM_Core_BAO_Cache::decode($dataSerialized);
    $this->expiresCache[$key] = $expires;
    return TRUE;
  }

  /**
   * @param string $key
   * @param mixed $default
   *
   * @return mixed
   *
   * @throws \CRM_Utils_Cache_InvalidArgumentException
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
    }
    return (isset($this->expiresCache[$key]) && time() < $this->expiresCache[$key]) ? $this->reobjectify($this->valueCache[$key]) : $default;
  }

  /**
   * @param mixed $value
   *
   * @return object
   */
  private function reobjectify($value) {
    return is_object($value) ? unserialize(serialize($value)) : $value;
  }

  /**
   * @param string $key
   * @param mixed $default
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
   *
   * @return bool
   * @throws \CRM_Utils_Cache_InvalidArgumentException
   */
  public function delete($key) {
    CRM_Utils_Cache::assertValidKey($key);
    // If we are triggering a deletion of a prevNextCache key in the civicrm_cache tabl
    // Alssure that the relevant prev_next_cache values are also removed.
    if ($this->group == CRM_Utils_Cache::cleanKey('CiviCRM Search PrevNextCache')) {
      Civi::service('prevnext')->deleteItem(NULL, $key);
    }
    CRM_Core_DAO::executeQuery("DELETE FROM {$this->table} WHERE {$this->where($key)}");
    unset($this->valueCache[$key]);
    unset($this->expiresCache[$key]);
    return TRUE;
  }

  public function flush() {
    if ($this->group == CRM_Utils_Cache::cleanKey('CiviCRM Search PrevNextCache') &&
      Civi::service('prevnext') instanceof CRM_Core_PrevNextCache_Sql) {
      Civi::service('prevnext')->cleanup();
    }
    else {
      CRM_Core_DAO::executeQuery("DELETE FROM {$this->table} WHERE {$this->where()}");
    }
    $this->valueCache = [];
    $this->expiresCache = [];
    return TRUE;
  }

  public function clear() {
    return $this->flush();
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    $sql = "DELETE FROM civicrm_cache WHERE expired_date < %1";
    $params = [
      1 => [date(CRM_Utils_Cache_SqlGroup::TS_FMT, CRM_Utils_Time::time()), 'String'],
    ];
    $return = CRM_Core_DAO::executeQuery($sql, $params);

    return !empty($return);
  }

  public function prefetch() {
    $dao = CRM_Core_DAO::executeQuery("SELECT path, data, UNIX_TIMESTAMP(expired_date) AS expires FROM {$this->table} WHERE " . $this->where(NULL));
    $this->valueCache = [];
    $this->expiresCache = [];
    while ($dao->fetch()) {
      $this->valueCache[$dao->path] = CRM_Core_BAO_Cache::decode($dao->data);
      $this->expiresCache[$dao->path] = $dao->expires;
    }
  }

  protected function where($path = NULL) {
    $clauses = [];
    $clauses[] = ('group_name = "' . CRM_Core_DAO::escapeString($this->group) . '"');
    if ($path) {
      $clauses[] = ('path = "' . CRM_Core_DAO::escapeString($path) . '"');
    }
    return $clauses ? implode(' AND ', $clauses) : '(1)';
  }

}
