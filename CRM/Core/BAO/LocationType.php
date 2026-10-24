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
   * @deprecated
   *   No longer used: the default location type is cached in \Civi::$statics.
   */
  public static $_defaultLocationType = NULL;

  /**
   * @var int|null
   * @deprecated
   *   No longer used: the billing location type is cached in \Civi::$statics.
   */
  public static $_billingLocationType = NULL;

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * Retrieve the default location_type.
   *
   * @return CRM_Core_DAO_LocationType|null
   *   The default location type object on success,
   *                          null otherwise
   */
  public static function &getDefault() {
    if (!isset(\Civi::$statics[__CLASS__]['default'])) {
      $params = ['is_default' => 1];
      $defaults = [];
      \Civi::$statics[__CLASS__]['default'] = self::retrieve($params, $defaults);
    }
    return \Civi::$statics[__CLASS__]['default'];
  }

  /**
   * Get ID of billing location type.
   *
   * @return int
   */
  public static function getBilling() {
    if (!isset(\Civi::$statics[__CLASS__]['billing'])) {
      $locationTypes = CRM_Core_DAO_Address::buildOptions('location_type_id', 'validate');
      \Civi::$statics[__CLASS__]['billing'] = array_search('Billing', $locationTypes);
    }
    return \Civi::$statics[__CLASS__]['billing'];
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
    elseif (in_array($event->action, ['create', 'edit'])) {
      if (!empty($event->params['is_default'])) {
        $query = "UPDATE civicrm_location_type SET is_default = 0";
        CRM_Core_DAO::executeQuery($query);
      }
    }
    // Todo: This was moved from CRM_Admin_Form_LocationType::postProcess but is probably unnecessarily broad.
    Civi::rebuild(['system' => TRUE])->execute();
  }

}
