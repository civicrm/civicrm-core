<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */
class CRM_Contact_Form_ProfileContact {

  protected $_mode;

  /**
   * Set variables up before form is built.
   *
   * @param CRM_Core_Form $form
   */
  public static function preProcess(&$form) {
    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');

    $ufJoinParams = array(
      'module' => 'soft_credit',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $form->_id,
    );
    $profileId = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);
    $form->_honoreeProfileId = $profileId[0];

    if (!$form->_honoreeProfileId ||
      !CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $form->_honoreeProfileId, 'is_active')
    ) {
      CRM_Core_Error::fatal(ts('This contribution page has been configured for contribution on behalf of honoree and the selected honoree profile is either disabled or not found.'));
    }

    $profileContactType = CRM_Core_BAO_UFGroup::getContactType($form->_honoreeProfileId);
    $requiredProfileFields = array(
      'Individual' => array('first_name', 'last_name'),
      'Organization' => array('organization_name', 'email'),
      'Household' => array('household_name', 'email'),
    );
    $validProfile = CRM_Core_BAO_UFGroup::checkValidProfile($form->_honoreeProfileId, $requiredProfileFields[$profileContactType]);
    if (!$validProfile) {
      CRM_Core_Error::fatal(ts('This contribution page has been configured for contribution on behalf of honoree and the required fields of the selected honoree profile are disabled or doesn\'t exist.'));
    }
  }

  /**
   * Build form for honoree contact / on behalf of organization.
   *
   * @param CRM_Core_Form $form
   *
   */
  public static function buildQuickForm(&$form) {
    $ufGroup = new CRM_Core_DAO_UFGroup();
    $ufGroup->id = $form->_honoreeProfileId;
    if (!$ufGroup->find(TRUE)) {
      CRM_Core_Error::fatal(ts('Chosen honoree profile for this contribution is disabled'));
    }

    $prefix = 'honor';
    $honoreeProfileFields = CRM_Core_BAO_UFGroup::getFields($form->_honoreeProfileId, FALSE, NULL,
      NULL, NULL,
      FALSE, NULL,
      TRUE, NULL,
      CRM_Core_Permission::CREATE
    );
    $form->addElement('hidden', 'honoree_profile_id', $form->_honoreeProfileId);
    $form->assign('honoreeProfileFields', $honoreeProfileFields);

    // add the form elements
    foreach ($honoreeProfileFields as $name => $field) {
      // If soft credit type is not chosen then make omit requiredness from honoree profile fields
      if (count($form->_submitValues) && empty($form->_submitValues['soft_credit_type_id']) && !empty($field['is_required'])) {
        $field['is_required'] = FALSE;
      }
      CRM_Core_BAO_UFGroup::buildProfile($form, $field, CRM_Profile_Form::MODE_CREATE, NULL, FALSE, FALSE, NULL, $prefix);
    }
  }

  /**
   * @param $form
   */
  public static function postProcess($form) {
  }

}
