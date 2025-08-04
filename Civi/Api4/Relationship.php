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
 * Relationship entity.
 *
 * @see https://docs.civicrm.org/user/en/latest/organising-your-data/relationships/
 * @searchable none
 * @searchFields contact_id_a.sort_name,relationship_type_id.label_a_b,contact_id_b.sort_name
 * @since 5.19
 * @package Civi\Api4
 */
class Relationship extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\Relationship\Get
   */
  public static function get($checkPermissions = TRUE) {
    return (new Action\Relationship\Get(static::class, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Relationship\Create
   */
  public static function create($checkPermissions = TRUE) {
    return (new Action\Relationship\Create(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Relationship\Save
   */
  public static function save($checkPermissions = TRUE) {
    return (new Action\Relationship\Save(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Relationship\Update
   */
  public static function update($checkPermissions = TRUE) {
    return (new Action\Relationship\Update(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return array
   */
  public static function permissions(): array {
    return [
      'meta' => ['access CiviCRM'],
      // get managed by CRM_Core_BAO::addSelectWhereClause
      // create/update/delete managed by CRM_Contact_BAO_Relationship::_checkAccess
      'default' => [],
    ];
  }

}
