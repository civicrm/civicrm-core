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
 * Class CRM_Event_Participant_Tokens
 *
 * Generate "participant.*" tokens.
 *
 */
class CRM_Event_Participant_Tokens extends \Civi\Token\AbstractTokenSubscriber {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct('participant', array_merge(
      [
        'participant_id' => ts('Participant ID'),
        'status' => ts('Participant Status'),
        'role' => ts('Participant Role'),
        'register_date' => ts('Registration Date'),
        'source' => ts('Source'),
        'fee_level' => ts('Fee Level'),
        'fee_amount' => ts('Fee Amount'),
        'is_pay_later' => ts('Is Pay Later'),
        'must_wait' => ts('On Waiting List'),
      ],
      CRM_Utils_Token::getCustomFieldTokens('Participant')
    ));
  }

  /**
   * @inheritDoc
   */
  public function checkActive(\Civi\Token\TokenProcessor $processor) {
    // Extracted from scheduled-reminders code. See the class description.
    return !empty($processor->context['actionMapping'])
      && $processor->context['actionMapping']->getEntity() === 'civicrm_participant';
  }

  /**
   * @inheritDoc
   */
  public function evaluateToken(\Civi\Token\TokenRow $row, $entity, $field, $prefetch = NULL) {
    $actionSearchResult = $row->context['actionSearchResult'];

    if ($field == 'participant_id') {
      $row->tokens($entity, $field, $actionSearchResult->contact_id);
    }
    elseif ($field == 'status') {
      $row->tokens($entity, $field, \CRM_Event_PseudoConstant::participantStatus($actionSearchResult->status_id, NULL, 'label'));
    }
    elseif ($field == 'role') {
      $role_ids = CRM_Utils_Array::explodePadded($actionSearchResult->role_id);
      foreach ($role_ids as $role_id) {
        $roles[] = \CRM_Event_PseudoConstant::participantRole($role_id);
      }
      $row->tokens($entity, $field, (!empty($roles)) ? implode(', ', $roles) : '');
    }
    elseif ($field == 'register_date') {
      $row->tokens($entity, $field, \CRM_Utils_Date::customFormat($actionSearchResult->register_date, Civi::settings()
        ->get('dateformatshortdate')));
    }
    elseif ($field == 'source') {
      $row->tokens($entity, $field, $actionSearchResult->source);
    }
    elseif ($field == 'fee_level') {
      $fee_level_multiple = \CRM_Utils_Array::explodePadded($actionSearchResult->fee_level);
      foreach ($fee_level_multiple as $fee_level_single) {
        $fee_levels[] = $fee_level_single;
      }
      $row->tokens($entity, $field, (!empty($fee_levels)) ? implode(', ', $fee_levels) : '');
    }
    elseif ($field == 'fee_amount') {
      $row->tokens($entity, $field, \CRM_Utils_Money::format($actionSearchResult->fee_amount));
    }
    elseif ($field == 'is_pay_later') {
      $row->tokens($entity, $field, ($actionSearchResult->is_pay_later == 0) ? '' : ts('You have opted to pay later for this event.'));
    }
    elseif ($field == 'must_wait') {
      $row->tokens($entity, $field, (empty($actionSearchResult->must_wait)) ? '' : ts('You have been added to the WAIT LIST for this event.'));
    }
    elseif ($cfID = \CRM_Core_BAO_CustomField::getKeyID($field)) {
      $row->customToken($entity, $cfID, $actionSearchResult->entity_id);
    }
    else {
      $row->tokens($entity, $field, '');
    }
  }

}
