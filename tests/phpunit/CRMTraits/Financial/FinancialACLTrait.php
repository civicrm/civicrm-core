<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Trait FinancialACLTrait
 *
 * Trait for working with Financial ACLs in tests
 */
trait CRMTraits_Financial_FinancialACLTrait {

  /**
   * Enable financial ACLs.
   */
  protected function enableFinancialACLs() {
    $contributeSettings = Civi::settings()->get('contribution_invoice_settings');
    $this->callAPISuccess('Setting', 'create', [
      'contribution_invoice_settings' => array_merge($contributeSettings, ['acl_financial_type' => TRUE]),
      'acl_financial_type' => TRUE,
    ]);
    unset(\Civi::$statics['CRM_Financial_BAO_FinancialType']);
  }

  /**
   * Disable financial ACLs.
   */
  protected function disableFinancialACLs() {
    $contributeSettings = Civi::settings()->get('contribution_invoice_settings');
    $this->callAPISuccess('Setting', 'create', [
      'contribution_invoice_settings' => array_merge($contributeSettings, ['acl_financial_type' => FALSE]),
      'acl_financial_type' => FALSE,
    ]);
    unset(\Civi::$statics['CRM_Financial_BAO_FinancialType']);
  }

  /**
   * Create a logged in user limited by ACL permissions.
   *
   * @param array $aclPermissions
   *   Array of ACL permissions in the format
   *   [[$action, $financialType], [$action, $financialType])
   *
   * @return int Contact ID
   */
  protected function createLoggedInUserWithFinancialACL($aclPermissions = [['view', 'Donation']]) {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view all contacts'];
    $contactID = $this->createLoggedInUser();
    $this->addFinancialAclPermissions($aclPermissions);
    return $contactID;
  }

  /**
   * Add a permission to the financial ACLs.
   *
   * @param array $aclPermissions
   *   Array of ACL permissions in the format
   *   [[$action, $financialType], [$action, $financialType])
   */
  protected function addFinancialAclPermissions($aclPermissions) {
    $permissions = CRM_Core_Config::singleton()->userPermissionClass->permissions;
    foreach ($aclPermissions as $aclPermission) {
      $permissions[] = $aclPermission[0] . ' contributions of type ' . $aclPermission[1];
    }
    $this->setPermissions($permissions);
  }

  /**
   * Add a permission to the permissions array.
   *
   * @param array $permissions
   *   Array of permissions to add - e.g. ['access CiviCRM','access CiviContribute'],
   */
  protected function addPermissions($permissions) {
    $permissions = array_merge(CRM_Core_Config::singleton()->userPermissionClass->permissions, $permissions);
    $this->setPermissions($permissions);
  }

  /**
   * Set ACL permissions, overwriting any existing ones.
   *
   * @param array $permissions
   *   Array of permissions e.g ['access CiviCRM','access CiviContribute'],
   */
  protected function setPermissions($permissions) {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = $permissions;
    if (isset(\Civi::$statics['CRM_Financial_BAO_FinancialType'])) {
      unset(\Civi::$statics['CRM_Financial_BAO_FinancialType']);
    }
  }

}
