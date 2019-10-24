<?php

/**
 * Class CRM_Contact_DAO_Factory
 */
class CRM_Contact_DAO_Factory {

  public static $_classes = [
    'Address' => 'data',
    'Contact' => 'data',
    'Email' => 'data',
    'Household' => 'data',
    'IM' => 'data',
    'Individual' => 'data',
    'Location' => 'data',
    'LocationType' => 'data',
    'Organization' => 'data',
    'Phone' => 'data',
    'Relationship' => 'data',
  ];

  public static $_prefix = [
    'business' => 'CRM_Contact_BAO_',
    'data' => 'CRM_Contact_DAO_',
  ];

  /**
   * @param string $className
   *
   * @return mixed
   */
  public static function create($className) {
    $type = CRM_Utils_Array::value($className, self::$_classes);
    if (!$type) {
      return CRM_Core_DAO_Factory::create($className);
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
