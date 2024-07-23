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

namespace Civi\Api4\Action\Participant;

use Civi\Api4\Generic\ValidateAction;
use Civi\Api4\Event\ValidateValuesEvent;
use Civi\API\EntityLookupTrait;

/**
 * Validate membership parameters before creating/updating Memberships.
 */
class Validate extends ValidateAction {
  use EntityLookupTrait;

  public $context = [];

  protected function onValidateValues(ValidateValuesEvent $e) {
    foreach ($e->records as $recordKey => $record) {
      parent::onValidateValues($e);
      if (!empty($e->event_id) && !$this->isDefined('Event' . $e->event_id)) {
        $this->define('Event', 'Event' . $e->event_id, ['id' => $e->event_id]);
      }
      if (!empty($record['id'])) {
        $this->validateCreate($record, $recordKey);
      }
      else {
        $this->validateUpdate($record, $recordKey);
      }
    }
  }

  private function validateCreate($record, $recordKey) {
    $errors = [];
    // First check the contact record does not already exist.
    $roleID = $record['role_id'] ?? NULL;
    if (!$roleID) {
      $roleID = $this->lookup('Event' . $record['event_id'], 'default_role_id');
    }
    $existing = \Civi\Api4\Participant::get(FALSE)
      ->addSelect('participant_status_id:name')
      ->addSelect('participant_status_id.is_counted')
      ->addSelect('event_id.has_waitlist')
      ->addSelect('event_id.requires_approval')
      ->addWhere('event_id', '=', $record['event_id'])
      ->addWhere('contact_id', '=', $record['event_id'])
      // The idea behind the class is that the statuses should be configurable.
      // It should be a better pick than just going for 'Cancelled'
      ->addWhere('role_id:class', '!=',  'Negative')
      // Allow multiple test attempts as it is reasonable to think an
      // admin might want to hammer the form a bit
      ->addWhere('is_test', '=', FALSE)
      // It is OK to have more than one registration, if they have different role.s
      ->addWhere('role_id:name', 'NOT IN', (array) $roleID)
     ->execute();

    if ($existing) {
      $existingStatuses = [];
      // The message may get some wrangling according to the waitlist combo.
      // Note it might be that 'allow_same_participant_emails'
      foreach ($existing as $participant) {
        if ($participant['participant_status_id:name'] === 'On waitlist') {
          // Do we want to return context - ie the record. THat feels like it might be the sort of
          // hack we think is a good idea to save the calling code a possibly fetch but would probably live to regret
          // (remember those pesky extension writers who like do unexpected things in unexpected places).
          $errors['duplidate'] = ['type' => 'duplicate_on_waitlist', 'title' => ts('You already have a another waitlisted record'),
            'description' => ts("It looks like you are already waitlisted for this event. If you want to change your registration, or you feel that you've received this message in error, please contact the site administrator.");];
        }
      }
      if (empty($errors['duplidate'])) {
      $errors[] = ['type' => 'duplicate', 'title' => ts('Duplicate'), 'description' => 'blah'];
      }
    }

    $hasMaxParticipants = $this->lookup('Event' . $record['event_id'], 'max_participants');

    // We check if there are spaces and if the contact is authorized IF
    // the context is not admin or the logged-in user does not have permission to the admin
    // context.
    if (empty($this->context['is_admin'] || !\CRM_Core_Permission::check(['manage event or whatever the permission is called']))) {
      if ($this->lookup('Event' . $record['event_id'], 'requires_approval')) {
        $errors[] = $this->lookup('Event' . $record['event_id'], 'approval_req_text');

        if ($hasMaxParticipants) {
          // Also in here check if there are spaces if they ARE approved
          // In this case we don't consider other pending / waitlist ones cos that doesn't block being
          // added to the queue.
          $numberOfSpacesPotentiallyAvoilable = \CRM_Event_BAO_Participant::eventFull($record['event_id'],
            TRUE,
            FALSE,
            FALSE,
            FALSE,
            TRUE,
          );
          if (!$numberOfSpacesPotentiallyAvoilable) {
            // Can this be combined with waitlist?
            $errors[] = 'na-ha - even if we approve you there is nothing going on';
          }
        }
      }
      elseif ($this->lookup('Event' . $record['event_id'], 'has_waitlist')) {
        // probably we don't do ^^ & this
          $numberAlreadyWaitlisted = \CRM_Event_BAO_Participant::eventFull($record['event_id'],
            TRUE,
            TRUE,
            TRUE,
          );
        $availableSpaces = \CRM_Event_BAO_Participant::eventFull($record['event_id'],
          TRUE,
          FALSE,
        );
        $available = is_numeric($availableSpaces) ? (int) $availableSpaces : 0;
          if ($numberAlreadyWaitlisted || !$available) {
            $errors[] = $this->lookup('Event' . $record['event_id'], 'waitlist_text');
          }
        }
      elseif ($hasMaxParticipants) {
        // OK these guys have no waitlist, no approval process , no admin over-ride
        // so you gotta ask yourself do you feel lucky, well do ya.
        $availableSpaces = \CRM_Event_BAO_Participant::eventFull($record['event_id'],
          TRUE,
          FALSE,
        );
        $available = is_numeric($availableSpaces) ? (int) $availableSpaces : 0;
        if (!$available) {
          $errors[] = ['sorry punk we got nothing'];
        }
      }
    }

  }

  private function validateUpdate($record) {
    // Not sure if there is much to do in update mode on Participant.validate.
    // However, we would also implement Order.validate and in this context
    // having an id would probably mean there is an existing
    // participant record that we are now ready to pay for.
    // This might be because it has been approved or it was waitlisted
    // so in the order context we would check that it has
    // participant_status_id.class === 'Pending'
    // OR the context + user permission is such that we should ignore that
    // ie context = is_admin && permission = (manage events?)
    // We can't check only 1 as the admin user should get non-admin behaviour
    // in the front end whereas the non-admin user should not be able to
    // override by only setting one.
  }

}
