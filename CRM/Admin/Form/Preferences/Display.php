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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class generates form components for the display preferences.
 */
class CRM_Admin_Form_Preferences_Display extends CRM_Admin_Form_Preferences {

  protected $_settings = [
    'contact_view_options' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'contact_smart_group_display' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'contact_edit_options' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'advanced_search_options' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'user_dashboard_options' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'contact_ajax_check_similar' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'activity_assignee_notification' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'activity_assignee_notification_ics' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'do_not_notify_assignees_for' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'preserve_activity_tab_filter' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'editor_id' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'ajaxPopupsEnabled' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'display_name_format' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'sort_name_format' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'menubar_position' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
  ];

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    //changes for freezing the invoices/credit notes checkbox if invoicing is uncheck
    $invoiceSettings = Civi::settings()->get('contribution_invoice_settings');
    $this->assign('invoicing', CRM_Invoicing_Utils::isInvoicingEnabled());

    $this->addElement('submit', 'ckeditor_config', ts('Configure CKEditor'));

    $editOptions = CRM_Core_OptionGroup::values('contact_edit_options', FALSE, FALSE, FALSE, 'AND v.filter = 0');
    $this->assign('editOptions', $editOptions);

    $contactBlocks = CRM_Core_OptionGroup::values('contact_edit_options', FALSE, FALSE, FALSE, 'AND v.filter = 1');
    $this->assign('contactBlocks', $contactBlocks);

    $nameFields = CRM_Core_OptionGroup::values('contact_edit_options', FALSE, FALSE, FALSE, 'AND v.filter = 2');
    $this->assign('nameFields', $nameFields);

    $this->addElement('hidden', 'contact_edit_preferences', NULL, ['id' => 'contact_edit_preferences']);

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
