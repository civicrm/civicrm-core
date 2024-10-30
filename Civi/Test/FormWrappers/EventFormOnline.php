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
    $_SESSION['_' . $this->form->controller->_name . '_container']['values'][$form->getName()] = $formValues;
    $this->subsequentForms[$form->getName()] = $form;
    return $this;
  }

}
