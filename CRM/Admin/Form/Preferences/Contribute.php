<?php 

/**
 * This class generates form components for the display preferences
 *
 */
class CRM_Admin_Form_Preferences_Contribute extends CRM_Admin_Form_Preferences {
  /**
   * Function to process the form
   *
   * @access public
   *
   * @return void
   */
  function preProcess() {
    CRM_Utils_System::setTitle(ts('CiviContribute Component Settings'));
    $this->_varNames = array(
                             CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME  =>
                             array(
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
                                                              'weight' => 4,
                                                              'description' => ts('Select the interval for due date.'),
                                                              ),
                                   'notes' => array(
                                                    'html_type' => 'textarea',
                                                    'title' => ts('Notes or Standard Terms'),
                                                    'weight' => 5,
                                                    'description' => ts('Enter note or message to be display on PDF invoice or credit notes '),
                                                    ),
                                   'tax_term' => array(
                                                       'html_type' => 'text',
                                                       'title' => ts('Tax Term'),
                                                       'weight' => 6,
                                                       ),
                                   'tax_display_settings'=> array(
                                                                  'html_type' => 'select',
                                                                  'weight' => 7,
                                                                  ),
                                   ),
                             );
    parent::preProcess();
  }

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  function buildQuickForm() {
    $config = CRM_Core_Config::singleton();
    $this->add('select', 'due_date_period', ts('For transmission'),
               array(
                     'select' => ts('- select -'),
                     'days' => ts('Days'),
                     'months' => ts('Months'),
                     'years' => ts('Years')
                     )    
               );
    $this->add('select','tax_display_settings', ts('Tax Display Settings'),
               array(
                     'Do_not_show' => ts('Do not show brakedown, only show total -i.e '.$config->defaultCurrencySymbol.'120.00'),
                     'Inclusive' => ts('Show [tax term] inclusive price - i.e. '.$config->defaultCurrencySymbol.'120.00 (includes [tax term] of '.$config->defaultCurrencySymbol.'20.00)'),
                     'Exclusive' => ts('Show [tax term] exclusive price - i.e. '.$config->defaultCurrencySymbol.'100.00 + '.$config->defaultCurrencySymbol.'20.00 [tax term]')
                     )    
               );
    $this->add('checkbox', 'invoicing', ts('Enable Tax and Invoicing'));
    parent::buildQuickForm();
  }

  /**
   * This function sets the default values for the form.
   * default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    $defaults = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME,'contribution_invoice_settings');
    return $defaults;
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);
    $setInvoiceSettings = CRM_Core_BAO_Setting::setItem($params, CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME, 'contribution_invoice_settings');

    // to set default value for 'Invoices / Credit Notes' checkbox on display preferences
    $values = CRM_Core_BAO_Setting::getItem("CiviCRM Preferences");
    $optionValues = CRM_Core_BAO_OptionValue::getOptionValuesAssocArrayFromName("user_dashboard_options");
    $setKey = array_search('Invoices / Credit Notes', $optionValues);

    if (isset($params['invoicing'])) {
      $value = array($setKey => $optionValues[$setKey]);
      $setInvoice = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR,
                    array_keys($value)) . CRM_Core_DAO::VALUE_SEPARATOR;
      CRM_Core_BAO_Setting::setItem($values['user_dashboard_options'].$setInvoice, 'CiviCRM Preferences', 'user_dashboard_options');
    }
    else {
      $setting = explode(CRM_Core_DAO::VALUE_SEPARATOR, substr($values['user_dashboard_options'], 1, -1));
      $invoiceKey = array_search ($setKey, $setting);
      unset($setting[$invoiceKey]);
      $settingName = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR,
                     array_values($setting)) . CRM_Core_DAO::VALUE_SEPARATOR;
      CRM_Core_BAO_Setting::setItem($settingName, 'CiviCRM Preferences', 'user_dashboard_options');
    }
  }
}



