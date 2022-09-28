<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_BAO_LocationType extends CRM_Core_DAO_LocationType implements \Civi\Core\HookInterface {

  /**
   * @var CRM_Core_DAO_LocationType|null
   */
  public static $_defaultLocationType = NULL;

  /**
   * @var int|null
   */
  public static $_billingLocationType = NULL;

  /**
   * Retrieve DB object and copy to defaults array.
   *
   * @param array $params
   *   Array of criteria values.
   * @param array $defaults
   *   Array to be populated with found values.
   *
   * @return self|null
   *   The DAO object, if found.
   *
   * @deprecated
   */
  public static function retrieve($params, &$defaults) {
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return bool
   *   true if we found and updated the object, else false
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_LocationType', $id, 'is_active', $is_active);
  }

  /**
   * Retrieve the default location_type.
   *
   * @return CRM_Core_DAO_LocationType|null
   *   The default location type object on success,
   *                          null otherwise
   */
  public static function &getDefault() {
    if (self::$_defaultLocationType == NULL) {
      $params = ['is_default' => 1];
      $defaults = [];
      self::$_defaultLocationType = self::retrieve($params, $defaults);
    }
    return self::$_defaultLocationType;
  }

  /**
   * Get ID of billing location type.
   *
   * @return int
   */
  public static function getBilling() {
    if (self::$_billingLocationType == NULL) {
      $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id', [], 'validate');
      self::$_billingLocationType = array_search('Billing', $locationTypes);
    }
    return self::$_billingLocationType;
  }

  /**
   * Add a Location Type.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   *
   *
   * @return object
   */
  public static function create(&$params) {
    if (empty($params['id'])) {
      $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
      $params['is_default'] = CRM_Utils_Array::value('is_default', $params, FALSE);
      $params['is_reserved'] = CRM_Utils_Array::value('is_reserved', $params, FALSE);
    }

    $locationType = new CRM_Core_DAO_LocationType();
    $locationType->copyValues($params);
    if (!empty($params['is_default'])) {
      $query = "UPDATE civicrm_location_type SET is_default = 0";
      CRM_Core_DAO::executeQuery($query);
    }

    $locationType->save();
    return $locationType;
  }

  /**
   * Delete location Types.
   *
   * @param int $locationTypeId
   * @deprecated
   */
  public static function del($locationTypeId) {
    static::deleteRecord(['id' => $locationTypeId]);
  }

  /**
   * Callback for hook_civicrm_pre().
   * @param \Civi\Core\Event\PreEvent $event
   * @throws CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    // When deleting a location type, delete related records
    if ($event->action === 'delete') {
      foreach (['Address', 'IM', 'Email', 'Phone'] as $entity) {
        civicrm_api4($entity, 'delete', [
          'checkPermissions' => FALSE,
          'where' => [['location_type_id', '=', $event->id]],
        ]);
      }
    }
  }

}
