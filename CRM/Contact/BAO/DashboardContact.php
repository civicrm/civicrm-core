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

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contact_BAO_DashboardContact extends CRM_Contact_DAO_DashboardContact {

  /**
   * @param array $record
   *
   * @return CRM_Contact_DAO_DashboardContact
   * @throws \CRM_Core_Exception
   */
  public static function writeRecord(array $record): CRM_Core_DAO {
    self::checkEditPermission($record);
    return parent::writeRecord($record);
  }

  /**
   * @param array $record
   * @return CRM_Contact_DAO_DashboardContact
   * @throws CRM_Core_Exception
   */
  public static function deleteRecord(array $record) {
    self::checkEditPermission($record);
    return parent::deleteRecord($record);
  }

  /**
   * Ensure that the current user has permission to create/edit/delete a DashboardContact record
   *
   * @param array $record
   * @throws CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function checkEditPermission(array $record) {
    if (!empty($record['check_permissions']) && !CRM_Core_Permission::check('administer CiviCRM')) {
      $cid = !empty($record['id']) ? self::getFieldValue(parent::class, $record['id'], 'contact_id') : $record['contact_id'];
      if ($cid != CRM_Core_Session::getLoggedInContactID()) {
        throw new \Civi\API\Exception\UnauthorizedException('You do not have permission to edit the dashboard for this contact.');
      }
    }
  }

}
