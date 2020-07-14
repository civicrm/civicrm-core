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
 * Class CRM_Extension_ShimApi
 *
 * For every extension which loads a shim, there should be one instance of the
 * ShimApi. This is the record offered to shim functions.
 */
class CRM_Extension_ShimApi {

  // EX: How to veto a shim-file
  // private static $vetoList = ['legacy-example'];

  /**
   * Load the shims listed in the map.
   *
   * @param array $shimMap
   *   Ex: [0 =>['key' => 'org.civicrm.flexmailer', 'file' => 'flexmailer', 'shimFiles' => ['foo' => 'shims/foo.shim.php']]]
   * @return array
   *   A copy of the $shimMap, possibly with some changes applied.
   */
  public static function loadMap($shimMap) {
    foreach ($shimMap as &$shimMapItem) {
      $shimApi = new static();
      $shimApi->raw = $shimMapItem;
      $shimApi->longName = $shimMapItem['longName'];
      $shimApi->shortName = $shimMapItem['shortName'];
      $shimApi->bootCache = $shimMapItem['bootCache'];
      $shimApi->path = CRM_Extension_System::singleton()->getFullContainer()->getPath($shimApi->longName);

      foreach ($shimMapItem['shimFiles'] as $shimName => $shimFile) {
        // TODO: If $shimName has been assimilated into core, then skip or substitute it. At the moment, none have been assimilated.
        // if (in_array($shimName, static::$vetoList)) continue;

        $shim = include $shimApi->path . '/' . $shimFile;
        if ($shim) {
          $shim($shimApi);
        }
      }

      $shimMapItem['bootCache'] = $shimApi->bootCache;
    }

    return $shimMap;
  }

  /**
   * @var string
   *
   * Ex: 'org.civicrm.flexmailer'
   */
  public $longName;

  /**
   * @var string
   *
   * Ex: 'flexmailer'
   */
  public $shortName;

  /**
   * @var string|null
   *
   * Ex: '/var/www/modules/civicrm/ext/flexmailer'.
   */
  public $path;

  /**
   * @var array
   */
  private $bootCache;

  /**
   * Define a persistent value in the extension's boot-cache.
   *
   * This value is retained as part of the boot-cache. It will be loaded
   * very quickly (eg via php op-code caching). However, as a trade-off,
   * you will not be able to change/reset at runtime - it will only
   * reset in response to a system-wide flush or redeployment.
   *
   * Ex: $shimApi->define('initTime', function() { return time(); });
   *
   * @param string $key
   * @param mixed $callback
   * @return mixed
   *   The value of $callback (either cached or fresh)
   */
  public function define($key, $callback) {
    if (!isset($this->bootCache[$key])) {
      $this->bootCache[$key] = $callback($this);
    }
    return $this->bootCache[$key];
  }

}
