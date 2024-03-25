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
 * CaseRoleCache - API wrapper on Relationship entity to facilitate joining and finding contacts with a role on a case
 *
 * @searchable secondary
 * @searchFields contact_a_id.sort_name,relationship_type_id:label,case_id.subject
 * @see \Civi\Api4\Relationship
 * @ui_join_filters relationship_type_id,is_active
 * @since 5.72
 * @package Civi\Api4
 */
class CaseRole extends Generic\AbstractEntity {
  use Generic\Traits\EntityBridge;

  protected static function getEntityTitle(bool $plural = FALSE): string {
    return $plural ? ts('Case Roles') : ts('Case Role');
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\DAOGetAction
   */
  public static function get($checkPermissions = TRUE) {
    return (new Generic\DAOGetAction(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\DAOGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\DAOGetFieldsAction(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return array
   */
  public static function getInfo() {
    $info = parent::getInfo();
    $info['bridge_title'] = ts('Case Roles');
    $info['bridge'] = [
      'contact_id_b' => [
        'to' => 'case_id',
        'label' => ts('Contact Roles'),
        'description' => ts('Contacts with a role in the this case'),
      ],
    ];
    $info['bridge']['case_id'] = [
      'to' => 'contact_id_b',
      'label' => ts('Case Roles'),
      'description' => ts('Cases in which this contact has a role'),
    ];
    return $info;
  }

}
