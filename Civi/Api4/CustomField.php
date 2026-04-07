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

/**
 * CustomField entity.
 *
 * @see https://docs.civicrm.org/user/en/latest/organising-your-data/creating-custom-fields/
 * @searchable secondary
 * @orderBy weight
 * @groupWeightsBy custom_group_id
 * @since 5.19
 * @package Civi\Api4
 */
class CustomField extends Generic\DAOEntity {
  use Generic\Traits\ManagedEntity;
  use Generic\Traits\SortableEntity;

  /**
   * @param bool $checkPermissions
   * @return Action\CustomField\Get
   */
  public static function get($checkPermissions = TRUE) {
    return (new Action\CustomField\Get(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\CustomField\Create
   */
  public static function create($checkPermissions = TRUE) {
    return (new Action\CustomField\Create(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\CustomField\Save
   */
  public static function save($checkPermissions = TRUE) {
    return (new Action\CustomField\Save(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\CustomField\Update
   */
  public static function update($checkPermissions = TRUE) {
    return (new Action\CustomField\Update(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
