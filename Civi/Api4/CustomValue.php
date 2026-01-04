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
 * Provides virtual api entities for every multi-record custom group.
 *
 * This class is different from other apis in that it is not itself an entity, but allows every
 * multi-record custom group to act like an entity.
 *
 * Each action takes the name of the custom group as a parameter, or in traditional syntax the entity is prefixed with 'Custom_'
 *
 * **Ex. OOP:** `\Civi\Api4\CustomValue::get('MyStuff')->addWhere('id', '=', 123);`
 * **Non-OOP:** `civicrm_api4('Custom_MyStuff', 'get', ['where' => [['id', '=', 123]]]);`
 *
 * Note: This class does NOT extend AbstractEntity so it doesn't get mistaken for a "real" entity.
 * @package Civi\Api4
 */
class CustomValue {

  /**
   * @param string $customGroup
   * @param bool $checkPermissions
   * @return \Civi\Api4\Generic\AutocompleteAction
   */
  public static function autocomplete(string $customGroup, $checkPermissions = TRUE) {
    return (new Generic\AutocompleteAction("Custom_$customGroup", __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param string $customGroup
   * @param bool $checkPermissions
   * @return Action\CustomValue\Get
   * @throws \CRM_Core_Exception
   */
  public static function get($customGroup, $checkPermissions = TRUE) {
    return (new Action\CustomValue\Get($customGroup, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param string $customGroup
   * @param bool $checkPermissions
   * @return \Civi\Api4\Generic\DAOGetFieldsAction
   * @throws \CRM_Core_Exception
   */
  public static function getFields($customGroup = NULL, $checkPermissions = TRUE) {
    return (new Generic\DAOGetFieldsAction("Custom_$customGroup", __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param string $customGroup
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\GetLinks
   */
  public static function getLinks($customGroup = NULL, $checkPermissions = TRUE) {
    return (new Action\GetLinks("Custom_$customGroup", __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param string $customGroup
   * @param bool $checkPermissions
   * @return Action\CustomValue\Save
   * @throws \CRM_Core_Exception
   */
  public static function save($customGroup, $checkPermissions = TRUE) {
    return (new Action\CustomValue\Save($customGroup, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param string $customGroup
   * @param bool $checkPermissions
   * @return Action\CustomValue\Create
   * @throws \CRM_Core_Exception
   */
  public static function create($customGroup, $checkPermissions = TRUE) {
    return (new Action\CustomValue\Create($customGroup, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param string $customGroup
   * @param bool $checkPermissions
   * @return Action\CustomValue\Update
   * @throws \CRM_Core_Exception
   */
  public static function update($customGroup, $checkPermissions = TRUE) {
    return (new Action\CustomValue\Update($customGroup, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param string $customGroup
   * @param bool $checkPermissions
   * @return Action\CustomValue\Delete
   * @throws \CRM_Core_Exception
   */
  public static function delete($customGroup, $checkPermissions = TRUE) {
    return (new Action\CustomValue\Delete($customGroup, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param string $customGroup
   * @param bool $checkPermissions
   * @return Generic\BasicReplaceAction
   * @throws \CRM_Core_Exception
   */
  public static function replace($customGroup, $checkPermissions = TRUE) {
    return (new Generic\BasicReplaceAction("Custom_$customGroup", __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param string $customGroup
   * @param bool $checkPermissions
   * @return Action\GetActions
   * @throws \CRM_Core_Exception
   */
  public static function getActions($customGroup = NULL, $checkPermissions = TRUE) {
    return (new Action\GetActions("Custom_$customGroup", __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return \Civi\Api4\Generic\CheckAccessAction
   */
  public static function checkAccess($customGroup) {
    return new Generic\CheckAccessAction("Custom_$customGroup", __FUNCTION__);
  }

  /**
   * @see \Civi\Api4\Generic\AbstractEntity::permissions()
   * @return array
   */
  public static function permissions() {
    // Permissions are managed by ACLs
    return [
      'create' => [],
      'update' => [],
      'delete' => [],
      'get' => [],
    ];
  }

  /**
   * @return \CRM_Core_DAO|string|null
   */
  protected static function getDaoName(): ?string {
    return 'CRM_Core_BAO_CustomValue';
  }

  /**
   * @see \Civi\Api4\Generic\AbstractEntity::getInfo()
   * @return array
   */
  public static function getInfo() {
    return [
      'class' => __CLASS__,
      'type' => ['CustomValue', 'DAOEntity'],
      'searchable' => 'secondary',
      'primary_key' => ['id'],
      'dao' => self::getDaoName(),
      'see' => [
        'https://docs.civicrm.org/user/en/latest/organising-your-data/creating-custom-fields/#multiple-record-fieldsets',
        '\Civi\Api4\CustomGroup',
      ],
    ];
  }

}
