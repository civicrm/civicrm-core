<?php
namespace CiviMix\Schema;

/**
 * This object is known as $GLOBALS['CiviMixSchema']. It is a reloadable service-object.
 * (It may be reloaded if you enable a new extension that includes an upgraded copy.)
 */
return new class() {

  /**
   * @param string $extensionKey
   *   Ex: 'org.civicrm.flexmailer'
   * @return \CiviMix\Schema\SchemaHelperInterface
   */
  public function getHelper(string $extensionKey) {
    $store = &\Civi::$statics['CiviMixSchema-helpers'];
    if (!isset($store[$extensionKey])) {
      $class = get_class(require __DIR__ . '/SchemaHelper.php');
      $store[$extensionKey] = new $class($extensionKey);
    }
    return $store[$extensionKey];
  }

};
