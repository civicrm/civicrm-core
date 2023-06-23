<?php
use CRM_Standaloneusers_ExtensionUtil as E;

class CRM_Standaloneusers_BAO_RolePermission extends CRM_Standaloneusers_DAO_RolePermission {

  /**
   * Create a new RolePermission based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Standaloneusers_DAO_RolePermission|NULL
   *
  public static function create($params) {
    $className = 'CRM_Standaloneusers_DAO_RolePermission';
    $entityName = 'RolePermission';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  } */

}
