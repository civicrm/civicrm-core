<?php

namespace Civi\Api4;

/**
 * Virtual API entities provided by SearchDisplays of type "entity"
 * @package Civi\Api4
 */
class SKEntity {

  /**
   * @param string $displayEntity
   * @param bool $checkPermissions
   *
   * @return \Civi\Api4\Generic\DAOGetFieldsAction
   */
  public static function getFields(string $displayEntity, bool $checkPermissions = TRUE): Generic\DAOGetFieldsAction {
    return (new Generic\DAOGetFieldsAction('SK_' . $displayEntity, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param string $displayEntity
   * @param bool $checkPermissions
   * @return \Civi\Api4\Generic\DAOGetAction
   * @throws \CRM_Core_Exception
   */
  public static function get(string $displayEntity, bool $checkPermissions = TRUE): Generic\DAOGetAction {
    return (new Generic\DAOGetAction('SK_' . $displayEntity, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param string $displayEntity
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\SKEntity\Refresh
   * @throws \CRM_Core_Exception
   */
  public static function refresh(string $displayEntity, bool $checkPermissions = TRUE): Action\SKEntity\Refresh {
    return (new Action\SKEntity\Refresh('SK_' . $displayEntity, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param string $displayEntity
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\SKEntity\GetRefreshDate
   * @throws \CRM_Core_Exception
   */
  public static function getRefreshDate(string $displayEntity, bool $checkPermissions = TRUE): Action\SKEntity\GetRefreshDate {
    return (new Action\SKEntity\GetRefreshDate('SK_' . $displayEntity, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param string $displayEntity
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\GetActions
   */
  public static function getActions(string $displayEntity, bool $checkPermissions = TRUE): Action\GetActions {
    return (new Action\GetActions('SK_' . $displayEntity, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param string $displayEntity
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\GetLinks
   */
  public static function getLinks(string $displayEntity, bool $checkPermissions = TRUE): Action\GetLinks {
    return (new Action\GetLinks('SK_' . $displayEntity, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param string $displayEntity
   * @return \Civi\Api4\Generic\CheckAccessAction
   * @throws \CRM_Core_Exception
   */
  public static function checkAccess(string $displayEntity): Generic\CheckAccessAction {
    return new Generic\CheckAccessAction('SK_' . $displayEntity, __FUNCTION__);
  }

  /**
   * @return array
   */
  public static function permissions($entityName): array {
    $permissions = [
      'meta' => ['access CiviCRM'],
      'default' => ['administer CiviCRM'],
      'refresh' => ['administer search_kit'],
      'getRefreshDate' => ['administer search_kit'],
    ];
    // Permissions based on search display
    [, $displayName] = explode('_', $entityName, 2);
    $query = \CRM_Utils_SQL_Select::from('civicrm_search_display');
    $query->select(['settings']);
    $query->where('type = "entity"');
    $query->where('name = @name', ['@name' => $displayName]);
    $settings = \CRM_Core_DAO::singleValueQuery($query->toSQL());
    if ($settings) {
      $settings = json_decode($settings, TRUE);
    }
    if (!empty($settings['entity_permission'])) {
      $permissions['default'] = (array) $settings['entity_permission'];
      // If the operator is OR, use a nested array per `CRM_Core_Permission::check`
      if (($settings['entity_permission_operator'] ?? 'AND') === 'OR') {
        $permissions['default'] = [$permissions['default']];
      }
    }

    return $permissions;
  }

}
