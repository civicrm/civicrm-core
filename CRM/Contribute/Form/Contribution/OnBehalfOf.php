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
class CRM_Contribute_Form_Contribution_OnBehalfOf extends CRM_Core_Form {

  protected $_profileId;

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    parent::preProcess();

    $this->_profileId = CRM_Utils_Request::retrieve('id', 'Positive', $this, NULL, FALSE, NULL, 'GET');

    $this->assign('suppressForm', TRUE);
    $this->assign('snippet', TRUE);
    $this->controller->_generateQFKey = FALSE;
  }

  /**
   * Build form for honoree contact / on behalf of organization.
   */
  public function buildQuickForm() {
    $contactID = $this->getContactID();

    $this->assign('fieldSetTitle', ts('Organization Details'));
    $this->assign('profileId', $this->_profileId);

    if ($contactID) {
      $employer = CRM_Contact_BAO_Relationship::getPermissionedEmployer($contactID);

      if (count($employer)) {
        // Related org url - pass checksum if needed
        $args = array('cid' => '');
        if (!empty($_GET['cs'])) {
          $args = array(
            'uid' => $this->_contactID,
            'cs' => $_GET['cs'],
            'cid' => '',
          );
        }
        $locDataURL = CRM_Utils_System::url('civicrm/ajax/permlocation', $args, FALSE, NULL, FALSE);
        $this->assign('locDataURL', $locDataURL);
      }
      if (count($employer) > 0) {
        $this->add('select', 'onbehalfof_id', '', CRM_Utils_Array::collect('name', $employer));

        $orgOptions = array(
          0 => ts('Select an existing organization'),
          1 => ts('Enter a new organization'),
        );
        $this->addRadio('org_option', ts('options'), $orgOptions);
        $this->setDefaults(array('org_option' => 0));
      }
    }

    $profileFields = CRM_Core_BAO_UFGroup::getFields($this->_profileId, FALSE, CRM_Core_Action::VIEW, NULL,
                     NULL, FALSE, NULL, FALSE, NULL,
                     CRM_Core_Permission::CREATE, NULL
    );
    $this->assign('onBehalfOfFields', $profileFields);
    $this->addElement('hidden', 'onbehalf_profile_id', $this->_profileId);

    $fieldTypes = array('Contact', 'Organization');
    $contactSubType = CRM_Contact_BAO_ContactType::subTypes('Organization');
    $fieldTypes = array_merge($fieldTypes, $contactSubType);

    foreach ($profileFields as $name => $field) {
      if (in_array($field['field_type'], $fieldTypes)) {
        list($prefixName, $index) = CRM_Utils_System::explode('-', $name, 2);
        if (in_array($prefixName, array('organization_name', 'email')) && empty($field['is_required'])) {
          $field['is_required'] = 1;
        }

        CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, NULL, FALSE, 'onbehalf', NULL, 'onbehalf');
      }
    }
  }

}
