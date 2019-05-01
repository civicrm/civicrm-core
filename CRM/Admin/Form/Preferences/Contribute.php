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
class CRM_Admin_Form_Preferences_Contribute extends CRM_Admin_Form_Preferences {
  protected $_settings = [
    'cvv_backoffice_required' => CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME,
    'update_contribution_on_membership_type_change' => CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME,
    'acl_financial_type' => CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME,
    'always_post_to_accounts_receivable' => CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME,
    'deferred_revenue_enabled' => CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME,
    'default_invoice_page' => CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME,
    'invoicing' => CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME,
  ];

  /**
   * Our standards for settings are to have a setting per value with defined metadata.
   *
   * Unfortunately the 'contribution_invoice_settings' has been added in non-compliance.
   * We use this array to hack-handle.
   *
   * I think the best way forwards would be to covert to multiple individual settings.
   *
   * @var array
   */
  protected $invoiceSettings = [];

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $config = CRM_Core_Config::singleton();
    $this->invoiceSettings = [
      'invoice_prefix' => [
        'html_type' => 'text',
        'title' => ts('Invoice Prefix'),
        'weight' => 1,
        'description' => ts('Enter prefix to be display on PDF for invoice'),
      ],
      'credit_notes_prefix' => [
        'html_type' => 'text',
        'title' => ts('Credit Notes Prefix'),
        'weight' => 2,
        'description' => ts('Enter prefix to be display on PDF for credit notes.'),
      ],
      'due_date' => [
        'html_type' => 'text',
        'title' => ts('Due Date'),
        'weight' => 3,
      ],
      'due_date_period' => [
        'html_type' => 'select',
        'title' => ts('For transmission'),
        'weight' => 4,
        'description' => ts('Select the interval for due date.'),
        'option_values' => [
          'select' => ts('- select -'),
          'days' => ts('Days'),
          'months' => ts('Months'),
          'years' => ts('Years'),
        ],
      ],
      'notes' => [
        'html_type' => 'wysiwyg',
        'title' => ts('Notes or Standard Terms'),
        'weight' => 5,
        'description' => ts('Enter note or message to be displayed on PDF invoice or credit notes '),
        'attributes' => ['rows' => 2, 'cols' => 40],
      ],
      'is_email_pdf' => [
        'html_type' => 'checkbox',
        'title' => ts('Automatically email invoice when user purchases online'),
        'weight' => 6,
        'description' => ts('Should a pdf invoice be emailed automatically?'),
      ],
      'tax_term' => [
        'html_type' => 'text',
        'title' => ts('Tax Term'),
        'weight' => 7,
      ],
      'tax_display_settings' => [
        'html_type' => 'select',
        'title' => ts('Tax Display Settings'),
        'weight' => 8,
        'option_values' => [
          'Do_not_show' => ts('Do not show breakdown, only show total -i.e ' .
            $config->defaultCurrencySymbol . '120.00'),
          'Inclusive' => ts('Show [tax term] inclusive price - i.e. ' .
            $config->defaultCurrencySymbol .
            '120.00 (includes [tax term] of ' .
            $config->defaultCurrencySymbol . '20.00)'),
          'Exclusive' => ts('Show [tax term] exclusive price - i.e. ' .
            $config->defaultCurrencySymbol . '100.00 + ' .
            $config->defaultCurrencySymbol . '20.00 [tax term]'),
        ],
      ],
    ];

    // @todo this is a faux metadata approach - we should be honest & add them correctly or find a way to make this
    // compatible with our settings standards.
    foreach ($this->invoiceSettings as $fieldName => $fieldValue) {
      switch ($fieldValue['html_type']) {
        case 'text':
          $this->addElement('text',
            $fieldName,
            $fieldValue['title'],
            [
              'maxlength' => 64,
              'size' => 32,
            ]
          );
          break;

        case 'checkbox':
          $this->add($fieldValue['html_type'],
            $fieldName,
            $fieldValue['title']
          );
          break;

        case 'select':
          $this->addElement('select',
            $fieldName,
            $fieldValue['title'],
            $fieldValue['option_values'],
            CRM_Utils_Array::value('attributes', $fieldValue)
          );
          break;

        case 'wysiwyg':
          $this->add('wysiwyg', $fieldName, $fieldValue['title'], $fieldValue['attributes']);
          break;
      }
    }

    $this->assign('htmlFields', $this->invoiceSettings);
  }

  /**
   * Set default values for the form.
   *
   * default values are retrieved from the database
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    $defaults = array_merge($defaults, Civi::settings()->get('contribution_invoice_settings'));
    return $defaults;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);
    $invoiceParams = array_intersect_key($params, $this->invoiceSettings);
    // This is a hack - invoicing is it's own setting but it is being used from invoice params
    // too. This means that saving from api will not have the desired core effect.
    // but we should fix that elsewhere - ie. stop abusing the settings
    // and fix the code repetition associated with invoicing
    $invoiceParams['invoicing'] = CRM_Utils_Array::value('invoicing', $params, 0);
    Civi::settings()->set('contribution_invoice_settings', $invoiceParams);
    parent::postProcess();
  }

}
