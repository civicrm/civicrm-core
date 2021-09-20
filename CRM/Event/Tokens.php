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

    if ($field == 'location') {
      $loc = [];
      $stateProvince = \CRM_Core_PseudoConstant::stateProvince();
      $loc['street_address'] = $actionSearchResult->street_address;
      $loc['city'] = $actionSearchResult->city;
      $loc['state_province'] = $stateProvince[$actionSearchResult->state_province_id] ?? NULL;
      $loc['postal_code'] = $actionSearchResult->postal_code;
      //$entityTokenParams[$tokenEntity][$field] = \CRM_Utils_Address::format($loc);
      $row->tokens($entity, $field, \CRM_Utils_Address::format($loc));
    }
    elseif ($field == 'info_url') {
      $row
        ->tokens($entity, $field, \CRM_Utils_System::url('civicrm/event/info', 'reset=1&id=' . $actionSearchResult->event_id, TRUE, NULL, FALSE));
    }
    elseif ($field == 'registration_url') {
      $row
        ->tokens($entity, $field, \CRM_Utils_System::url('civicrm/event/register', 'reset=1&id=' . $actionSearchResult->event_id, TRUE, NULL, FALSE));
    }
    elseif (in_array($field, ['start_date', 'end_date'])) {
      $row->tokens($entity, $field, \CRM_Utils_Date::customFormat($actionSearchResult->$field));
    }
    elseif ($field == 'balance') {
      if ($actionSearchResult->entityTable == 'civicrm_contact') {
        $balancePay = 'N/A';
      }
      elseif (!empty($actionSearchResult->entityID)) {
        $info = \CRM_Contribute_BAO_Contribution::getPaymentInfo($actionSearchResult->entityID, 'event');
        $balancePay = $info['balance'] ?? NULL;
        $balancePay = \CRM_Utils_Money::format($balancePay);
      }
      $row->tokens($entity, $field, $balancePay);
    }
    elseif ($field == 'fee_amount') {
      $row->tokens($entity, $field, \CRM_Utils_Money::format($actionSearchResult->$field));
    }
    elseif (isset($actionSearchResult->$field)) {
      $row->tokens($entity, $field, $actionSearchResult->$field);
    }
    elseif ($cfID = \CRM_Core_BAO_CustomField::getKeyID($field)) {
      $row->customToken($entity, $cfID, $actionSearchResult->entity_id);
    }
    else {
      $row->tokens($entity, $field, '');
    }
  }

}
