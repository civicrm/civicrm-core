<?php
namespace CiviMix\Schema;

/**
 * This object is known as $GLOBALS['CiviMixSchema']. It is a reloadable service-object.
 * (It may be reloaded if you enable a new extension that includes an upgraded copy.)
 */
return new class() {

  /**
   * @var string
   *   Regular expression. Note the 2 groupings. $m[1] identifies a per-extension namespace. $m[2] identifies the actual class.
   */
  private $regex = ';^CiviMix\\\Schema\\\(\w+)\\\(AutomaticUpgrader|DAO)$;';

  /**
   * If someone requests a class like:
   *
   *    CiviMix\Schema\MyExt\AutomaticUpgrader
   *
   * then load the latest version of:
   *
   *    civimix-schema/src/Helper.php
   */
  public function loadClass(string $class) {
    if (preg_match($this->regex, $class, $m)) {
      $absPath = __DIR__ . DIRECTORY_SEPARATOR . $m[2] . '.php';
      class_alias(get_class(require $absPath), $class);
    }
  }

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
