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
 * Class CRM_Core_PrevNextCache_Memory
 *
 * Store the previous/next cache in a Redis set.
 *
 * Each logical prev-next cache corresponds to three distinct items in Redis:
 *   - "{prefix}/{qfKey}/list" - Sorted set of `entity_id`, with all entities
 *   - "{prefix}/{qfkey}/sel" - Sorted set of `entity_id`, with only entities marked by user
 *   - "{prefix}/{qfkey}/data" - Hash mapping from `entity_id` to `data`
 *
 * @link https://github.com/phpredis/phpredis
 */
class CRM_Core_PrevNextCache_Redis implements CRM_Core_PrevNextCache_Interface {

  private const TTL = 21600;

  /**
   * @var Redis
   */
  protected $redis;

  /**
   * @var string
   */
  protected $prefix;

  /**
   * CRM_Core_PrevNextCache_Redis constructor.
   * @param array $settings
   */
  public function __construct($settings) {
    $this->redis = CRM_Utils_Cache_Redis::connect($settings);
    $this->prefix = $settings['prefix'] ?? '';
    $this->prefix .= \CRM_Utils_Cache::DELIMITER . 'prevnext' . \CRM_Utils_Cache::DELIMITER;
  }

  /**
   * Get the time-to-live.
   *
   * This is likely to be made configurable in future.
   *
   * @return int
   */
  public function getTTL() : int {
    return self::TTL;
  }

  public function fillWithSql($cacheKey, $sql, $sqlParams = []) {
    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams, FALSE);

    [$allKey, $dataKey, , $maxScore] = $this->initCacheKey($cacheKey);
    $first = TRUE;
    while ($dao->fetch()) {
      [, $entity_id, $data] = array_values($dao->toArray());
      $maxScore++;
      $this->redis->zAdd($allKey, $maxScore, $entity_id);
      if ($first) {
        $this->redis->expire($allKey, $this->getTTL());
      }
      $this->redis->hSet($dataKey, $entity_id, $data);
      if ($first) {
        $this->redis->expire($dataKey, $this->getTTL());
      }
      $first = FALSE;
    }

    return TRUE;
  }

  public function fillWithArray($cacheKey, $rows) {
    [$allKey, $dataKey, , $maxScore] = $this->initCacheKey($cacheKey);
    $first = TRUE;
    foreach ($rows as $row) {
      $maxScore++;
      $this->redis->zAdd($allKey, $maxScore, $row['entity_id1']);
      if ($first) {
        $this->redis->expire($allKey, $this->getTTL());
      }
      $this->redis->hSet($dataKey, $row['entity_id1'], $row['data']);
      if ($first) {
        $this->redis->expire($dataKey, $this->getTTL());
      }
      $first = FALSE;
    }

    return TRUE;
  }

  public function fetch($cacheKey, $offset, $rowCount) {
    $allKey = $this->key($cacheKey, 'all');
    return $this->redis->zRange($allKey, $offset, $offset + $rowCount - 1);
  }

  public function markSelection($cacheKey, $action, $ids = NULL) {
    $allKey = $this->key($cacheKey, 'all');
    $selKey = $this->key($cacheKey, 'sel');

    if ($action === 'select') {
      $first = TRUE;
      foreach ((array) $ids as $id) {
        $score = $this->redis->zScore($allKey, $id);
        $this->redis->zAdd($selKey, $score, $id);
        if ($first) {
          $this->redis->expire($selKey, $this->getTTL());
        }
        $first = FALSE;
      }
    }
    elseif ($action === 'unselect' && $ids === NULL) {
      $this->redis->del($selKey);
      $this->redis->expire($selKey, $this->getTTL());
    }
    elseif ($action === 'unselect' && $ids !== NULL) {
      foreach ((array) $ids as $id) {
        $this->redis->zRem($selKey, $id);
      }
    }
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function getSelection($cacheKey, $action = 'get') {
    $allKey = $this->key($cacheKey, 'all');
    $selKey = $this->key($cacheKey, 'sel');

    if ($action === 'get') {
      $result = [];
      foreach ($this->redis->zRange($selKey, 0, -1) as $entity_id) {
        $result[$entity_id] = 1;
      }
      return [$cacheKey => $result];
    }
    if ($action === 'getall') {
      $result = [];
      foreach ($this->redis->zRange($allKey, 0, -1) as $entity_id) {
        $result[$entity_id] = 1;
      }
      return [$cacheKey => $result];
    }
    throw new \CRM_Core_Exception("Unrecognized action: $action");
  }

  public function getPositions($cacheKey, $id1) {
    $allKey = $this->key($cacheKey, 'all');
    $dataKey = $this->key($cacheKey, 'data');

    $rank = $this->redis->zRank($allKey, $id1);
    if (!is_int($rank) || $rank < 0) {
      return ['foundEntry' => 0];
    }

    $pos = ['foundEntry' => 1];

    if ($rank > 0) {
      $pos['prev'] = [];
      foreach ($this->redis->zRange($allKey, $rank - 1, $rank - 1) as $value) {
        $pos['prev']['id1'] = $value;
      }
      $pos['prev']['data'] = $this->redis->hGet($dataKey, $pos['prev']['id1']);
    }

    $count = $this->getCount($cacheKey);
    if ($count > $rank + 1) {
      $pos['next'] = [];
      foreach ($this->redis->zRange($allKey, $rank + 1, $rank + 1) as $value) {
        $pos['next']['id1'] = $value;
      }
      $pos['next']['data'] = $this->redis->hGet($dataKey, $pos['next']['id1']);
    }

    return $pos;
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function deleteItem($id = NULL, $cacheKey = NULL) {
    if ($id === NULL && $cacheKey !== NULL) {
      // Delete by cacheKey.
      $allKey = $this->key($cacheKey, 'all');
      $selKey = $this->key($cacheKey, 'sel');
      $dataKey = $this->key($cacheKey, 'data');
      $this->redis->del($allKey, $selKey, $dataKey);
    }
    elseif ($id === NULL && $cacheKey === NULL) {
      // Delete everything.
      $keys = $this->redis->keys($this->prefix . '*');
      $this->redis->del($keys);
    }
    elseif ($id !== NULL && $cacheKey !== NULL) {
      // Delete a specific contact, within a specific cache.
      $deleted = $this->redis->zRem($this->key($cacheKey, 'all'), $id);
      if ($deleted) {
        // If they were in the 'all' key they might be in the more specific 'sel' and 'data' keys.
        $this->redis->zRem($this->key($cacheKey, 'sel'), $id);
        $this->redis->hDel($this->key($cacheKey, 'data'), $id);
      }
    }
    elseif ($id !== NULL && $cacheKey === NULL) {
      // Delete a specific contact, across all prevnext caches.
      $allKeys = $this->redis->keys($this->key('*', 'all'));
      foreach ($allKeys as $allKey) {
        $parts = explode(\CRM_Utils_Cache::DELIMITER, $allKey);
        array_pop($parts);
        $tmpCacheKey = array_pop($parts);
        $this->deleteItem($id, $tmpCacheKey);
      }
    }
    else {
      throw new CRM_Core_Exception('Not implemented: Redis::deleteItem');
    }
  }

  public function getCount($cacheKey) {
    $allKey = $this->key($cacheKey, 'all');
    return $this->redis->zCard($allKey);
  }

  /**
   * Construct the full path to a cache item.
   *
   * @param string $cacheKey
   *   Identifier for this saved search.
   *   Ex: 'abcd1234abcd1234'.
   * @param string $item
   *   Ex: 'list', 'rel', 'data'.
   * @return string
   *   Ex: 'dmaster/prevnext/abcd1234abcd1234/list'
   */
  private function key($cacheKey, $item) {
    return $this->prefix . $cacheKey . \CRM_Utils_Cache::DELIMITER . $item;
  }

  /**
   * Initialize any data-structures or timeouts for the cache-key.
   *
   * This is non-destructive -- if data already exists, it's preserved.
   *
   * @return array
   *   0 => string $allItemsCacheKey,
   *   1 => string $dataItemsCacheKey,
   *   2 => string $selectedItemsCacheKey,
   *   3 => int $maxExistingScore
   */
  private function initCacheKey($cacheKey) {
    $allKey = $this->key($cacheKey, 'all');
    $selKey = $this->key($cacheKey, 'sel');
    $dataKey = $this->key($cacheKey, 'data');

    $maxScore = 0;
    foreach ($this->redis->zRange($allKey, -1, -1, TRUE) as $lastElem => $lastScore) {
      $maxScore = $lastScore;
    }
    return [$allKey, $dataKey, $selKey, $maxScore];
  }

  /**
   * @inheritDoc
   */
  public function cleanup() {
    // Redis already handles cleaning up stale keys.
    return;
  }

}
