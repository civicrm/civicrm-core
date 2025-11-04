<?php
namespace Civi\Api4;

/**
 * SearchDisplay entity.
 *
 * Provided by the Search Kit extension.
 *
 * @method Action\SearchDisplay\CreateBatch createBatch(bool $checkPemissions)
 * @method Action\SearchDisplay\RunBatch runBatch(bool $checkPemissions)
 *
 * @since 5.32
 * @searchable secondary
 * @package Civi\Api4
 */
class SearchDisplay extends Generic\DAOEntity {

  use Generic\Traits\ManagedEntity;

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
   * @return Action\SearchDisplay\Download
   */
  public static function download($checkPermissions = TRUE) {
    return (new Action\SearchDisplay\Download(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\SearchDisplay\SaveFile
   */
  public static function saveFile($checkPermissions = TRUE) {
    return (new Action\SearchDisplay\SaveFile(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\SearchDisplay\EmailReport
   */
  public static function emailReport($checkPermissions = TRUE) {
    return (new Action\SearchDisplay\EmailReport(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\SearchDisplay\InlineEdit
   */
  public static function inlineEdit($checkPermissions = TRUE) {
    return (new Action\SearchDisplay\InlineEdit(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\SearchDisplay\GetDefault
   */
  public static function getDefault($checkPermissions = TRUE) {
    return (new Action\SearchDisplay\GetDefault(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  public static function permissions() {
    $permissions = parent::permissions();
    $permissions['default'] = ['manage own search_kit'];
    // Anyone with access to CiviCRM can view search displays (but not necessarily the results)
    $permissions['get'] = $permissions['getDefault'] = ['access CiviCRM'];
    // Permission to get search results is checked via `SavedSearchInspectorTrait::checkPermissionToLoadSearch`
    $permissions['run'] = $permissions['download'] = $permissions['inlineEdit'] = $permissions['getSearchTasks'] = [];
    return $permissions;
  }

}
