<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class generates form components for the display preferences.
 */
class CRM_Admin_Form_Preferences_Display extends CRM_Admin_Form_Preferences {
  public function preProcess() {
    CRM_Utils_System::setTitle(ts('Settings - Display Preferences'));

    $this->_varNames = array(
      CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME => array(
        'contact_view_options' => array(
          'html_type' => 'checkboxes',
          'title' => ts('Viewing Contacts'),
          'weight' => 1,
        ),
        'contact_smart_group_display' => array(
          'html_type' => 'radio',
          'title' => ts('Viewing Smart Groups'),
          'weight' => 2,
        ),
        'contact_edit_options' => array(
          'html_type' => 'checkboxes',
          'title' => ts('Editing Contacts'),
          'weight' => 3,
        ),
        'advanced_search_options' => array(
          'html_type' => 'checkboxes',
          'title' => ts('Contact Search'),
          'weight' => 4,
        ),
        'activity_assignee_notification' => array(
          'html_type' => 'checkbox',
          'title' => ts('Notify Activity Assignees'),
          'weight' => 5,
        ),
        'activity_assignee_notification_ics' => array(
          'html_type' => 'checkbox',
          'title' => ts('Include ICal Invite to Activity Assignees'),
          'weight' => 6,
        ),
        'contact_ajax_check_similar' => array(
          'html_type' => 'checkbox',
          'title' => ts('Check for Similar Contacts'),
          'weight' => 7,
        ),
        'user_dashboard_options' => array(
          'html_type' => 'checkboxes',
          'title' => ts('Contact Dashboard'),
          'weight' => 8,
        ),
        'display_name_format' => array(
          'html_type' => 'textarea',
          'title' => ts('Individual Display Name Format'),
          'weight' => 9,
        ),
        'sort_name_format' => array(
          'html_type' => 'textarea',
          'title' => ts('Individual Sort Name Format'),
          'weight' => 10,
        ),
        'editor_id' => array(
          'html_type' => NULL,
          'weight' => 11,
        ),
        'ajaxPopupsEnabled' => array(
          'html_type' => 'checkbox',
          'title' => ts('Enable Popup Forms'),
          'weight' => 12,
        ),
      ),
    );

    parent::preProcess();
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    parent::cbsDefaultValues($defaults);

    if ($this->_config->display_name_format) {
      $defaults['display_name_format'] = $this->_config->display_name_format;
    }
    if ($this->_config->sort_name_format) {
      $defaults['sort_name_format'] = $this->_config->sort_name_format;
    }

    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $wysiwyg_options = CRM_Core_OptionGroup::values('wysiwyg_editor', FALSE, FALSE, FALSE, NULL, 'label', TRUE, FALSE, 'name');

    //changes for freezing the invoices/credit notes checkbox if invoicing is uncheck
    $invoiceSettings = Civi::settings()->get('contribution_invoice_settings');
    $invoicing = CRM_Utils_Array::value('invoicing', $invoiceSettings);
    $this->assign('invoicing', $invoicing);
    $extra = array();

    $this->addElement('select', 'editor_id', ts('WYSIWYG Editor'), $wysiwyg_options, $extra);
    $this->addElement('submit', 'ckeditor_config', ts('Configure CKEditor'));

    $editOptions = CRM_Core_OptionGroup::values('contact_edit_options', FALSE, FALSE, FALSE, 'AND v.filter = 0');
    $this->assign('editOptions', $editOptions);

    $contactBlocks = CRM_Core_OptionGroup::values('contact_edit_options', FALSE, FALSE, FALSE, 'AND v.filter = 1');
    $this->assign('contactBlocks', $contactBlocks);

    $nameFields = CRM_Core_OptionGroup::values('contact_edit_options', FALSE, FALSE, FALSE, 'AND v.filter = 2');
    $this->assign('nameFields', $nameFields);

    $this->addElement('hidden', 'contact_edit_preferences', NULL, array('id' => 'contact_edit_preferences'));

    $optionValues = CRM_Core_OptionGroup::values('user_dashboard_options', FALSE, FALSE, FALSE, NULL, 'name');
    $invoicesKey = array_search('Invoices / Credit Notes', $optionValues);
    $this->assign('invoicesKey', $invoicesKey);
    parent::buildQuickForm();
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action == CRM_Core_Action::VIEW) {
      return;
    }

    $this->_params = $this->controller->exportValues($this->_name);

    if (!empty($this->_params['contact_edit_preferences'])) {
      $preferenceWeights = explode(',', $this->_params['contact_edit_preferences']);
      foreach ($preferenceWeights as $key => $val) {
        if (!$val) {
          unset($preferenceWeights[$key]);
        }
      }
      $opGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'contact_edit_options', 'id', 'name');
      CRM_Core_BAO_OptionValue::updateOptionWeights($opGroupId, array_flip($preferenceWeights));
    }

    $this->_config->editor_id = $this->_params['editor_id'];

    $this->postProcessCommon();

    // If "Configure CKEditor" button was clicked
    if (!empty($this->_params['ckeditor_config'])) {
      // Suppress the "Saved" status message and redirect to the CKEditor Config page
      $session = CRM_Core_Session::singleton();
      $session->getStatus(TRUE);
      $url = CRM_Utils_System::url('civicrm/admin/ckeditor', 'reset=1');
      $session->pushUserContext($url);
    }
  }

}
