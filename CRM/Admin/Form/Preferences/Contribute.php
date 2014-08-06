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
                                   'is_email_pdf' => array(
                                     'html_type' => 'checkbox',
                                     'title' => ts('Automatically email invoice when user purchases online'),
                                     'weight' => 5,
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
    $this->add('checkbox', 'is_email_pdf', ts('Automatically email invoice when user purchases online'));
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
  }
}



