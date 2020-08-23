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
 * Define some common, global lists of resources.
 */
class CRM_Core_Resources_Common {

  const REGION = 'html-header';

  /**
   * The 'bundle.coreStyles' service is a collection of resources used on some
   * non-Civi pages (wherein Civi may be mixed-in).
   *
   * @param string $name
   *   i.e. 'coreStyles'
   * @return \CRM_Core_Resources_Bundle
   */
  public static function createStyleBundle($name) {
    $bundle = new CRM_Core_Resources_Bundle($name);
    // TODO
    CRM_Utils_Hook::alterBundle($bundle);
    self::useRegion($bundle, self::REGION);
    return $bundle;
  }

  /**
   * The 'bundle.coreResources' service is a collection of resources
   * shared by Civi pages (ie pages where Civi controls rendering).
   *
   * @param string $name
   *   i.e. 'coreResources'
   * @return \CRM_Core_Resources_Bundle
   */
  public static function createFullBundle($name) {
    $bundle = new CRM_Core_Resources_Bundle($name);
    // TODO
    CRM_Utils_Hook::alterBundle($bundle);
    self::useRegion($bundle, self::REGION);
    return $bundle;
  }

  /**
   * Ensure that all elements of the bundle are in the same region.
   *
   * @param CRM_Core_Resources_Bundle $bundle
   * @param string $region
   * @return CRM_Core_Resources_Bundle
   */
  protected static function useRegion($bundle, $region) {
    $bundle->filter(function ($s) use ($region) {
      if ($s['type'] !== 'settings' && !isset($s['region'])) {
        $s['region'] = $region;
      }
      return $s;
    });
    return $bundle;
  }

}
