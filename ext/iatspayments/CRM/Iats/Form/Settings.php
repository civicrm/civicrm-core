<?php

use CRM_Iats_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Iats_Form_Settings extends CRM_Core_Form {
  public function buildQuickForm() {

    // Add form elements.
    $this->add(
      'text',
      'email_recurring_failure_report',
      ts('Email Recurring Contribution failure reports to this Email address')
    );
    $this->addRule('email_recurring_failure_report', ts('Email address is not a valid format.'), 'email');
    $this->add(
      'text',
      'recurring_failure_threshhold',
      ts('When failure count is equal to or greater than this number, push the next scheduled contribution date forward')
    );
    $this->addRule('recurring_failure_threshhold', ts('Threshhold must be a positive integer.'), 'integer');
    $receipt_recurring_options =  array('0' => 'Never', '1' => 'Always', '2' => 'As set for a specific Contribution Series');
    $this->add(
      'select',
      'receipt_recurring',
      ts('Email receipt for a Contribution in a Recurring Series'),
      $receipt_recurring_options
    );

    $this->add(
      'checkbox',
      'disable_cryptogram',
      ts('Disable use of cryptogram (only applies to FirstAmerican).')
    );
    
    $this->add(
      'text',
      'ach_category_text',
      ts('ACH Category Text (only applies to FirstAmerican).')
    );

    $this->add(
      'checkbox',
      'no_edit_extra',
      ts('Disable extra edit fields for Recurring Contributions')
    );

    $this->add(
      'checkbox',
      'enable_update_subscription_billing_info',
      ts('Enable self-service updates to recurring contribution Contact Billing Info.')
    );

    /* These checkboxes are not yet implemented, ignore for now
    $this->add(
      'checkbox', // field type
      'import_quick', // field name
      ts('Import one-time/new iATS transactions into CiviCRM (e.g. "mobile").')
    );

    $this->add(
      'checkbox', // field type
      'import_recur', // field name
      ts('Import recurring iATS transactions into CiviCRM for known series (e.g. "iATS managed recurring").')
    );

    $this->add(
      'checkbox', // field type
      'import_series', // field name
      ts('Allow creation of new recurring series from iATS imports. (e.g. "mobile recurring")')
    );
    */
    
    $this->add(
      'checkbox',
      'enable_public_future_recurring_start',
      ts('Enable public selection of future recurring start dates.')
    );

    $days = array('-1' => 'disabled');
    for ($i = 1; $i <= 28; $i++) {
      $days["$i"] = "$i";
    }
    $attr = array(
      'size' => 29,
      'style' => 'width:150px',
      'required' => FALSE,
    );
    $day_select = $this->add(
      'select',
      'days',
      ts('Restrict allowable days of the month for Recurring Contributions'),
      $days,
      FALSE,
      $attr
    );

    $day_select->setMultiple(TRUE);
    $day_select->setSize(29);
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    $result = Civi::settings()->get('iats_settings');
    $defaults = (empty($result)) ? array() : $result;
    if (empty($defaults['recurring_failure_threshhold'])) {
      $defaults['recurring_failure_threshhold'] = 3;
    }
    if (empty($defaults['ach_category_text'])) {
      $defaults['ach_category_text'] = FAPS_DEFAULT_ACH_CATEGORY_TEXT;
    }
    $this->setDefaults($defaults);

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    foreach (array('qfKey', '_qf_default', '_qf_Settings_submit', 'entryURL') as $key) {
      if (isset($values[$key])) {
        unset($values[$key]);
      }
    }
    Civi::settings()->set('iats_settings', $values);
    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
