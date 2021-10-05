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

use Civi\Api4\Event;
use Civi\Token\TokenRow;

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
  protected function getBespokeTokens(): array {
    return [
      'location' => [
        'title' => ts('Event Location'),
        'name' => 'location',
        'type' => 'calculated',
        'options' => NULL,
        'data_type' => 'String',
      ],
      'info_url' => [
        'title' => ts('Event Info URL'),
        'name' => 'info_url',
        'type' => 'calculated',
        'options' => NULL,
        'data_type' => 'String',
      ],
      'registration_url' => [
        'title' => ts('Event Registration URL'),
        'name' => 'registration_url',
        'type' => 'calculated',
        'options' => NULL,
        'data_type' => 'String',
      ],
      'contact_email' => [
        'title' => ts('Event Contact Email'),
        'name' => 'contact_email',
        'type' => 'calculated',
        'options' => NULL,
        'data_type' => 'String',
      ],
      'contact_phone' => [
        'title' => ts('Event Contact Phone'),
        'name' => 'contact_phone',
        'type' => 'calculated',
        'options' => NULL,
        'data_type' => '',
      ],
    ];
  }

  /**
   * @inheritDoc
   * @throws \API_Exception
   */
  public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = NULL) {
    $eventID = $this->getFieldValue($row, 'id');
    if (!$eventID) {
      $eventID = $row->context['actionSearchResult']->event_id;
    }
    if (array_key_exists($field, $this->getEventTokenValues($eventID))) {
      foreach ($this->getEventTokenValues($eventID)[$field] as $format => $value) {
        $row->format($format)->tokens($entity, $field, $value);
      }
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
        ->setSelect(array_merge([
          'loc_block_id.address_id.street_address',
          'loc_block_id.address_id.city',
          'loc_block_id.address_id.state_province_id:label',
          'loc_block_id.address_id.postal_code',
          'loc_block_id.email_id.email',
          'loc_block_id.phone_id.phone',
          'custom.*',
        ], $this->getExposedFields()))
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
      $tokens['event_type_id:name']['text/html'] = CRM_Core_PseudoConstant::getName('CRM_Event_BAO_Event', 'event_type_id', $event['event_type_id']);
      $tokens['contact_phone']['text/html'] = $event['loc_block_id.phone_id.phone'];
      $tokens['contact_email']['text/html'] = $event['loc_block_id.email_id.email'];

      foreach (array_keys($this->getAllTokens()) as $field) {
        if (!isset($tokens[$field])) {
          if ($this->isCustomField($field)) {
            $this->prefetch[$eventID] = $event;
            $tokens[$field]['text/html'] = $this->getCustomFieldValue($eventID, $field);
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

  /**
   * Get entity fields that should be exposed as tokens.
   *
   * Event has traditionally exposed very few fields. This is probably because
   * a) there are a tonne of weird fields so an opt out approach doesn't work and
   * b) so people just added what they needed at the time...
   *
   * @return string[]
   *
   */
  protected function getExposedFields(): array {
    return ['event_type_id',
      'title',
      'id',
      'start_date',
      'end_date',
      'summary',
      'description',
    ];
  }

}
