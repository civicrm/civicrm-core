<?php
use Civi\Token\Event\TokenRegisterEvent;
use Civi\Token\Event\TokenValueEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CRM_Core_ActionScheduleTmp
 *
 * This is a transitional file - we've moved a chunk of code out of
 * CRM_Core_BAO_ActionSchedule into a smaller, event-driven class. However,
 * it should be broken up further into smaller, more specialized
 * classes for each mapping type (e.g. CRM_Activity_ActionSchedule,
 * CRM_Event_ActionSchedule, CRM_Member_ActionSchedule).
 */
class CRM_Core_ActionScheduleTmp implements EventSubscriberInterface {

  const ACTIVITY_MAPPING_ID = 1;
  const EVENT_TYPE_MAPPING_ID = 2;
  const EVENT_NAME_MAPPING_ID = 3;
  const MEMBERSHIP_TYPE_MAPPING_ID = 4;
  const EVENT_TPL_MAPPING_ID = 5;
  const CONTACT_MAPPING_ID = 6;

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    return array(
      \Civi\Token\Events::TOKEN_REGISTER => 'onRegisterTokens',
      \Civi\Token\Events::TOKEN_EVALUATE => 'onEvaluateTokens',
      \Civi\ActionSchedule\Events::MAPPINGS => 'onRegisterMappings',
    );
  }

  public function onRegisterMappings(\Civi\ActionSchedule\Event\MappingRegisterEvent $registrations) {
    $registrations->register(\Civi\ActionSchedule\Mapping::create(array(
      'id' => CRM_Core_ActionScheduleTmp::ACTIVITY_MAPPING_ID,
      'entity' => 'civicrm_activity',
      'entity_label' => ts('Activity'),
      'entity_value' => 'activity_type',
      'entity_value_label' => 'Activity Type',
      'entity_status' => 'activity_status',
      'entity_status_label' => 'Activity Status',
      'entity_date_start' => 'activity_date_time',
      'entity_recipient' => 'activity_contacts',
    )));
    $registrations->register(\Civi\ActionSchedule\Mapping::create(array(
      'id' => CRM_Core_ActionScheduleTmp::EVENT_TYPE_MAPPING_ID,
      'entity' => 'civicrm_participant',
      'entity_label' => ts('Event Type'),
      'entity_value' => 'event_type',
      'entity_value_label' => 'Event Type',
      'entity_status' => 'civicrm_participant_status_type',
      'entity_status_label' => 'Participant Status',
      'entity_date_start' => 'event_start_date',
      'entity_date_end' => 'event_end_date',
      'entity_recipient' => 'event_contacts',
    )));
    $registrations->register(\Civi\ActionSchedule\Mapping::create(array(
      'id' => CRM_Core_ActionScheduleTmp::EVENT_NAME_MAPPING_ID,
      'entity' => 'civicrm_participant',
      'entity_label' => ts('Event Name'),
      'entity_value' => 'civicrm_event',
      'entity_value_label' => 'Event Name',
      'entity_status' => 'civicrm_participant_status_type',
      'entity_status_label' => 'Participant Status',
      'entity_date_start' => 'event_start_date',
      'entity_date_end' => 'event_end_date',
      'entity_recipient' => 'event_contacts',
    )));
    $registrations->register(\Civi\ActionSchedule\Mapping::create(array(
      'id' => CRM_Core_ActionScheduleTmp::MEMBERSHIP_TYPE_MAPPING_ID,
      'entity' => 'civicrm_membership',
      'entity_label' => ts('Membership'),
      'entity_value' => 'civicrm_membership_type',
      'entity_value_label' => 'Membership Type',
      'entity_status' => 'auto_renew_options',
      'entity_status_label' => 'Auto Renew Options',
      'entity_date_start' => 'membership_join_date',
      'entity_date_end' => 'membership_end_date',
    )));
    $registrations->register(\Civi\ActionSchedule\Mapping::create(array(
      'id' => CRM_Core_ActionScheduleTmp::EVENT_TPL_MAPPING_ID,
      'entity' => 'civicrm_participant',
      'entity_label' => ts('Event Template'),
      'entity_value' => 'event_template',
      'entity_value_label' => 'Event Template',
      'entity_status' => 'civicrm_participant_status_type',
      'entity_status_label' => 'Participant Status',
      'entity_date_start' => 'event_start_date',
      'entity_date_end' => 'event_end_date',
      'entity_recipient' => 'event_contacts',
    )));
    $registrations->register(\Civi\ActionSchedule\Mapping::create(array(
      'id' => CRM_Core_ActionScheduleTmp::CONTACT_MAPPING_ID,
      'entity' => 'civicrm_contact',
      'entity_label' => ts('Contact'),
      'entity_value' => 'civicrm_contact',
      'entity_value_label' => 'Date Field',
      'entity_status' => 'contact_date_reminder_options',
      'entity_status_label' => 'Annual Options',
      'entity_date_start' => 'date_field',
    )));
  }

  /**
   * @param \Civi\ActionSchedule\Mapping $mapping
   * @return array
   *   Ex: $result['event']['start_date'] === 'Event Start Date'.
   */
  protected static function listMailingTokens($mapping) {
    $tokenEntity = NULL;

    $tokens = array(
      'civicrm_activity' => array(
        'activity' => array(
          'activity_id' => ts('Activity ID'),
          'activity_type' => ts('Activity Type'),
          'subject' => ts('Activity Subject'),
          'details' => ts('Activity Details'),
          'activity_date_time' => ts('Activity Date-Time'),
        ),
      ),
      'civicrm_participant' => array(
        'event' => array(
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
          'contact_email' => ts('Event Contact (Email)'),
          'contact_phone' => ts('Event Contact (Phone)'),
          'balance' => ts('Event Balance'),
        ),
      ),
      'civicrm_membership' => array(
        'membership' => array(
          'fee' => ts('Membership Fee'),
          'id' => ts('Membership ID'),
          'join_date' => ts('Membership Join Date'),
          'start_date' => ts('Membership Start Date'),
          'end_date' => ts('Membership End Date'),
          'status' => ts('Membership Status'),
          'type' => ts('Membership Type'),
        ),
      ),
      //'civicrm_contact' => array(
      //  'contact' => array(
      //    'birth_date' => ts('Birth Date'),
      //    'last_name' => ts('Last Name'),
      //  )
      //),
    );

    return isset($tokens[$mapping->entity]) ? $tokens[$mapping->entity] : array();
  }

  /**
   * Declare available tokens.
   *
   * @param TokenRegisterEvent $e
   */
  public function onRegisterTokens(TokenRegisterEvent $e) {
    if (!isset($e->getTokenProcessor()->context['actionMapping'])) {
      return;
    }

    $allTokenFields = self::listMailingTokens($e->getTokenProcessor()->context['actionMapping']);
    foreach ($allTokenFields as $tokenEntity => $tokenFields) {
      foreach ($tokenFields as $field => $label) {
        $e->register($field, $label);
      }
    }
  }

  /**
   * Load token data.
   *
   * @param TokenValueEvent $e
   * @throws TokenException
   */
  public function onEvaluateTokens(TokenValueEvent $e) {
    foreach ($e->getRows() as $row) {
      /** @var \Civi\Token\TokenRow $row */
      if (!isset($row->context['actionSearchResult'])) {
        continue;
      }
      $row->tokens(self::prepareMailingTokens($row->context['actionMapping'], $row->context['actionSearchResult']));
    }
  }

  /**
   * @param \Civi\ActionSchedule\Mapping $mapping
   * @param $dao
   * @return array
   *   Ex: array('activity' => array('subject' => 'Hello world)).
   */
  protected static function prepareMailingTokens($mapping, $dao) {
    $allTokenFields = self::listMailingTokens($mapping);

    $entityTokenParams = array();
    foreach ($allTokenFields as $tokenEntity => $tokenFields) {
      foreach ($tokenFields as $field => $fieldLabel) {
        if ($field == 'location') {
          $loc = array();
          $stateProvince = \CRM_Core_PseudoConstant::stateProvince();
          $loc['street_address'] = $dao->street_address;
          $loc['city'] = $dao->city;
          $loc['state_province'] = \CRM_Utils_Array::value($dao->state_province_id, $stateProvince);
          $loc['postal_code'] = $dao->postal_code;
          $entityTokenParams[$tokenEntity][$field] = \CRM_Utils_Address::format($loc);
        }
        elseif ($field == 'info_url') {
          $entityTokenParams[$tokenEntity][$field] = \CRM_Utils_System::url('civicrm/event/info', 'reset=1&id=' . $dao->event_id, TRUE, NULL, FALSE);
        }
        elseif ($field == 'registration_url') {
          $entityTokenParams[$tokenEntity][$field] = \CRM_Utils_System::url('civicrm/event/register', 'reset=1&id=' . $dao->event_id, TRUE, NULL, FALSE);
        }
        elseif (in_array($field, array('start_date', 'end_date', 'join_date', 'activity_date_time'))) {
          $entityTokenParams[$tokenEntity][$field] = \CRM_Utils_Date::customFormat($dao->$field);
        }
        elseif ($field == 'balance') {
          if ($dao->entityTable == 'civicrm_contact') {
            $balancePay = 'N/A';
          }
          elseif (!empty($dao->entityID)) {
            $info = \CRM_Contribute_BAO_Contribution::getPaymentInfo($dao->entityID, 'event');
            $balancePay = \CRM_Utils_Array::value('balance', $info);
            $balancePay = \CRM_Utils_Money::format($balancePay);
          }
          $entityTokenParams[$tokenEntity][$field] = $balancePay;
        }
        elseif ($field == 'fee_amount') {
          $entityTokenParams[$tokenEntity][$field] = CRM_Utils_Money::format($dao->$field);
        }
        else {
          $entityTokenParams[$tokenEntity][$field] = $dao->$field;
        }
      }
    }
    return $entityTokenParams;
  }

}
