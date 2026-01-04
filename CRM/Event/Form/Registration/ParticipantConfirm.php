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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class generates form components for processing Event
 *
 */
class CRM_Event_Form_Registration_ParticipantConfirm extends CRM_Event_Form_Registration {

  /**
   * Optional credit card return status code
   * @var string
   * @see https://issues.civicrm.org/jira/browse/CRM-6060
   */
  protected $_cc = NULL;

  /**
   * Set variables up before form is built.
   *
   * @return void
   */
  public function preProcess() {
    $this->_participantId = CRM_Utils_Request::retrieve('participantId', 'Positive', $this);

    $this->_cc = CRM_Utils_Request::retrieve('cc', 'String', $this);

    //get the contact and event id and assing to session.
    $values = [];
    if ($this->_participantId) {
      $params = ['id' => $this->_participantId];
      CRM_Core_DAO::commonRetrieve('CRM_Event_DAO_Participant', $params, $values,
        ['contact_id', 'event_id', 'status_id']
      );
    }

    $this->_participantStatusId = $values['status_id'] ?? NULL;
    $this->_eventId = $values['event_id'] ?? NULL;
    $csContactId = $values['contact_id'] ?? NULL;

    // make sure we have right permission to edit this user
    $this->_csContactID = NULL;
    if ($csContactId && $this->_eventId) {
      $session = CRM_Core_Session::singleton();
      if ($csContactId == $session->get('userID')) {
        $this->_csContactID = $csContactId;
      }
      else {
        if (CRM_Contact_BAO_Contact_Permission::validateChecksumContact($csContactId, $this)) {
          //since we have landing page so get this contact
          //id in session if user really want to walk wizard.
          $this->_csContactID = $csContactId;
        }
      }
    }

    if (!$this->_csContactID) {
      $config = CRM_Core_Config::singleton();
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this event registration. Contact the site administrator if you need assistance.'), $config->userFrameworkBaseURL);
    }
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    $params = ['id' => $this->_eventId];
    $values = [];
    CRM_Core_DAO::commonRetrieve('CRM_Event_DAO_Event', $params, $values,
      ['title']
    );

    $buttons = [];
    // only pending status class family able to confirm.

    $statusMsg = NULL;
    if (array_key_exists($this->_participantStatusId,
      CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Pending'")
    )) {

      //need to confirm that though participant confirming
      //registration - but is there enough space to confirm.
      $emptySeats = CRM_Event_BAO_Participant::eventFull($this->_eventId, TRUE, FALSE, TRUE, FALSE, TRUE);
      $additionalIds = CRM_Event_BAO_Participant::getAdditionalParticipantIds($this->_participantId);
      $requireSpace = 1 + count($additionalIds);
      if ($emptySeats !== NULL && ($requireSpace > $emptySeats)) {
        $statusMsg = ts("Unfortunately there are currently no available spaces for the %1 event.", [1 => $values['title']]);
      }
      else {
        if ($this->_cc == 'fail') {
          $statusMsg = '<div class="bold">' . ts('Your Credit Card transaction was not successful. No money has yet been charged to your card.') . '</div><div><br />' . ts('Click the "Confirm Registration" button to complete your registration in %1, or click "Cancel Registration" if you are no longer interested in attending this event.', [
            1 => $values['title'],
          ]) . '</div>';
        }
        else {
          $statusMsg = '<div class="bold">' . ts('Confirm your registration for %1.', [
            1 => $values['title'],
          ]) . '</div><div><br />' . ts('Click the "Confirm Registration" button to begin, or click "Cancel Registration" if you are no longer interested in attending this event.') . '</div>';
        }
        $buttons = array_merge($buttons, [
          [
            'type' => 'next',
            'name' => ts('Confirm Registration'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
          ],
        ]);
      }
    }

    // status class other than Negative should be able to cancel registration.
    if (array_key_exists($this->_participantStatusId,
      CRM_Event_PseudoConstant::participantStatus(NULL, "class != 'Negative'")
    )) {
      $cancelConfirm = ts('Are you sure you want to cancel your registration for this event?');
      $buttons = array_merge($buttons, [
        [
          'type' => 'submit',
          'name' => ts('Cancel Registration'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'js' => ['onclick' => 'return confirm(\'' . $cancelConfirm . '\');'],
        ],
      ]);
      if (!$statusMsg) {
        $statusMsg = ts('You can cancel your registration for %1 by clicking "Cancel Registration".', [1 => $values['title']]);
      }
    }
    if (!$statusMsg) {
      $statusMsg = ts("Your registration for %1 has already been cancelled. No further action is needed.",
        [1 => $values['title']]
      );
    }
    $this->assign('statusMsg', $statusMsg);

    $this->addButtons($buttons);
  }

  /**
   * Process the form submission.
   *
   *
   * @return void
   */
  public function postProcess() {
    //get the button.
    $buttonName = $this->controller->getButtonName();
    $participantId = $this->_participantId;

    if ($buttonName == '_qf_ParticipantConfirm_next') {
      //lets get contact id in session.
      $session = CRM_Core_Session::singleton();
      $session->set('userID', $this->_csContactID);

      $this->postProcessHook();

      //check user registration status is from pending class
      $url = CRM_Utils_System::url('civicrm/event/register',
        "reset=1&id={$this->_eventId}&participantId={$participantId}"
      );
      CRM_Utils_System::redirect($url);
    }
    elseif ($buttonName == '_qf_ParticipantConfirm_submit') {
      //need to registration status to 'cancelled'.

      $cancelledId = array_search('Cancelled', CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Negative'"));
      $additionalParticipantIds = CRM_Event_BAO_Participant::getAdditionalParticipantIds($participantId);

      $participantIds = array_merge([$participantId], $additionalParticipantIds);
      $results = CRM_Event_BAO_Participant::transitionParticipants($participantIds, $cancelledId, NULL, TRUE);

      if (count($participantIds) > 1) {
        $statusMessage = ts("%1 Event registration(s) have been cancelled.", [1 => count($participantIds)]);
      }
      else {
        $statusMessage = ts("Your Event Registration has been cancelled.");
      }

      if (!empty($results['mailedParticipants'])) {
        foreach ($results['mailedParticipants'] as $key => $displayName) {
          $statusMessage .= "<br />" . ts("Email has been sent to : %1", [1 => $displayName]);
        }
      }

      $this->postProcessHook();
      CRM_Core_Session::setStatus($statusMessage);
      // The noFullMsg here is about suppressing the event full message as the person
      // is potentially just being bounced to that screen.
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/event/info',
          "reset=1&id={$this->_eventId}&noFullMsg=1",
          FALSE, NULL, FALSE, TRUE
        )
      );
    }
  }

}
