<?php

namespace Civi\Test\FormWrappers;

use Civi\Test\FormWrapper;

class EventFormParticipant extends FormWrapper {

  /**
   * @var \CRM_Event_Form_Participant
   */
  protected $form;

  /**
   * @return int
   */
  public function getEventID(): int {
    return $this->form->getEventID();
  }

  public function getParticipantID(): int {
    return $this->form->getEventID();
  }

  public function getDiscountID(): int {
    return $this->form->getDiscountID();
  }

  public function getPriceSetID(): int {
    return $this->form->getPriceSetID();
  }

}
