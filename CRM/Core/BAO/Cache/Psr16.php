<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * Class CRM_Core_BAO_Cache_Psr16
 *
 * This optional adapter to help phase-out CRM_Core_BAO_Cache.
 *
 * In effect, it changes the default behavior of legacy cache-consumers
 * (CRM_Core_BAO_Cache) so that they store in the best-available tier
 * (Reds/Memcache or SQL or array) rather than being hard-coded to SQL.
 *
 * It basically just calls "CRM_Utils_Cache::create()" for each $group and
 * maps the getItem/setItem to get()/set().
 */
class CRM_Core_BAO_Cache_Psr16 {

  /**
   * Original BAO behavior did not do expiration. PSR-16 providers have
   * diverse defaults. To provide some consistency, we'll pick a long(ish)
   * TTL for everything that goes through the adapter.
   */
  const TTL = 86400;

  /**
   * @param string $group
   * @return CRM_Utils_Cache_Interface
   */
  protected static function getGroup($group) {
    if (!isset(Civi::$statics[__CLASS__][$group])) {
      if (!in_array($group, self::getLegacyGroups())) {
        Civi::log()
          ->warning('Unrecognized BAO cache group ({group}). This should work generally, but data may not be flushed in some edge-cases. Consider migrating explicitly to PSR-16.', [
            'group' => $group,
          ]);
      }

      $cache = CRM_Utils_Cache::create([
        'name' => "bao_$group",
        'type' => ['*memory*', 'SqlGroup', 'ArrayCache'],
        // We're replacing CRM_Core_BAO_Cache, which traditionally used a front-cache
        // that was not aware of TTLs. So it seems more consistent/performant to
        // use 'fast' here.
        'withArray' => 'fast',
      ]);
      Civi::$statics[__CLASS__][$group] = $cache;
    }
    return Civi::$statics[__CLASS__][$group];
  }

  /**
   * Retrieve an item from the DB cache.
   *
   * @param string $group
   *   (required) The group name of the item.
   * @param string $path
   *   (required) The path under which this item is stored.
   * @param int $componentID
   *   The optional component ID (so componenets can share the same name space).
   *
   * @return object
   *   The data if present in cache, else null
   */
  public static function getItem($group, $path, $componentID = NULL) {
    // TODO: Generate a general deprecation notice.
    if ($componentID) {
      Civi::log()
        ->warning('getItem({group},{path},...) uses unsupported componentID. Consider migrating explicitly to PSR-16.', [
          'group' => $group,
          'path' => $path,
        ]);
    }
    return self::getGroup($group)->get(CRM_Core_BAO_Cache::cleanKey($path));
  }

  /**
   * Retrieve all items in a group.
   *
   * @param string $group
   *   (required) The group name of the item.
   * @param int $componentID
   *   The optional component ID (so componenets can share the same name space).
   *
   * @throws CRM_Core_Exception
   */
  public static function &getItems($group, $componentID = NULL) {
    // Based on grepping universe, this function is not currently used.
    // Moreover, it's hard to implement in PSR-16. (We'd have to extend the
    // interface.) Let's wait and see if anyone actually needs this...
    throw new \CRM_Core_Exception('Not implemented: CRM_Core_BAO_Cache_Psr16::getItems');
  }

  /**
   * Store an item in the DB cache.
   *
   * @param object $data
   *   (required) A reference to the data that will be serialized and stored.
   * @param string $group
   *   (required) The group name of the item.
   * @param string $path
   *   (required) The path under which this item is stored.
   * @param int $componentID
   *   The optional component ID (so componenets can share the same name space).
   */
  public static function setItem(&$data, $group, $path, $componentID = NULL) {
    // TODO: Generate a general deprecation notice.

    if ($componentID) {
      Civi::log()
        ->warning('setItem({group},{path},...) uses unsupported componentID. Consider migrating explicitly to PSR-16.', [
          'group' => $group,
          'path' => $path,
        ]);
    }
    self::getGroup($group)
      ->set(CRM_Core_BAO_Cache::cleanKey($path), $data, self::TTL);
  }

  /**
   * Delete all the cache elements that belong to a group OR delete the entire cache if group is not specified.
   *
   * @param string $group
   *   The group name of the entries to be deleted.
   * @param string $path
   *   Path of the item that needs to be deleted.
   */
  public static function deleteGroup($group = NULL, $path = NULL) {
    // FIXME: Generate a general deprecation notice.

    if ($path) {
      self::getGroup($group)->delete(CRM_Core_BAO_Cache::cleanKey($path));
    }
    else {
      self::getGroup($group)->clear();
    }
  }

  /**
   * Cleanup any caches that we've mapped.
   *
   * Traditional SQL-backed caches are cleared as a matter of course during a
   * system flush (by way of "TRUNCATE TABLE civicrm_cache"). This provides
   * a spot where the adapter can
   */
  public static function clearDBCache() {
    foreach (self::getLegacyGroups() as $groupName) {
      $group = self::getGroup($groupName);
      $group->clear();
    }
  }

  /**
   * Get a list of known cache-groups
   *
   * @return array
   */
  public static function getLegacyGroups() {
    return [
      // Core
      'CiviCRM Search PrevNextCache',
      'contact fields',
      'navigation',
      'contact groups',
      'custom data',

      // Universe

      // be.chiro.civi.atomfeeds
      'dashboard',

      // biz.jmaconsulting.lineitemedit
      'lineitem-editor',

      // civihr/uk.co.compucorp.civicrm.hrcore
      'HRCore_Info',

      // nz.co.fuzion.entitysetting
      'CiviCRM setting Spec',

      // org.civicrm.multisite
      'descendant groups for an org',
    ];
  }

}
