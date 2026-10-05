<?php

namespace Civi\Test\FormWrappers;

use Civi\Test\FormWrapper;

/**
 *
 */
class EventFormOnline extends FormWrapper {

  /**
   * Add another form to process.
   *
   * @param string $formName
   * @param array $formValues
   *
   * @return $this
   */
  public function addSubsequentForm(string $formName, array $formValues = []): FormWrapper {
    if ($formName !== 'CRM_Event_Form_Registration_AdditionalParticipant') {
      return parent::addSubsequentForm($formName, $formValues);
    }
    $formNumber = 1;
    while (!empty($this->subsequentForms['Participant_' . $formNumber])) {
      $formNumber++;
    }
    /* @var \CRM_Core_Form */
    $form = new $formName(NULL, \CRM_Core_Action::NONE, 'post', 'Participant_' . $formNumber);
    $form->controller = $this->form->controller;
    $form->_submitValues = $formValues;
    $form->controller->addPage($form);
    $_SESSION['_' . $this->form->controller->_name . '_container']['values'][$form->getName()] = $formValues;
    $this->subsequentForms[$form->getName()] = $form;
    return $this;
  }

  public function getLineItems() {
    // The 'final' combined line items need to be read from whichever page
    // ran last (e.g. Confirm), not $this->form, which stays pinned to the
    // originally-constructed Register page throughout - Register's own
    // order is memoized early, before any subsequent participant page or
    // Confirm has been processed.
    $form = end($this->subsequentForms) ?: $this->form;
    return $form->getLineItems();
  }

  public function getTotalAmount() {
    $amount = 0;
    foreach ($this->getLineItems() as $lineItem) {
      $amount += $lineItem['line_total_inclusive'];
    }
    return $amount;
  }

  public function getTotalTaxAmount() {
    $amount = 0;
    foreach ($this->getLineItems() as $lineItem) {
      $amount += $lineItem['tax_amount'];
    }
    return $amount;
  }

}
