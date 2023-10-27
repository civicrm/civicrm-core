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
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $metadata = \Civi\Core\SettingsMetadata::getMetadata(['name' => ['invoice_prefix', 'tax_term', 'invoice_notes', 'invoice_due_date', 'invoice_is_email_pdf', 'invoice_due_date_period', 'tax_display_settings']], NULL, TRUE);
    $this->invoiceSettings = [
      'invoice_prefix' => $metadata['invoice_prefix'],
      'due_date' => $metadata['invoice_due_date'],
      'due_date_period' => $metadata['invoice_due_date_period'],
      'notes' => $metadata['invoice_notes'],
      'is_email_pdf' => $metadata['invoice_is_email_pdf'],
      'tax_term' => $metadata['tax_term'],
      'tax_display_settings' => $metadata['tax_display_settings'],
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
            $fieldValue['options'],
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
    $invoiceParams['invoicing'] = $params['invoicing'] ?? 0;
    Civi::settings()->set('contribution_invoice_settings', $invoiceParams);
    parent::postProcess();
  }

}
