<?php 

/**
 * This class generates form components for the display preferences
 *
 */
class CRM_Admin_Form_Preferences_Contribute extends CRM_Admin_Form_Preferences {
  /**
   * gives array of contribution settings of administer form for Invoice
   *
   * @access public
   **/ 
  public $_defaults;

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
                                   'tax_display_settings'=> array(
                                                                  'html_type' => 'select', 
                                                                  'weight' => 6,
                                                                  ),
                                   'notes' => array(
                                                    'html_type' => 'textarea',
                                                    'title' => ts('Notes or Standard Terms'),
                                                    'weight' => 5,
                                                    'description' => ts('Enter note or message to be display on PDF invoice or credit notes '),
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
    $this->add('select', 'due_date_period', ts('For transmission'),
               array(
                     'select' => ts('- select -'),
                     'days' => ts('Days'),
                     'months' => ts('months'),
                     'years' => ts('years')
                     )    
               );
    $this->add('select','tax_display_settings', ts('Tax Display Settings'),
               array(
                     'Do_not_show' => ts('Do not show brakedown, only show total -i.e $120.00'),
                     'Inclusive' => ts('Show VAT inclusive price - i.e. $120.00(include TAX LABLE -$20)'),
                     'Exclusive' => ts('Show VAT exclusive price - i.e. $100 + TAX LABLE -$20)')
                     )    
               );
    parent::buildQuickForm();
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

  /**
   * This function sets the default values for the form.
   * default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    if (!$this->_defaults) {
      $this->_defaults = array();
      $contributionSettings = $this->_config->contribution_invoice_settings;
      $this->_defaults = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME,'contribution_invoice_settings');
    }
    return $this->_defaults;
  }
}



