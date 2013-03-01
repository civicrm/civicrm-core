<?php
// $Id$

class CRM_Contact_DAO_Factory {

  static $_classes = array(
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
  );

  static $_prefix = array(
    'business' => 'CRM/Contact/BAO/',
    'data' => 'CRM/Contact/DAO/',
  );

  static $_suffix = '.php';

  static $_preCall = array(
    'singleton' => '',
    'business' => 'new',
    'data' => 'new',
  );

  static $_extCall = array(
    'singleton' => '::singleton',
    'business' => '',
    'data' => '',
  );


  static
  function &create($className) {
    $type = CRM_Utils_Array::value($className, self::$_classes);
    if (!$type) {
      return CRM_Core_DAO_Factory::create($className);
    }

    $file = self::$_prefix[$type] . $className;
    $class = str_replace('/', '_', $file);

    require_once ($file . self::$_suffix);

    $newObj = eval(sprintf("return %s %s%s();",
        self::$_preCall[$type],
        $class,
        self::$_extCall[$type]
      ));

    return $newObj;
  }
}

