<?php
// phpcs:disable
use CRM_Cors_ExtensionUtil as E;
// phpcs:enable

class CRM_Cors_BAO_CorsRule extends CRM_Cors_DAO_CorsRule {

  /**
   * Create a new CorsRule based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Cors_DAO_CorsRule|NULL
   */
  /*
  public static function create($params) {
    $className = 'CRM_Cors_DAO_CorsRule';
    $entityName = 'CorsRule';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }
  */

}
