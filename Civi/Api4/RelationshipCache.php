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
 * RelationshipCache - readonly table to facilitate joining and finding contacts by relationship.
 *
 * @searchable secondary
 * @searchFields near_contact_id.sort_name,near_relation:label,far_contact_id.sort_name
 * @see \Civi\Api4\Relationship
 * @ui_join_filters near_relation
 * @since 5.29
 * @package Civi\Api4
 */
class RelationshipCache extends Generic\AbstractEntity {
  use Generic\Traits\EntityBridge;

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
   * @param bool $checkPermissions
   * @return Action\RelationshipCache\Rebuild
   */
  public static function rebuild($checkPermissions = TRUE) {
    return (new Action\RelationshipCache\Rebuild(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return array
   */
  public static function getInfo() {
    $info = parent::getInfo();
    $info['bridge_title'] = ts('Relationship');
    // This entity uses DAOGetAction so counts as a DAOEntity
    $info['type'][0] = 'DAOEntity';
    $info['bridge'] = [
      'near_contact_id' => [
        'to' => 'far_contact_id',
        'label' => ts('Related Contacts'),
        'description' => ts('One or more related contacts'),
      ],
    ];
    if (\CRM_Core_Component::isEnabled('CiviCase')) {
      $info['bridge']['case_id'] = [
        'to' => 'far_contact_id',
        'label' => ts('Case Roles'),
        'description' => ts('Cases in which this contact has a role'),
      ];
    }
    return $info;
  }

}
