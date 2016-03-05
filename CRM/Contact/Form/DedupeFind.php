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

/**
 * This class generates form components for DedupeRules.
 */
class CRM_Contact_Form_DedupeFind extends CRM_Admin_Form {

  /**
   * Pre processing.
   */
  public function preProcess() {
    $this->rgid = CRM_Utils_Request::retrieve('rgid', 'Positive', $this, FALSE, 0);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    $groupList = array('' => ts('- All Contacts -')) + CRM_Core_PseudoConstant::nestedGroup();

    $this->add('select', 'group_id', ts('Select Group'), $groupList, FALSE, array('class' => 'crm-select2 huge'));
    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Continue'),
          'isDefault' => TRUE,
        ),
        //hack to support cancel button functionality
        array(
          'type' => 'submit',
          'class' => 'cancel',
          'icon' => 'fa-times',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  public function setDefaultValues() {
    return $this->_defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $values = $this->exportValues();
    if (!empty($_POST['_qf_DedupeFind_submit'])) {
      //used for cancel button
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/deduperules', 'reset=1'));
      return;
    }
    if ($values['group_id']) {
      $url = CRM_Utils_System::url('civicrm/contact/dedupefind', "reset=1&action=update&rgid={$this->rgid}&gid={$values['group_id']}");
    }
    else {
      $url = CRM_Utils_System::url('civicrm/contact/dedupefind', "reset=1&action=update&rgid={$this->rgid}");
    }

    CRM_Utils_System::redirect($url);
  }

}
