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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class generates form components for the display preferences.
 */
class CRM_Admin_Form_Preferences_Display extends CRM_Admin_Form_Generic {

  public function preProcess() {
    parent::preProcess();
    $this->sections = [
      'default' => [
        'title' => ts('General'),
      ],
      'contact' => [
        'title' => ts('Contacts'),
        'icon' => 'fa-contacts',
        'weight' => 10,
      ],
      'activity' => [
        'title' => ts('Activities'),
        'icon' => 'fa-tasks',
        'weight' => 20,
      ],
      'theme' => [
        'title' => ts('Theme'),
        'icon' => 'fa-palette',
        'weight' => 30,
      ],
    ];
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    //changes for freezing the invoices/credit notes checkbox if invoicing is uncheck
    $this->assign('invoicing', Civi::settings()->get('invoicing'));

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
    parent::postProcess();
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
  }

}
