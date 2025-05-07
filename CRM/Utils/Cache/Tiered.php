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
 * Class CRM_Utils_Cache_Tiered
 *
 * `Tiered` implements a hierarchy of fast and slow caches. For example, you
 * might have a configuration in which:
 *
 *   - A local/in-memory array caches info for up to 1 minute (60s).
 *   - A Redis cache retains info for up to 10 minutes (600s).
 *   - A SQL cache retains info for up to 1 hour (3600s).
 *
 * Cached data will be written to all three tiers. When reading, you'll hit the
 * fastest available tier.
 *
 * The example would be created with:
 *
 * $cache = new CRM_Utils_Cache_Tiered([
 *   new CRM_Utils_Cache_ArrayCache(...),
 *   new CRM_Utils_Cache_Redis(...),
 *   new CRM_Utils_Cache_SqlGroup(...),
 * ], [60, 600, 3600]);
 *
 * Note:
 *  - Correctly implementing PSR-16 leads to a small amount of CPU+mem overhead.
 *    If you need an extremely high number of re-reads within a thread and can live
 *    with only two tiers, try CRM_Utils_Cache_ArrayDecorator or
 *    CRM_Utils_Cache_FastArrayDecorator instead.
 *  - With the exception of unit-testing, you should not access the underlying
 *    tiers directly. The data-format may be different than your expectation.
 */
class CRM_Utils_Cache_Tiered implements CRM_Utils_Cache_Interface {

  // TODO Consider native implementation.
  use CRM_Utils_Cache_NaiveMultipleTrait;

  /**
   * @var array
   *   Array(int $tierNum => int $seconds).
   */
  protected $maxTimeouts;

  /**
   * @var array
   *   List of cache instances, with fastest/closest first.
   *   Array(int $tierNum => CRM_Utils_Cache_Interface).
   */
  protected $tiers;

  /**
   * CRM_Utils_Cache_Tiered constructor.
   * @param array $tiers
   *   List of cache instances, with fastest/closest first.
   *   Must be indexed numerically (0, 1, 2...).
   * @param array $maxTimeouts
   *   A list of maximum timeouts for each cache-tier.
   *   There must be at least one value in this array.
   *   If timeouts are omitted for slower tiers, they are filled in with the last value.
   * @throws CRM_Core_Exception
   */
  public function __construct($tiers, $maxTimeouts = [86400]) {
    $this->tiers = $tiers;
    $this->maxTimeouts = [];

    foreach ($tiers as $k => $tier) {
      $this->maxTimeouts[$k] = isset($maxTimeouts[$k])
        ? $maxTimeouts[$k]
        : $this->maxTimeouts[$k - 1];
    }

    for ($far = 1; $far < count($tiers); $far++) {
      $near = $far - 1;
      if ($this->maxTimeouts[$near] > $this->maxTimeouts[$far]) {
        throw new \CRM_Core_Exception("Invalid configuration: Near cache #{$near} has longer timeout than far cache #{$far}");
      }
    }
  }

  public function set($key, $value, $ttl = NULL) {
    if ($ttl !== NULL & !is_int($ttl) && !($ttl instanceof DateInterval)) {
      throw new CRM_Utils_Cache_InvalidArgumentException("Invalid cache TTL");
    }
    foreach ($this->tiers as $tierNum => $tier) {
      /** @var CRM_Utils_Cache_Interface $tier */
      $effTtl = $this->getEffectiveTtl($tierNum, $ttl);
      $expiresAt = CRM_Utils_Date::convertCacheTtlToExpires($effTtl, $this->maxTimeouts[$tierNum]);
      if (!$tier->set($key, [0 => $expiresAt, 1 => $value], $effTtl)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  public function get($key, $default = NULL) {
    $nack = CRM_Utils_Cache::nack();
    foreach ($this->tiers as $readTierNum => $tier) {
      /** @var CRM_Utils_Cache_Interface $tier */
      $wrapped = $tier->get($key, $nack);
      if ($wrapped !== $nack && $wrapped[0] >= CRM_Utils_Time::getTimeRaw()) {
        list ($parentExpires, $value) = $wrapped;
        // (Re)populate the faster caches; and then return the value we found.
        for ($i = 0; $i < $readTierNum; $i++) {
          $now = CRM_Utils_Time::getTimeRaw();
          $effExpires = min($parentExpires, $now + $this->maxTimeouts[$i]);
          $this->tiers[$i]->set($key, [0 => $effExpires, 1 => $value], $effExpires - $now);
        }
        return $value;
      }
    }
    return $default;
  }

  public function delete($key) {
    foreach ($this->tiers as $tier) {
      /** @var CRM_Utils_Cache_Interface $tier */
      $tier->delete($key);
    }
    return TRUE;
  }

  public function flush() {
    return $this->clear();
  }

  public function clear() {
    foreach ($this->tiers as $tier) {
      /** @var CRM_Utils_Cache_Interface $tier */
      if (!$tier->clear()) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    foreach ($this->tiers as $tier) {
      /** @var CRM_Utils_Cache_Interface $tier */
      $tier->garbageCollection();
    }
    return TRUE;
  }

  public function has($key) {
    $nack = CRM_Utils_Cache::nack();
    foreach ($this->tiers as $tier) {
      /** @var CRM_Utils_Cache_Interface $tier */
      $wrapped = $tier->get($key, $nack);
      if ($wrapped !== $nack && $wrapped[0] > CRM_Utils_Time::getTimeRaw()) {
        return TRUE;
      }
    }
    return FALSE;
  }

  protected function getEffectiveTtl($tierNum, $ttl) {
    if ($ttl === NULL) {
      return $this->maxTimeouts[$tierNum];
    }
    else {
      if ($ttl instanceof \DateInterval) {
        $ttl = date_add(new DateTime(), $ttl)->getTimestamp() - time();
      }
      return min($this->maxTimeouts[$tierNum], $ttl);
    }
  }

}
