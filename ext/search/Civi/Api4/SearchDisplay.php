<?php
namespace Civi\Api4;

/**
 * SearchDisplay entity.
 *
 * Provided by the Search Kit extension.
 *
 * @searchable false
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

}
