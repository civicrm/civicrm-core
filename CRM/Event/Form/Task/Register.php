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
use Civi\Api4\Participant;

/**
 * This class provides the register functionality from a search context.
 *
 * Originally the functionality was all munged into the main Participant form.
 *
 * Ideally it would be entirely separated but for now this overrides the main form,
 * just providing a better separation of the functionality for the search vs main form.
 */
class CRM_Event_Form_Task_Register extends CRM_Event_Form_Participant {

  /**
   * Are we operating in "single mode", i.e. adding / editing only
   * one participant record, or is this a batch add operation
   *
   * ote the goal is to disentangle all the non-single stuff
   * into this form and discontinue this param.
   *
   * @var bool
   */
  public $_single = FALSE;

  /**
   * Assign the url path to the template.
   */
  protected function assignUrlPath() {
    //set the appropriate action
    $context = $this->get('context');
    $urlString = 'civicrm/contact/search';
    $this->_action = CRM_Core_Action::BASIC;
    switch ($context) {
      case 'advanced':
        $urlString = 'civicrm/contact/search/advanced';
        $this->_action = CRM_Core_Action::ADVANCED;
        break;

      case 'builder':
        $urlString = 'civicrm/contact/search/builder';
        $this->_action = CRM_Core_Action::PROFILE;
        break;

      case 'basic':
        $urlString = 'civicrm/contact/search/basic';
        $this->_action = CRM_Core_Action::BASIC;
        break;

      case 'custom':
        $urlString = 'civicrm/contact/search/custom';
        $this->_action = CRM_Core_Action::COPY;
        break;
    }
    CRM_Contact_Form_Task::preProcessCommon($this);

    $this->_contactId = NULL;

    //set ajax path, this used for custom data building
    $this->assign('urlPath', 'civicrm/contact/view/participant');

    $key = CRM_Core_Key::get('CRM_Event_Form_Participant', TRUE);
    $this->assign('participantQfKey', $key);
    $this->assign('participantAction', CRM_Core_Action::ADD);
    $this->assign('urlPathVar', "_qf_Participant_display=true&context=search");
  }

  /**
   * Get id of participant being edited.
   *
   * This always returns null as it is the form to take action on search results.
   *
   * The parent class works on a single record & hence lik
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @return null
   */
  public function getParticipantID(): ?int {
    return NULL;
  }

  /**
   * Process the form submission.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess(): void {
    $params = $this->controller->exportValues($this->_name);
    // When adding more than one contact, the duplicates are
    // removed automatically and the user receives one notification.
    $event_id = $this->_eventId;
    if (!$event_id && !empty($params['event_id'])) {
      $event_id = $params['event_id'];
    }
    if (!empty($event_id)) {
      $allowSameParticipantEmails = \Civi\Api4\Event::get()
        ->addSelect('allow_same_participant_emails')->addWhere('id', '=', $event_id)->execute()
        ->first()['allow_same_participant_emails'];
      $duplicateContacts = 0;
      foreach ($this->_contactIds as $k => $dupeCheckContactId) {
        // Eliminate contacts that have already been assigned to this event.
        if (!empty($this->getExistingParticipantRecords($dupeCheckContactId))) {
          $duplicateContacts++;
          if (!$allowSameParticipantEmails) {
            unset($this->_contactIds[$k]);
          }
        }
      }
      if ($duplicateContacts > 0) {
        if ($allowSameParticipantEmails) {
          $msg = ts(
            '%1 contacts were already registered for this event, but have been added a second time.',
            [1 => $duplicateContacts]
          );
        }
        else {
          $msg = ts(
            '%1 contacts have already been assigned to this event. They were not added a second time.',
            [1 => $duplicateContacts]
          );
        }
        CRM_Core_Session::setStatus($msg);
      }
      if (count($this->_contactIds) === 0) {
        CRM_Core_Session::setStatus(ts('No participants were added.'));
        return;
      }
      // We have to re-key $this->_contactIds so each contact has the same
      // key as their corresponding record in the $participants array that
      // will be created below.
      $this->_contactIds = array_values($this->_contactIds);
    }
    if ($this->getPriceSetID()) {
      $this->getOrder()->setPriceSelectionFromUnfilteredInput($this->getSubmittedValues());
    }
    $statusMsg = $this->submit($params);
    CRM_Core_Session::setStatus($statusMsg, ts('Saved'), 'success');
  }

  /**
   * Get status message
   *
   * @param array $params
   * @param int $numberSent
   * @param int $numberNotSent
   * @param string $updateStatusMsg
   *
   * @return string
   */
  protected function getStatusMsg(array $params, int $numberSent, int $numberNotSent, string $updateStatusMsg): string {
    $statusMsg = '';
    if ($this->_action & CRM_Core_Action::ADD) {
      $statusMsg = ts('Total Participant(s) added to event: %1.', [1 => count($this->_contactIds)]);
      if ($numberNotSent > 0) {
        $statusMsg .= ' ' . ts('Email has NOT been sent to %1 contact(s) - communication preferences specify DO NOT EMAIL OR valid Email is NOT present. ', [1 => $numberNotSent]);
      }
      elseif (isset($params['send_receipt'])) {
        $statusMsg .= ' ' . ts('A confirmation email has been sent to ALL participants');
      }
    }
    return $statusMsg;
  }

  /**
   * Add local and global form rules.
   *
   * @return void
   */
  public function addRules(): void {
    $this->addFormRule(['CRM_Event_Form_Task_Register', 'formRule'], $this);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $values
   *   Posted values of the form.
   * @param $files
   * @param self $self
   *
   * @return array|true
   *   list of errors to be posted back to the form
   */
  public static function formRule($values, $files, $self) {
    $errorMsg = [];
    if (!empty($values['record_contribution'])) {
      if (empty($values['financial_type_id'])) {
        $errorMsg['financial_type_id'] = ts('Please enter the associated Financial Type');
      }
      if (empty($values['payment_instrument_id'])) {
        $errorMsg['payment_instrument_id'] = ts('Payment Method is a required field.');
      }
      if (!empty($values['priceSetId'])) {
        CRM_Price_BAO_PriceField::priceSetValidation($values['priceSetId'], $values, $errorMsg);
      }
    }

    // do the amount validations.
    //skip for update mode since amount is freeze, CRM-6052
    if (empty($values['total_amount']) &&
        empty($self->_values['line_items'])
      ) {
      $priceSetId = $values['priceSetId'] ?? NULL;
      if ($priceSetId) {
        CRM_Price_BAO_PriceField::priceSetValidation($priceSetId, $values, $errorMsg, TRUE);
      }
    }

    return empty($errorMsg) ? TRUE : $errorMsg;
  }

  /**
   * Get any existing participant records for the given contact ID.
   *
   * @internal this function is expected to change. It should only be called from tested
   * core functions. If other core forms need this function we should move it to the EventFormTrait,
   * which holds functions shared between core forms.
   *
   * The expectation is that at some point an api will be available for getDuplicates functionality
   * that would be used by this function, and by the import code. It would also be called
   * by the Participant.validate api once it emerges.
   *
   * @param int $contactID
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function getExistingParticipantRecords(int $contactID): array {
    $participants = Participant::get(FALSE)
      ->addWhere('contact_id', '=', $contactID)
      ->addWhere('event_id', '=', $this->getEventID())
      ->addWhere('is_test', '=', FALSE)
      // @todo - consider also adding this filter - it is used elsewhere
      // and everything points to it being omitted by accident rather than
      // on purpose on this form.
      // if added, then add to the data provider for testRegisterDuplicateParticipant
      // ->addWhere('participant_status_id.name', '!=', 'Cancelled')
      ->execute();
    return (array) $participants;
  }

}
