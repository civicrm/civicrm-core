<?php

/**
 * Class CRM_Core_DAO_Factory
 */
class CRM_Core_DAO_Factory {

  static $_classes = array(
    'Domain' => 'data',
    'Country' => 'singleton',
    'County' => 'singleton',
    'StateProvince' => 'singleton',
    'GeoCoord' => 'singleton',
    'IMProvider' => 'singleton',
    'MobileProvider' => 'singleton',
  );

  static $_prefix = array(
    'business' => 'CRM/Core/BAO/',
    'data' => 'CRM/Core/DAO/',
  );

  static $_suffix = '.php';

  /**
   * @param string $className
   *
   * @return mixed
   * @throws Exception
   */
  static function &create($className) {
    $type = CRM_Utils_Array::value($className, self::$_classes);
    if (!$type) {
      CRM_Core_Error::fatal("class $className not found");
    }

    $file = self::$_prefix[$type] . $className;
    $class = str_replace('/', '_', $file);

    require_once($file . self::$_suffix);

    if ($type == 'singleton') {
      $newObj = $class::singleton();
    }
    else {
      // this is either 'business' or 'data'
      $newObj = new $class;
    }

    return $newObj;
  }
}
