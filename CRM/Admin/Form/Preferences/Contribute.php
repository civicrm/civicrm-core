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
 * This class generates form components for the display preferences.
 */
class CRM_Admin_Form_Preferences_Contribute extends CRM_Admin_Form_Preferences {
  protected $_settings = array(
    'cvv_backoffice_required' => CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME,
  );

  /**
   * Process the form submission.
   */
  public function preProcess() {
    $config = CRM_Core_Config::singleton();
    CRM_Utils_System::setTitle(ts('CiviContribute Component Settings'));
    $this->_varNames = array(
      CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME => array(
        'invoice_prefix' => array(
          'html_type' => 'text',
          'title' => ts('Invoice Prefix'),
          'weight' => 1,
          'description' => ts('Enter prefix to be display on PDF for invoice'),
        ),
        'credit_notes_prefix' => array(
          'html_type' => 'text',
          'title' => ts('Credit Notes Prefix'),
          'weight' => 2,
          'description' => ts('Enter prefix to be display on PDF for credit notes.'),
        ),
        'due_date' => array(
          'html_type' => 'text',
          'title' => ts('Due Date'),
          'weight' => 3,
        ),
        'due_date_period' => array(
          'html_type' => 'select',
          'title' => ts('For transmission'),
          'weight' => 4,
          'description' => ts('Select the interval for due date.'),
          'option_values' => array(
            'select' => ts('- select -'),
            'days' => ts('Days'),
            'months' => ts('Months'),
            'years' => ts('Years'),
          ),
        ),
        'notes' => array(
          'html_type' => 'wysiwyg',
          'title' => ts('Notes or Standard Terms'),
          'weight' => 5,
          'description' => ts('Enter note or message to be displayed on PDF invoice or credit notes '),
          'attributes' => array('rows' => 2, 'cols' => 40),
        ),
        'is_email_pdf' => array(
          'html_type' => 'checkbox',
          'title' => ts('Automatically email invoice when user purchases online'),
          'weight' => 6,
        ),
        'tax_term' => array(
          'html_type' => 'text',
          'title' => ts('Tax Term'),
          'weight' => 7,
        ),
        'tax_display_settings' => array(
          'html_type' => 'select',
          'title' => ts('Tax Display Settings'),
          'weight' => 8,
          'option_values' => array(
            'Do_not_show' => ts('Do not show breakdown, only show total -i.e ' .
              $config->defaultCurrencySymbol . '120.00'),
            'Inclusive' => ts('Show [tax term] inclusive price - i.e. ' .
              $config->defaultCurrencySymbol .
              '120.00 (includes [tax term] of ' .
              $config->defaultCurrencySymbol . '20.00)'),
            'Exclusive' => ts('Show [tax term] exclusive price - i.e. ' .
              $config->defaultCurrencySymbol . '100.00 + ' .
              $config->defaultCurrencySymbol . '20.00 [tax term]'),
          ),
        ),
      ),
    );
    parent::preProcess();
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    //CRM-16691: Changes made related to settings of 'CVV'.
    foreach ($this->_settings as $setting => $group) {
      $settingMetaData = civicrm_api3('setting', 'getfields', array('name' => $setting));
      $props = $settingMetaData['values'][$setting];
      if (isset($props['quick_form_type'])) {
        $add = 'add' . $props['quick_form_type'];
        if ($add == 'addElement') {
          $this->$add(
            $props['html_type'],
            $setting,
            ts($props['title']),
            CRM_Utils_Array::value($props['html_type'] == 'select' ? 'option_values' : 'html_attributes', $props, array()),
            $props['html_type'] == 'select' ? CRM_Utils_Array::value('html_attributes', $props) : NULL
          );
        }
        else {
          $this->$add($setting, ts($props['title']));
        }
      }
      $this->assign("{$setting}_description", ts($props['description']));
    }
    $this->add('checkbox', 'invoicing', ts('Enable Tax and Invoicing'));
    $this->add('checkbox', 'acl_financial_type', ts('Enable Access Control by Financial Type'));
    parent::buildQuickForm();
  }

  /**
   * Set default values for the form.
   *
   * default values are retrieved from the database
   */
  public function setDefaultValues() {
    $defaults = Civi::settings()->get('contribution_invoice_settings');
    //CRM-16691: Changes made related to settings of 'CVV'.
    foreach ($this->_settings as $setting => $group) {
      $settingMetaData = civicrm_api3('setting', 'getfields', array('name' => $setting));
      $defaults[$setting] = civicrm_api3('setting', 'getvalue',
        array(
          'name' => $setting,
          'group' => $group,
          'default_value' => CRM_Utils_Array::value('default', $settingMetaData['values'][$setting]),
        )
      );
    }
    return $defaults;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);
    unset($params['qfKey']);
    unset($params['entryURL']);
    Civi::settings()->set('contribution_invoice_settings', $params);

    // to set default value for 'Invoices / Credit Notes' checkbox on display preferences
    $values = CRM_Core_BAO_Setting::getItem("CiviCRM Preferences");
    $optionValues = CRM_Core_OptionGroup::values('user_dashboard_options', FALSE, FALSE, FALSE, NULL, 'name');
    $setKey = array_search('Invoices / Credit Notes', $optionValues);

    if (isset($params['invoicing'])) {
      $value = array($setKey => $optionValues[$setKey]);
      $setInvoice = CRM_Core_DAO::VALUE_SEPARATOR .
        implode(CRM_Core_DAO::VALUE_SEPARATOR, array_keys($value)) .
        CRM_Core_DAO::VALUE_SEPARATOR;
      Civi::settings()->set('user_dashboard_options', $values['user_dashboard_options'] . $setInvoice);
    }
    else {
      $setting = explode(CRM_Core_DAO::VALUE_SEPARATOR, substr($values['user_dashboard_options'], 1, -1));
      $invoiceKey = array_search($setKey, $setting);
      unset($setting[$invoiceKey]);
      $settingName = CRM_Core_DAO::VALUE_SEPARATOR .
        implode(CRM_Core_DAO::VALUE_SEPARATOR, array_values($setting)) .
        CRM_Core_DAO::VALUE_SEPARATOR;
      Civi::settings()->set('user_dashboard_options', $settingName);
    }
    //CRM-16691: Changes made related to settings of 'CVV'.
    $settings = array_intersect_key($params, $this->_settings);
    $result = civicrm_api3('setting', 'create', $settings);
    CRM_Core_Session::setStatus(ts('Your changes have been saved.'), ts('Changes Saved'), "success");
  }

}
