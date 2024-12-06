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
 * Contacts - Individuals, Organizations, Households.
 *
 * This is the central entity in the CiviCRM database, and links to
 * many other entities (Email, Phone, Participant, etc.).
 *
 * Creating a new contact requires at minimum a name or email address.
 *
 * @see https://docs.civicrm.org/user/en/latest/organising-your-data/contacts/
 * @searchable primary
 * @orderBy sort_name
 * @searchFields sort_name
 * @iconField contact_sub_type:icon,contact_type:icon
 * @since 5.19
 * @package Civi\Api4
 */
class Contact extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\Contact\Create
   */
  public static function create($checkPermissions = TRUE) {
    return (new Action\Contact\Create(self::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Contact\Update
   */
  public static function update($checkPermissions = TRUE) {
    return (new Action\Contact\Update(self::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Contact\Save
   */
  public static function save($checkPermissions = TRUE) {
    return (new Action\Contact\Save(self::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Contact\Delete
   */
  public static function delete($checkPermissions = TRUE) {
    return (new Action\Contact\Delete(self::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Contact\GetChecksum
   */
  public static function getChecksum($checkPermissions = TRUE) {
    return (new Action\Contact\GetChecksum(self::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Contact\ValidateChecksum
   */
  public static function validateChecksum($checkPermissions = TRUE) {
    return (new Action\Contact\ValidateChecksum(self::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Contact\GetDuplicates
   */
  public static function getDuplicates($checkPermissions = TRUE) {
    return (new Action\Contact\GetDuplicates(self::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Contact\MergeDuplicates
   */
  public static function mergeDuplicates($checkPermissions = TRUE) {
    return (new Action\Contact\MergeDuplicates(self::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  protected static function getDaoName(): string {
    // Child classes (Individual, Organization, Household) need this.
    return 'CRM_Contact_DAO_Contact';
  }

  /**
   * @inheritDoc
   */
  public static function getInfo(): array {
    $info = parent::getInfo();
    $contactType = static::getEntityName();
    // Adjust info for child classes (Individual, Organization, Household)
    if ($contactType !== 'Contact') {
      $contactTypeInfo = \CRM_Contact_BAO_ContactType::getContactType($contactType);
      $info['icon'] = $contactTypeInfo['icon'] ?? $info['icon'];
      $info['type'] = ['DAOEntity', 'ContactType'];
      $info['description'] = ts('Contacts of type %1.', [1 => $contactTypeInfo['label']]);
      // This forces the value into get and create api actions
      $info['where'] = ['contact_type' => $contactType];
    }
    return $info;
  }

  /**
   * Override base method so it can be shared with Individual, Household, Organization APIs.
   */
  public static function permissions() {
    $allPermissions = \CRM_Core_Permission::getEntityActionPermissions();
    $permissions = ($allPermissions['contact'] ?? []) + $allPermissions['default'];
    // Checksums are meant for anonymous use
    $permissions['validateChecksum'] = [];
    return $permissions;
  }

}
