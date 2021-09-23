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

use Civi\ActionSchedule\Event\MailingQueryEvent;
use Civi\Api4\Event;

/**
 * Class CRM_Event_Tokens
 *
 * Generate "event.*" tokens.
 *
 * This TokenSubscriber was produced by refactoring the code from the
 * scheduled-reminder system with the goal of making that system
 * more flexible. The current implementation is still coupled to
 * scheduled-reminders. It would be good to figure out a more generic
 * implementation which is not tied to scheduled reminders, although
 * that is outside the current scope.
 */
class CRM_Event_Tokens extends CRM_Core_EntityTokens {

  /**
   * Get the entity name for api v4 calls.
   *
   * @return string
   */
  protected function getApiEntityName(): string {
    return 'Event';
  }

  /**
   * Get all tokens.
   *
   * This function will be removed once the parent class can determine it.
   */
  public function getAllTokens(): array {
    return array_merge(
      [
        'event_type' => ts('Event Type'),
        'title' => ts('Event Title'),
        'event_id' => ts('Event ID'),
        'start_date' => ts('Event Start Date'),
        'end_date' => ts('Event End Date'),
        'summary' => ts('Event Summary'),
        'description' => ts('Event Description'),
        'location' => ts('Event Location'),
        'info_url' => ts('Event Info URL'),
        'registration_url' => ts('Event Registration URL'),
        'fee_amount' => ts('Event Fee'),
        'contact_email' => ts('Event Contact Email'),
        'contact_phone' => ts('Event Contact Phone'),
        'balance' => ts('Event Balance'),
      ],
      CRM_Utils_Token::getCustomFieldTokens('Event')
    );
  }

  /**
   * @inheritDoc
   */
  public function checkActive(\Civi\Token\TokenProcessor $processor) {
    // Extracted from scheduled-reminders code. See the class description.
    return ((!empty($processor->context['actionMapping'])
      && $processor->context['actionMapping']->getEntity() === 'civicrm_participant'))
      || in_array($this->getEntityIDField(), $processor->context['schema'], TRUE);
  }

  /**
   * Alter action schedule query.
   *
   * @param \Civi\ActionSchedule\Event\MailingQueryEvent $e
   */
  public function alterActionScheduleQuery(MailingQueryEvent $e): void {
    if ($e->mapping->getEntity() !== 'civicrm_participant') {
      return;
    }

    // FIXME: seems too broad.
    $e->query->select('e.*');
    $e->query->select('ov.label as event_type, ev.title, ev.id as event_id, ev.start_date, ev.end_date, ev.summary, ev.description, address.street_address, address.city, address.state_province_id, address.postal_code, email.email as contact_email, phone.phone as contact_phone');
    $e->query->join('participant_stuff', "
!casMailingJoinType civicrm_event ev ON e.event_id = ev.id
!casMailingJoinType civicrm_option_group og ON og.name = 'event_type'
!casMailingJoinType civicrm_option_value ov ON ev.event_type_id = ov.value AND ov.option_group_id = og.id
LEFT JOIN civicrm_loc_block lb ON lb.id = ev.loc_block_id
LEFT JOIN civicrm_address address ON address.id = lb.address_id
LEFT JOIN civicrm_email email ON email.id = lb.email_id
LEFT JOIN civicrm_phone phone ON phone.id = lb.phone_id
");
  }

  /**
   * @inheritDoc
   */
  public function evaluateToken(\Civi\Token\TokenRow $row, $entity, $field, $prefetch = NULL) {
    $actionSearchResult = $row->context['actionSearchResult'];
    $eventID = $row->context['eventId'] ?? $actionSearchResult->event_id;
    if (array_key_exists($field, $this->getEventTokenValues($eventID))) {
      foreach ($this->getEventTokenValues($eventID)[$field] as $format => $value) {
        $row->format($format)->tokens($entity, $field, $value);
      }
      return;
    }

    if ($field === 'event_id') {
      // @todo - migrate this to 'id'
      $row->tokens($entity, $field, $eventID);
    }
    elseif ($field === 'event_type') {
      // temporary - @todo db update to event_type_id:label
      $row->tokens($entity, $field, $this->getEventTokenValues($eventID)['event_type_id:label']['text/html']);
    }
    elseif ($field === 'balance') {
      if ($actionSearchResult->entityTable === 'civicrm_contact') {
        $balancePay = 'N/A';
      }
      elseif (!empty($actionSearchResult->entityID)) {
        $info = \CRM_Contribute_BAO_Contribution::getPaymentInfo($actionSearchResult->entityID, 'event');
        $balancePay = $info['balance'] ?? NULL;
        $balancePay = \CRM_Utils_Money::format($balancePay);
      }
      $row->tokens($entity, $field, $balancePay);
    }
    elseif ($field === 'fee_amount') {
      $row->tokens($entity, $field, \CRM_Utils_Money::format($actionSearchResult->$field));
    }
    elseif ($cfID = CRM_Core_BAO_CustomField::getKeyID($field)) {
      $row->customToken($entity, $cfID, $actionSearchResult->event_id);
    }
    else {
      parent::evaluateToken($row, $entity, $field, $prefetch);
    }
  }

  /**
   * Get the tokens available for the event.
   *
   * Cache by event as it's l
   *
   * @param int|null $eventID
   *
   * @return array
   *
   * @throws \API_Exception|\CRM_Core_Exception
   *
   * @internal
   */
  protected function getEventTokenValues(int $eventID = NULL): array {
    $cacheKey = __CLASS__ . 'event_tokens' . $eventID . '_' . CRM_Core_I18n::getLocale();
    if (!Civi::cache('metadata')->has($cacheKey)) {
      $event = Event::get(FALSE)->addWhere('id', '=', $eventID)
        ->setSelect([
          'event_type_id',
          'title',
          'id',
          'start_date',
          'end_date',
          'summary',
          'description',
          'loc_block_id',
          'loc_block_id.address_id.street_address',
          'loc_block_id.address_id.city',
          'loc_block_id.address_id.state_province_id:label',
          'loc_block_id.address_id.postal_code',
          'loc_block_id.email_id.email',
          'loc_block_id.phone_id.phone',
          'custom.*',
        ])
        ->execute()->first();
      $tokens['location']['text/plain'] = \CRM_Utils_Address::format([
        'street_address' => $event['loc_block_id.address_id.street_address'],
        'city' => $event['loc_block_id.address_id.city'],
        'state_province' => $event['loc_block_id.address_id.state_province_id:label'],
        'postal_code' => $event['loc_block_id.address_id.postal_code'],

      ]);
      $tokens['info_url']['text/html'] = \CRM_Utils_System::url('civicrm/event/info', 'reset=1&id=' . $eventID, TRUE, NULL, FALSE);
      $tokens['registration_url']['text/html'] = \CRM_Utils_System::url('civicrm/event/register', 'reset=1&id=' . $eventID, TRUE, NULL, FALSE);
      $tokens['start_date']['text/html'] = !empty($event['start_date']) ? new DateTime($event['start_date']) : '';
      $tokens['end_date']['text/html'] = !empty($event['end_date']) ? new DateTime($event['end_date']) : '';
      $tokens['event_type_id:label']['text/html'] = CRM_Core_PseudoConstant::getLabel('CRM_Event_BAO_Event', 'event_type_id', $event['event_type_id']);
      $tokens['contact_phone']['text/html'] = $event['loc_block_id.phone_id.phone'];
      $tokens['contact_email']['text/html'] = $event['loc_block_id.email_id.email'];

      foreach (array_keys($this->getAllTokens()) as $field) {
        if (!isset($tokens[$field]) && isset($event[$field])) {
          if ($this->isCustomField($field)) {
            $tokens[$field]['text/html'] = CRM_Core_BAO_CustomField::displayValue($event[$field], str_replace('custom_', '', $field));
          }
          else {
            $tokens[$field]['text/html'] = $event[$field];
          }
        }
      }
      Civi::cache('metadata')->set($cacheKey, $tokens);
    }
    return Civi::cache('metadata')->get($cacheKey);
  }

}
