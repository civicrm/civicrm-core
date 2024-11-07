<?php

use Civi\Api4\LineItem;
use Civi\Api4\Participant;

/**
 * Trait for participant workflow classes.
 *
 * @method int getParticipantID()
 * @method int getEventID()
 */
trait CRM_Event_WorkflowMessage_ParticipantTrait {

  use CRM_Contribute_WorkflowMessage_ContributionTrait;
  use CRM_Core_WorkflowMessage_ProfileTrait;

  /**
   * @var int
   *
   * @scope tokenContext as participantId, tplParams as participantID
   */
  public $participantID;

  /**
   * The participant record.
   *
   * @var array|null
   *
   * @scope tokenContext as participant
   */
  public $participant;

  /**
   * Is this the primary participant.
   *
   * @var bool
   *
   * @scope tplParams as isPrimary
   */
  public $isPrimary;

  /**
   * Should a participant count column be shown.
   *
   * This would be true if there is a line item on the receipt
   * with more than one participant in it. Otherwise it's confusing to
   * show.
   *
   * @var bool
   *
   * @scope tplParams as isShowParticipantCount
   */
  public $isShowParticipantCount;

  /**
   * What is the participant count, if 'specifically configured'.
   *
   * See getter notes.
   *
   * @var bool
   *
   * @scope tplParams as participantCount
   */
  public $participantCount;

  /**
   * @var int
   *
   * @scope tokenContext as eventId, tplParams as eventID
   */
  public $eventID;

  /**
   * Line items indexed by the participant.
   *
   * The format is otherwise the same as lineItems which is also available on the
   * template. The by-participant re-keying permits only including the current
   * participant for non-primary participants and
   * creating a by-participant table for the primary participant.
   *
   * @var array
   *
   * @scope tplParams as participants
   */
  public $participants;

  /**
   * The current participant (if there are multiple this is the one being emailed).
   *
   * This uses the same format as the participants array.
   *
   * @var array
   *
   * @scope tplParams as participant
   */
  public $currentParticipant;

  /**
   * Details of the participant contacts.
   *
   * This would normally be loaded but exists to allow the example to set them.
   *
   * @var array
   */
  protected $participantContacts;

  private function isCiviContributeEnabled(): bool {
    return array_key_exists('Contribution', \Civi::service('action_object_provider')->getEntities());
  }

  /**
   * @param array $participantContacts
   *
   * @return CRM_Event_WorkflowMessage_ParticipantTrait
   */
  public function setParticipantContacts(array $participantContacts): self {
    $this->participantContacts = $participantContacts;
    return $this;
  }

  /**
   * @param int $eventID
   *
   * @return CRM_Event_WorkflowMessage_ParticipantTrait
   */
  public function setEventID(int $eventID): self {
    $this->eventID = $eventID;
    return $this;
  }

  /**
   * @throws \Civi\API\Exception\UnauthorizedException
   * @throws \CRM_Core_Exception
   */
  public function setParticipantID(int $participantID) {
    $this->participantID = $participantID;
    if (!$this->getContributionID() && $this->isCiviContributeEnabled()) {
      $lineItem = LineItem::get(FALSE)
        ->addWhere('entity_table', '=', 'civicrm_participant')
        ->addWhere('entity_id', '=', $participantID)
        ->addSelect('contribution_id')
        ->execute()->first();
      if (!empty($lineItem)) {
        $this->setContributionID($lineItem['contribution_id']);
      }
      else {
        // It might be bad data on the site - let's do a noisy fall back to participant payment
        // (the relationship between contribution & participant should be in the line item but
        // some integrations might mess this up - if they are not using the order api).
        // Note that for free events there won't be a participant payment either hence moving the status message into the if statement.
        $participantPayment = civicrm_api3('ParticipantPayment', 'get', ['participant_id' => $participantID])['values'];
        if (!empty($participantPayment)) {
          // no ts() since this should be rare
          CRM_Core_Error::deprecatedWarning('There might be a data problem, contribution id could not be loaded from the line item');
          $participantPayment = reset($participantPayment);
          $this->setContributionID((int) $participantPayment['contribution_id']);
        }
      }
    }
    return $this;
  }

  /**
   * Is the participant the primary participant.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function getIsPrimary(): bool {
    return !$this->getParticipant()['registered_by_id'];
  }

  /**
   * @return int
   */
  public function getPrimaryParticipantID(): int {
    return $this->participant['registered_by_id'] ?: $this->participantID;
  }

  /**
   * It is a good idea to show the participant count column.
   *
   * This would be true if there is a line item on the receipt
   * with more than one participant in it. Otherwise it's confusing to
   * show.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function getIsShowParticipantCount(): bool {
    return (bool) $this->getParticipantCount();
  }

  /**
   * Get the count of participants, where count is used in the line items.
   *
   * This might be the case where a line item represents a table of 6 people.
   *
   * Where the price field value does not record the participant count we ignore.
   *
   * This lack of specifying it is a bit unclear but seems to be 'presumed 1'.
   * From the templates point of view it is not information to present if not
   * configured.
   *
   * @throws \CRM_Core_Exception
   */
  public function getParticipantCount() {
    $count = 0;
    foreach ($this->getLineItems() as $lineItem) {
      $count += $lineItem['participant_count'];
    }
    return $count;
  }

  /**
   * Set participant object.
   *
   * @param array $participant
   *
   * @return $this
   */
  public function setParticipant(array $participant): self {
    $this->participant = $participant;
    if (!empty($participant['id'])) {
      $this->setParticipantID($participant['id']);
    }
    if (!empty($participant['event_id'])) {
      $this->eventID = $participant['event_id'];
    }
    return $this;
  }

  /**
   * Get the participant record.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getParticipant(): array {
    if (!$this->participant) {
      $this->participant = Participant::get(FALSE)
        ->addWhere('id', '=', $this->participantID)
        ->setSelect($this->getFieldsToLoadForParticipant())->execute()->first();
    }
    return $this->participant;
  }

  /**
   * Get the participant fields we need to load.
   */
  protected function getFieldsToLoadForParticipant(): array {
    return ['registered_by_id', 'contact_id'];
  }

  /**
   * Get the line items and tax information indexed by participant.
   *
   * We will likely add profile data to this too. This is so we can iterate through
   * participants as the primary participant needs to show them all (and the others
   * need to be able to filter).
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getCurrentParticipant(): array {
    // @todo - it is only because of some messed up tests which use
    // the legacy testSubmit function we have ?? []
    return $this->getParticipants()[$this->participantID] ?? [];
  }

  /**
   * Get the line items and tax information indexed by participant.
   *
   * We will likely add profile data to this too. This is so we can iterate through
   * participants as the primary participant needs to show them all (and the others
   * need to be able to filter).
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getParticipants(): array {
    if (!$this->participants) {
      // Initiate with the current participant to ensure they are first.
      $participants = [$this->participantID => ['id' => $this->participantID, 'tax_rate_breakdown' => []]];
      if ($this->getLineItems() && $this->isCiviContributeEnabled()) {
        foreach ($this->getLineItems() as $lineItem) {
          if ($lineItem['entity_table'] === 'civicrm_participant') {
            $participantID = (int) $lineItem['entity_id'];
          }
          else {
            // It is not clear if this could ever be true - testing the CiviCRM event
            // form shows all line items assigned to participants but we should
            // assign to primary if this can occur.
            $participantID = $this->getPrimaryParticipantID();
          }
          $participants[$participantID]['line_items'][] = $lineItem;
          if (!isset($participants[$participantID]['totals'])) {
            $participants[$participantID]['totals'] = ['total_amount_exclusive' => 0, 'tax_amount' => 0, 'total_amount_inclusive' => 0];
          }
          $participants[$participantID]['totals']['total_amount_exclusive'] += $lineItem['line_total'];
          $participants[$participantID]['totals']['tax_amount'] += $lineItem['tax_amount'];
          $participants[$participantID]['totals']['total_amount_inclusive'] += ($lineItem['line_total'] + $lineItem['tax_amount']);
          if (!isset($participants[$participantID]['tax_rate_breakdown'])) {
            $participants[$participantID]['tax_rate_breakdown'] = [];
          }
          if (!isset($participants[$participantID]['tax_rate_breakdown'][$lineItem['tax_rate']])) {
            $participants[$participantID]['tax_rate_breakdown'][$lineItem['tax_rate']] = [
              'amount' => 0,
              'rate' => $lineItem['tax_rate'],
              'percentage' => sprintf('%.2f', $lineItem['tax_rate']),
            ];
          }
          $participants[$participantID]['tax_rate_breakdown'][$lineItem['tax_rate']]['amount'] += $lineItem['tax_amount'];
        }
      }
      elseif ($this->getIsPrimary()) {
        $participants += (array) Participant::get(FALSE)
          ->setSelect(['id'])
          ->addWhere('registered_by_id', '=', $this->getPrimaryParticipantID())
          ->execute()->indexBy('id');
      }
      $count = 1;
      foreach ($participants as $participantID => &$participant) {
        $participant['id'] = $participantID;
        $participant['is_primary'] = $this->getParticipantID() === $participantID;
        $participant['index'] = $count;
        $participant['contact'] = $this->getParticipantContact($participantID);
        foreach ($participant['tax_rate_breakdown'] ?? [] as $rate => $details) {
          if ($details['amount'] === 0.0) {
            unset($participant['tax_rate_breakdown'][$rate]);
          }
        }
        if (array_keys($participant['tax_rate_breakdown'] ?? []) === [0]) {
          // If the only tax rate charged is 0% then no tax breakdown is returned.
          $participant['tax_rate_breakdown'] = [];
        }
        if (!isset($participant['line_items'])) {
          $participant['line_items'] = [];
        }
        $count++;
      }
      $this->participants = $participants;
    }
    return $this->participants;
  }

  /**
   * @param $participantID
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function getParticipantContact($participantID = NULL) {
    if (!$participantID) {
      $participantID = $this->participantID;
    }
    if (empty($this->participantContacts[$participantID])) {
      $participantContact = Participant::get(FALSE)
        ->addWhere('id', '=', $participantID ?: $this->participantID)
        ->addSelect('contact_id.display_name', 'contact_id')
        ->execute()
        ->first();
      $this->participantContacts[$participantID] = ['id' => $participantContact['contact_id'], 'display_name' => $participantContact['contact_id.display_name']];
    }

    return $this->participantContacts[$participantID];
  }

}
