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
class CRM_Contribute_Form_Contribution_OnBehalfOf {

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
    $contactID = $form->_contactID;

    $ufJoinParams = array(
      'module' => 'onBehalf',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $form->_id,
    );
    $profileId = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);
    $form->_profileId = $profileId[0];

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
      } else if (!empty($form->_employers)) {
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

      if ($form->_values['is_for_organization'] != 2) {
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

  /**
   * Function to build form for related contacts / on behalf of organization.
   *
   * @param $form              object  invoking Object
   *
   * @internal param string $contactType contact type
   * @internal param string $title fieldset title
   *
   * @static
   */
  static function buildQuickForm(&$form) {
    $form->assign('fieldSetTitle', ts('Organization Details'));
    $form->assign('buildOnBehalfForm', TRUE);

    $contactID = $form->_contactID;

    if ($contactID && count($form->_employers) >= 1) {
      $form->add('text', 'organization_id', ts('Select an existing related Organization OR enter a new one'));

      $form->add('select', 'onbehalfof_id', '', CRM_Utils_Array::collect('name', $form->_employers));

      $orgOptions = array(
        0 => ts('Select an existing organization'),
        1 => ts('Enter a new organization'),
      );

      $form->addRadio('org_option', ts('options'), $orgOptions);
      $form->setDefaults(array('org_option' => 0));
      $form->add('checkbox', 'mode', '');
    }

    $profileFields = CRM_Core_BAO_UFGroup::getFields($form->_profileId, FALSE, CRM_Core_Action::VIEW, NULL,
      NULL, FALSE, NULL, FALSE, NULL,
      CRM_Core_Permission::CREATE, NULL
    );
    $fieldTypes     = array('Contact', 'Organization');
    $contactSubType = CRM_Contact_BAO_ContactType::subTypes('Organization');
    $fieldTypes     = array_merge($fieldTypes, $contactSubType);

    if (is_array($form->_membershipBlock) && !empty($form->_membershipBlock)) {
      $fieldTypes = array_merge($fieldTypes, array('Membership'));
    }
    else {
      $fieldTypes = array_merge($fieldTypes, array('Contribution'));
    }

    foreach ($profileFields as $name => $field) {
      if (in_array($field['field_type'], $fieldTypes)) {
        list($prefixName, $index) = CRM_Utils_System::explode('-', $name, 2);
        if (in_array($prefixName, array('organization_name', 'email')) && empty($field['is_required'])) {
          $field['is_required'] = 1;
        }

        CRM_Core_BAO_UFGroup::buildProfile($form, $field, NULL, NULL, FALSE, TRUE);
      }
    }

    $form->assign('onBehalfOfFields', $profileFields);
    $form->addElement('hidden', 'hidden_onbehalf_profile', 1);
  }
}
