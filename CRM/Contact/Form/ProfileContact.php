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

    foreach (array('soft_credit', 'on_behalf') as $module) {
      $ufJoinParams = array(
        'module' => $module,
        'entity_table' => 'civicrm_contribution_page',
        'entity_id' => $form->_id,
      );

      $ufJoin = new CRM_Core_DAO_UFJoin();
      $ufJoin->copyValues($ufJoinParams);
      $ufJoin->find(TRUE);
      if (!$ufJoin->is_active) {
        continue;
      }

      if ($module == 'soft_credit') {
        $form->_honoreeProfileId = $ufJoin->uf_group_id;

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
      else {
        $form->_onbehalf = FALSE;
        $params = CRM_Contribute_BAO_ContributionPage::formatMultilingualOnBehalfParams($ufJoin->module_data);
        if (CRM_Utils_Array::value('is_for_organization', $params)) {
          if ($params['is_for_organization'] == 2) {
            $form->_onBehalfRequired = TRUE;
          }
          // Add organization profile if 1 of the following are true:
          // If the org profile is required
          if ($form->_onBehalfRequired ||
            // Or we are building the form for the first time
            empty($_POST) ||
            // Or the user has submitted the form and checked the "On Behalf" checkbox
            !empty($_POST['is_for_organization'])
          ) {
            $form->_onbehalf = TRUE;
            $form->_profileId = $ufJoin->uf_group_id;

            if (!$form->_profileId ||
              !CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $form->_profileId, 'is_active')
            ) {
              CRM_Core_Error::fatal(ts('This contribution page has been configured for contribution on behalf of an organization and the selected onbehalf profile is either disabled or not found.'));
            }

            $requiredProfileFields = array('organization_name', 'email');
            $validProfile = CRM_Core_BAO_UFGroup::checkValidProfile($form->_profileId, $requiredProfileFields);
            if (!$validProfile) {
              CRM_Core_Error::fatal(ts('This contribution page has been configured for contribution on behalf of an organization and the required fields of the selected onbehalf profile are disabled.'));
            }

            $form->assign('profileId', $form->_profileId);
            $form->assign('mode', $form->_mode);

            if ($contactID) {
              $form->_employers = CRM_Contact_BAO_Relationship::getPermissionedEmployer($contactID);

              if (!empty($form->_membershipContactID) && $contactID != $form->_membershipContactID) {
                // renewal case - membership being renewed may or may not be for organization
                if (!empty($form->_employers) && array_key_exists($form->_membershipContactID, $form->_employers)) {
                  // if _membershipContactID belongs to employers list, we can say:
                  $form->_relatedOrganizationFound = TRUE;
                }
              }
              elseif (!empty($form->_employers)) {
                // not a renewal case and _employers list is not empty
                $form->_relatedOrganizationFound = TRUE;
              }

              if ($form->_relatedOrganizationFound) {
                // Related org url - pass checksum if needed
                $args = array('cid' => '');
                if (!empty($_GET['cs'])) {
                  $args = array(
                    'uid' => $form->_contactID,
                    'cs' => $_GET['cs'],
                    'cid' => '',
                  );
                }
                $locDataURL = CRM_Utils_System::url('civicrm/ajax/permlocation', $args, FALSE, NULL, FALSE);
                $form->assign('locDataURL', $locDataURL);

                if (!empty($form->_submitValues['onbehalf'])) {
                  if (!empty($form->_submitValues['onbehalfof_id'])) {
                    $form->assign('submittedOnBehalf', $form->_submitValues['onbehalfof_id']);
                  }
                  $form->assign('submittedOnBehalfInfo', json_encode($form->_submitValues['onbehalf']));
                }
              }

              if ($params['is_for_organization'] != 2) {
                $form->assign('relatedOrganizationFound', $form->_relatedOrganizationFound);
              }
              else {
                $form->assign('onBehalfRequired', $form->_onBehalfRequired);
              }

              if (count($form->_employers) == 1) {
                foreach ($form->_employers as $id => $value) {
                  $form->_organizationName = $value['name'];
                  $orgId = $id;
                }
                $form->assign('orgId', $orgId);
                $form->assign('organizationName', $form->_organizationName);
              }
            }
          }
        }
        $this->assign('onBehalfRequired', $form->_onBehalfRequired);
      }
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
