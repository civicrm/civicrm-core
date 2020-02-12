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
class CRM_Admin_Form_Preferences_Contribute extends CRM_Admin_Form_Preferences {

  /**
   * Our standards for settings are to have a setting per value with defined metadata.
   *
   * Unfortunately the 'contribution_invoice_settings' has been added in non-compliance.
   * We use this array to hack-handle.
   *
   * These are now stored as individual settings but this form still does weird & wonderful things.
   *
   * Note the 'real' settings on this form are added via metadata definition - ie
   * 'settings_pages' => ['contribute' => ['weight' => 1]], in their metadata.
   *
   * @var array
   */
  protected $invoiceSettings = [];

  /**
   * Build the form object.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->invoiceSettings = [
      'invoice_prefix' => [
        'html_type' => 'text',
        'title' => ts('Invoice Prefix'),
        'weight' => 1,
        'description' => ts('Enter prefix to be display on PDF for invoice'),
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
          'Do_not_show' => ts('Do not show breakdown, only show total - i.e %1', [
            1 => CRM_Utils_Money::format(120),
          ]),
          'Inclusive' => ts('Show [tax term] inclusive price - i.e. %1', [
            1 => ts('%1 (includes [tax term] of %2)', [1 => CRM_Utils_Money::format(120), 2 => CRM_Utils_Money::format(20)]),
          ]),
          'Exclusive' => ts('Show [tax term] exclusive price - i.e. %1', [
            1 => ts('%1 + %2 [tax term]', [1 => CRM_Utils_Money::format(120), 2 => CRM_Utils_Money::format(20)]),
          ]),
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
