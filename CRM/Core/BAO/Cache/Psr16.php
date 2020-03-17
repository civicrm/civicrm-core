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
    return self::getGroup($group)->get(CRM_Utils_Cache::cleanKey($path));
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
      ->set(CRM_Utils_Cache::cleanKey($path), $data, self::TTL);
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
      self::getGroup($group)->delete(CRM_Utils_Cache::cleanKey($path));
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
    $groups = [
      // Universe

      // biz.jmaconsulting.lineitemedit
      'lineitem-editor',

      // civihr/uk.co.compucorp.civicrm.hrcore
      'HRCore_Info',

    ];
    // Handle Legacy Multisite caching group.
    $extensions = CRM_Extension_System::singleton()->getManager();
    $multisiteExtensionStatus = $extensions->getStatus('org.civicrm.multisite');
    if ($multisiteExtensionStatus == $extensions::STATUS_INSTALLED) {
      $extension_version = civicrm_api3('Extension', 'get', ['key' => 'org.civicrm.multisite'])['values'][0]['version'];
      if (version_compare($extension_version, '2.7', '<')) {
        Civi::log()->warning(
          'CRM_Core_BAO_Cache_PSR is deprecated for multisite extension, you should upgrade to the latest version to avoid this warning, this code will be removed at the end of 2019',
          ['civi.tag' => 'deprecated']
        );
        $groups[] = 'descendant groups for an org';
      }
    }
    $entitySettingExtensionStatus = $extensions->getStatus('nz.co.fuzion.entitysetting');
    if ($multisiteExtensionStatus == $extensions::STATUS_INSTALLED) {
      $extension_version = civicrm_api3('Extension', 'get', ['key' => 'nz.co.fuzion.entitysetting'])['values'][0]['version'];
      if (version_compare($extension_version, '1.3', '<')) {
        Civi::log()->warning(
          'CRM_Core_BAO_Cache_PSR is deprecated for entity setting extension, you should upgrade to the latest version to avoid this warning, this code will be removed at the end of 2019',
          ['civi.tag' => 'deprecated']
        );
        $groups[] = 'CiviCRM setting Spec';
      }
    }
    $atomFeedsSettingExtensionStatus = $extensions->getStatus('be.chiro.civi.atomfeeds');
    if ($atomFeedsSettingExtensionStatus == $extensions::STATUS_INSTALLED) {
      $extension_version = civicrm_api3('Extension', 'get', ['key' => 'be.chiro.civi.atomfeeds'])['values'][0]['version'];
      if (version_compare($extension_version, '0.1-alpha2', '<')) {
        Civi::log()->warning(
          'CRM_Core_BAO_Cache_PSR is deprecated for Atomfeeds extension, you should upgrade to the latest version to avoid this warning, this code will be removed at the end of 2019',
          ['civi.tag' => 'deprecated']
        );
        $groups[] = 'dashboard';
      }
    }
    return $groups;
  }

}
