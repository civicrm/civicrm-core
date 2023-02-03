<?php
use CRM_Standaloneusers_ExtensionUtil as E;

class CRM_Standaloneusers_BAO_User extends CRM_Standaloneusers_DAO_User {

  /**
   * Create a new User based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Standaloneusers_DAO_User|NULL
   *
  public static function create($params) {
    $className = 'CRM_Standaloneusers_DAO_User';
    $entityName = 'User';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  } */

}
