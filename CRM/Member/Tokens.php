<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Member_Tokens
 *
 * Generate "member.*" tokens.
 *
 * This TokenSubscriber was produced by refactoring the code from the
 * scheduled-reminder system with the goal of making that system
 * more flexible. The current implementation is still coupled to
 * scheduled-reminders. It would be good to figure out a more generic
 * implementation which is not tied to scheduled reminders, although
 * that is outside the current scope.
 */
class CRM_Member_Tokens extends \Civi\Token\AbstractTokenSubscriber {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct('membership', array(
      'fee' => ts('Membership Fee'),
      'id' => ts('Membership ID'),
      'join_date' => ts('Membership Join Date'),
      'start_date' => ts('Membership Start Date'),
      'end_date' => ts('Membership End Date'),
      'status' => ts('Membership Status'),
      'type' => ts('Membership Type'),
    ));
  }

  /**
   * Is token active.
   *
   * @param \Civi\Token\TokenProcessor $processor
   *
   * @return bool
   */
  public function checkActive(\Civi\Token\TokenProcessor $processor) {
    // Extracted from scheduled-reminders code. See the class description.
    return
      !empty($processor->context['actionMapping'])
      && $processor->context['actionMapping']->getEntity() === 'civicrm_membership';
  }

  public function alterActionScheduleQuery(\Civi\ActionSchedule\Event\MailingQueryEvent $e) {
    if ($e->mapping->getEntity() !== 'civicrm_membership') {
      return;
    }

    $e->query
      ->select('e.*') // FIXME: seems too broad.
      ->select('mt.minimum_fee as fee, e.id as id , e.join_date, e.start_date, e.end_date, ms.name as status, mt.name as type')
      ->join('mt', "!casMailingJoinType civicrm_membership_type mt ON e.membership_type_id = mt.id")
      ->join('ms', "!casMailingJoinType civicrm_membership_status ms ON e.status_id = ms.id");
  }

  /**
   * Evaluate the content of a single token.
   *
   * @param \Civi\Token\TokenRow $row
   *   The record for which we want token values.
   * @param string $entity
   * @param string $field
   *   The name of the token field.
   * @param mixed $prefetch
   *   Any data that was returned by the prefetch().
   *
   * @return mixed
   */
  public function evaluateToken(\Civi\Token\TokenRow $row, $entity, $field, $prefetch = NULL) {
    $actionSearchResult = $row->context['actionSearchResult'];

    if (in_array($field, array('start_date', 'end_date', 'join_date'))) {
      $row->tokens($entity, $field, \CRM_Utils_Date::customFormat($actionSearchResult->$field));
    }
    elseif (isset($actionSearchResult->$field)) {
      $row->tokens($entity, $field, $actionSearchResult->$field);
    }
    else {
      $row->tokens($entity, $field, '');
    }
  }

}
