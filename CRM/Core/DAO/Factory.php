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
   * @throws Exception
   */
  public static function create($className) {
    $type = CRM_Utils_Array::value($className, self::$_classes);
    if (!$type) {
      CRM_Core_Error::fatal("class $className not found");
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
