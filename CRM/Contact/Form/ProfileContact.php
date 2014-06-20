<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Contact_Form_ProfileContact {

  protected $_mode;

  /**
   * Function to set variables up before form is built
   *
   * @param $form
   *
   * @return void
   * @access public
   */
  static function preProcess(&$form) {
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
      'Household' => array('household_name', 'email')
    );
    $validProfile = CRM_Core_BAO_UFGroup::checkValidProfile($form->_honoreeProfileId, $requiredProfileFields[$profileContactType]);
    if (!$validProfile) {
      CRM_Core_Error::fatal(ts('This contribution page has been configured for contribution on behalf of honoree and the required fields of the selected honoree profile are disabled or doesn\'t exist.'));
    }
  }

  /**
   * Function to build form for honoree contact / on behalf of organization.
   *
   * @param $form              object  invoking Object
   *
   * @internal param string $contactType contact type
   * @internal param string $title fieldset title
   *
   * @static
   */
  static function buildQuickForm(&$form) {
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
  static function postProcess($form) {
    $params = $form->_params;
    if (!empty($form->_honor_block_is_active) && !empty($params['soft_credit_type_id'])) {
      $honorId = null;

      //check if there is any duplicate contact
      $profileContactType = CRM_Core_BAO_UFGroup::getContactType($params['honoree_profile_id']);
      $dedupeParams = CRM_Dedupe_Finder::formatParams($params['honor'], $profileContactType);
      $dedupeParams['check_permission'] = FALSE;
      $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, $profileContactType);
      if(count($ids)) {
        $honorId = CRM_Utils_Array::value(0, $ids);
      }

      $honorId = CRM_Contact_BAO_Contact::createProfileContact(
        $params['honor'], CRM_Core_DAO::$_nullArray,
        $honorId, NULL,
        $params['honoree_profile_id']
      );
      $softParams = array();
      $softParams['contribution_id'] = $form->_contributionID;
      $softParams['contact_id'] = $honorId;
      $softParams['soft_credit_type_id'] = $params['soft_credit_type_id'];
      $contribution = new CRM_Contribute_DAO_Contribution();
      $contribution->id = $form->_contributionID;
      $contribution->find();
      while ($contribution->fetch()) {
        $softParams['currency'] = $contribution->currency;
        $softParams['amount'] = $contribution->total_amount;
      }
      CRM_Contribute_BAO_ContributionSoft::add($softParams);

      if (CRM_Utils_Array::value('is_email_receipt', $form->_values)) {
        $form->_values['honor'] = array(
          'soft_credit_type' => CRM_Utils_Array::value(
            $params['soft_credit_type_id'],
            CRM_Core_OptionGroup::values("soft_credit_type")
          ),
          'honor_id' => $honorId,
          'honor_profile_id' => $params['honoree_profile_id'],
          'honor_profile_values' => $params['honor']
        );
      }
    }
  }
}

