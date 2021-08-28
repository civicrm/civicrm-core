<?php
namespace Civi\Api4;

/**
 * SearchDisplay entity.
 *
 * Provided by the Search Kit extension.
 *
 * @searchable none
 * @package Civi\Api4
 */
class SearchDisplay extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\SearchDisplay\Run
   */
  public static function run($checkPermissions = TRUE) {
    return (new Action\SearchDisplay\Run(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\SearchDisplay\GetSearchTasks
   */
  public static function getSearchTasks($checkPermissions = TRUE) {
    return (new Action\SearchDisplay\GetSearchTasks(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\SearchDisplay\Run
   */
  public static function download($checkPermissions = TRUE) {
    return (new Action\SearchDisplay\Download(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  public static function permissions() {
    $permissions = parent::permissions();
    $permissions['default'] = ['administer CiviCRM data'];
    $permissions['get'] = ['access CiviCRM'];
    $permissions['getSearchTasks'] = ['access CiviCRM'];
    // Permission for run action is checked internally
    $permissions['run'] = [];
    return $permissions;
  }

}
