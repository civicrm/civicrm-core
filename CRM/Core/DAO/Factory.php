<?php

/**
 * Class CRM_Core_DAO_Factory
 */
class CRM_Core_DAO_Factory {

  public static $_classes = [
    'Domain' => 'data',
    'Country' => 'singleton',
    'County' => 'singleton',
    'StateProvince' => 'singleton',
    'GeoCoord' => 'singleton',
    'IMProvider' => 'singleton',
    'MobileProvider' => 'singleton',
  ];

  public static $_prefix = [
    'business' => 'CRM_Core_BAO_',
    'data' => 'CRM_Core_DAO_',
  ];

  /**
   * @param string $className
   *
   * @return mixed
   * @throws CRM_Core_Exception
   */
  public static function create($className) {
    $type = self::$_classes[$className] ?? NULL;
    if (!$type) {
      throw new CRM_Core_Exception("class $className not found");
    }

    $class = self::$_prefix[$type] . $className;

    if ($type == 'singleton') {
      $newObj = $class::singleton();
    }
    else {
      // this is either 'business' or 'data'
      $newObj = new $class();
    }

    return $newObj;
  }

}
