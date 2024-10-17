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
        'audience' => 'user',
      ],
      'info_url' => [
        'title' => ts('Event Info URL'),
        'name' => 'info_url',
        'type' => 'calculated',
        'options' => NULL,
        'data_type' => 'String',
        'audience' => 'user',
      ],
      'registration_url' => [
        'title' => ts('Event Registration URL'),
        'name' => 'registration_url',
        'type' => 'calculated',
        'options' => NULL,
        'data_type' => 'String',
        'audience' => 'user',
      ],
      'loc_block_id.email_id.email' => [
        'title' => ts('Event Contact Email'),
        'name' => 'loc_block_id.email_id.email',
        'type' => 'calculated',
        'options' => NULL,
        'data_type' => 'String',
        'audience' => 'user',
      ],
      'loc_block_id.email_2_id.email' => [
        'title' => ts('Event Contact Email 2'),
        'name' => 'loc_block_id.email_2_id.email',
        'type' => 'calculated',
        'options' => NULL,
        'data_type' => 'String',
        'audience' => 'sysadmin',
      ],
      'loc_block_id.phone_id.phone' => [
        'title' => ts('Event Contact Phone'),
        'name' => 'loc_block_id.phone_id.phone',
        'type' => 'calculated',
        'options' => NULL,
        'data_type' => '',
        'audience' => 'user',
      ],
      'loc_block_id.phone_id.phone_type_id' => [
        'title' => ts('Event Contact Phone'),
        'name' => 'loc_block_id.phone_id.phone_type_id',
        'type' => 'calculated',
        'options' => NULL,
        'data_type' => 'Int',
        'audience' => 'sysadmin',
      ],
      'loc_block_id.phone_id.phone_type_id:label' => [
        'title' => ts('Event Contact Phone'),
        'name' => 'loc_block_id.phone_id.phone_type_id:label',
        'type' => 'calculated',
        'options' => NULL,
        'data_type' => 'String',
        'audience' => 'sysadmin',
      ],
      'loc_block_id.phone_id.phone_ext' => [
        'title' => ts('Event Contact Phone Extension'),
        'name' => 'loc_block_id.phone_id.phone_ext',
        'type' => 'calculated',
        'options' => NULL,
        'data_type' => 'String',
        'audience' => 'sysadmin',
      ],
      'loc_block_id.phone_2_id.phone' => [
        'title' => ts('Event Contact Phone 2'),
        'name' => 'loc_block_id.phone_2_id.phone',
        'type' => 'calculated',
        'options' => NULL,
        'data_type' => '',
        'audience' => 'sysadmin',
      ],
      'loc_block_id.phone_2_id.phone_type_id' => [
        'title' => ts('Event Contact Phone'),
        'name' => 'loc_block_id.phone_2_id.phone_type_id',
        'type' => 'calculated',
        'options' => NULL,
        'data_type' => 'Int',
        'audience' => 'sysadmin',
      ],
      'loc_block_id.phone_2_id.phone_type_id:label' => [
        'title' => ts('Event Contact Phone 2'),
        'name' => 'loc_block_id.phone_2_id.phone_type_id:label',
        'type' => 'calculated',
        'options' => NULL,
        'data_type' => 'String',
        'audience' => 'sysadmin',
      ],
      'loc_block_id.phone_2_id.phone_ext' => [
        'title' => ts('Event Contact Phone 2 Extension'),
        'name' => 'loc_block_id.phone_2_id.phone_ext',
        'type' => 'calculated',
        'options' => NULL,
        'data_type' => 'String',
        'audience' => 'sysadmin',
      ],
    ];
  }

  /**
   * @inheritDoc
   * @throws \CRM_Core_Exception
   */
  public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = NULL) {
    $eventID = (int) $this->getFieldValue($row, 'id');
    if (array_key_exists($field, $this->getEventTokenValues($eventID))) {
      foreach ($this->getEventTokenValues($eventID)[$field] as $format => $value) {
        $row->format($format)->tokens($entity, $field, $value ?? '');
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
   * @throws \CRM_Core_Exception
   *
   * @internal
   */
  protected function getEventTokenValues(?int $eventID = NULL): array {
    if (!$eventID) {
      return [];
    }
    $cacheKey = __CLASS__ . 'event_tokens' . $eventID . '_' . CRM_Core_I18n::getLocale();
    if ($this->checkPermissions) {
      $cacheKey .= '__' . CRM_Core_Session::getLoggedInContactID();
    }
    if (!Civi::cache('metadata')->has($cacheKey)) {
      $event = Event::get($this->checkPermissions)->addWhere('id', '=', $eventID)
        ->setSelect(array_merge([
          'loc_block_id.address_id.*',
          'loc_block_id.address_id.state_province_id:label',
          'loc_block_id.address_id.country_id:label',
          'loc_block_id.email_id.email',
          'loc_block_id.email_2_id.email',
          'loc_block_id.phone_id.phone',
          'loc_block_id.phone_id.phone_type_id',
          'loc_block_id.phone_id.phone_ext',
          'loc_block_id.phone_id.phone_type_id:label',
          'loc_block_id.phone_2_id.phone',
          'loc_block_id.phone_2_id.phone_type_id',
          'loc_block_id.phone_2_id.phone_ext',
          'loc_block_id.phone_2_id.phone_type_id:label',
          'is_show_location:label',
          'allow_selfcancelxfer',
          'allow_selfcancelxfer:label',
          'selfcancelxfer_time',
          'is_public:label',
          'is_share',
          'is_share:label',
          'requires_approval',
          'requires_approval:label',
          'is_monetary:label',
          'event_type_id:name',
          'pay_later_text',
          'pay_later_receipt',
          'fee_label',
          'is_show_calendar_links:label',
          'custom.*',
        ], $this->getExposedFields()))
        ->execute()->first();
      $addressValues = ['address_name' => $event['loc_block_id.address_id.name']];
      foreach ($event as $key => $value) {
        if (strpos($key, 'loc_block_id.address_id.') === 0) {
          $addressValues[str_replace('loc_block_id.address_id.', '', $key)] = $value;
        }
      }
      $tokens['location']['text/plain'] = \CRM_Utils_Address::format($addressValues);
      $tokens['info_url']['text/html'] = \CRM_Utils_System::url('civicrm/event/info', 'reset=1&id=' . $eventID, TRUE, NULL, FALSE, TRUE);
      $tokens['registration_url']['text/html'] = \CRM_Utils_System::url('civicrm/event/register', 'reset=1&id=' . $eventID, TRUE, NULL, FALSE, TRUE);
      $tokens['start_date']['text/html'] = !empty($event['start_date']) ? new DateTime($event['start_date']) : '';
      $tokens['end_date']['text/html'] = !empty($event['end_date']) ? new DateTime($event['end_date']) : '';
      $tokens['contact_email']['text/html'] = $event['loc_block_id.email_id.email'];
      $tokens['contact_phone']['text/html'] = $event['loc_block_id.phone_id.phone'];

      foreach ($this->getTokenMetadata() as $fieldName => $fieldSpec) {
        if (!isset($tokens[$fieldName])) {
          if ($fieldSpec['type'] === 'Custom') {
            $this->prefetch[$eventID] = $event;
            $value = $event[$fieldSpec['name']];
            $tokens[$fieldName]['text/html'] = CRM_Core_BAO_CustomField::displayValue($value, $fieldSpec['custom_field_id']);
          }
          else {
            if ($this->isHTMLTextField($fieldName)) {
              $tokens[$fieldName]['text/html'] = $event[$fieldName];
            }
            else {
              $tokens[$fieldName]['text/plain'] = $event[$fieldName];
            }
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
    return [
      'event_type_id',
      'event_type_id:label',
      'title',
      'id',
      'pay_later_receipt',
      'start_date',
      'end_date',
      'summary',
      'description',
      'is_show_location',
      'is_public',
      'allow_selfcancelxfer',
      'selfcancelxfer_time',
      'confirm_email_text',
      'is_monetary',
      'fee_label',
      'is_show_calendar_links',
    ];
  }

  /**
   * Get any overrides for token metadata.
   *
   * This is most obviously used for setting the audience, which
   * will affect widget-presence.
   *
   * Changing the audience is done in order to simplify the
   * UI for more general users.
   *
   * @return \string[][]
   */
  protected function getTokenMetadataOverrides(): array {
    return [
      'allow_selfcancelxfer' => ['audience' => 'sysadmin'],
      'is_monetary' => ['audience' => 'sysadmin'],
      'is_public' => ['audience' => 'sysadmin'],
      'is_show_calendar_links' => ['audience' => 'sysadmin'],
      'is_show_location' => ['audience' => 'sysadmin'],
      'selfcancelxfer_time' => ['audience' => 'sysadmin'],
    ];
  }

  /**
   * These tokens still work but we don't advertise them.
   *
   * We will actively remove from the following places
   * - scheduled reminders
   * - add to 'blocked' on pdf letter & email
   *
   * & then at some point start issuing warnings for them.
   *
   * @return string[]
   */
  protected function getDeprecatedTokens(): array {
    return [
      'contact_phone' => 'loc_block_id.phone_id.phone',
      'contact_email' => 'loc_block_id.email_id.email',
    ];
  }

}
