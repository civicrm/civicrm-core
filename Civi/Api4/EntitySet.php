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
namespace Civi\Api4;

use Civi\Api4\Generic\BasicGetFieldsAction;

/**
 * API to query multiple entities with a UNION.
 *
 * @searchable none
 * @since 5.64
 * @package Civi\Api4
 */
class EntitySet extends Generic\AbstractEntity {

  /**
   * @return \Civi\Api4\Action\EntitySet\Get
   */
  public static function get($checkPermissions = TRUE) {
    return (new Action\EntitySet\Get('EntitySet', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\AutocompleteAction
   */
  public static function autocomplete($checkPermissions = TRUE) {
    return (new Generic\AutocompleteAction('EntitySet', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return \Civi\Api4\Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new BasicGetFieldsAction('EntitySet', __FUNCTION__, function() {
      return [];
    }))->setCheckPermissions($checkPermissions);
  }

  public static function permissions() {
    return [
      'get' => [],
    ];
  }

  /**
   * @param bool $plural
   * @return string
   */
  protected static function getEntityTitle($plural = FALSE) {
    return $plural ? ts('Entity Sets') : ts('Entity Set');
  }

}
