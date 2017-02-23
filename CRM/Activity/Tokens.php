<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * Class CRM_Member_Tokens
 *
 * Generate "activity.*" tokens.
 *
 * This TokenSubscriber was produced by refactoring the code from the
 * scheduled-reminder system with the goal of making that system
 * more flexible. The current implementation is still coupled to
 * scheduled-reminders. It would be good to figure out a more generic
 * implementation which is not tied to scheduled reminders, although
 * that is outside the current scope.
 */
class CRM_Activity_Tokens extends \Civi\Token\AbstractTokenSubscriber {

  public function __construct() {
    parent::__construct('activity', array(
      'activity_id' => ts('Activity ID'),
      'activity_type' => ts('Activity Type'),
      'subject' => ts('Activity Subject'),
      'details' => ts('Activity Details'),
      'activity_date_time' => ts('Activity Date-Time'),
    ));
  }

  /**
   * Check is active.
   *
   * @param \Civi\Token\TokenProcessor $processor
   *
   * @return bool
   */
  public function checkActive(\Civi\Token\TokenProcessor $processor) {
    // Extracted from scheduled-reminders code. See the class description.
    return
      !empty($processor->context['actionMapping'])
      && $processor->context['actionMapping']->getEntity() === 'civicrm_activity';
  }

  public function alterActionScheduleQuery(\Civi\ActionSchedule\Event\MailingQueryEvent $e) {
    if ($e->mapping->getEntity() !== 'civicrm_activity') {
      return;
    }

    // The joint expression for activities needs some extra nuance to handle.
    // Multiple revisions of the activity.
    // Q: Could we simplify & move the extra AND clauses into `where(...)`?
    $e->query->param('casEntityJoinExpr', 'e.id = reminder.entity_id AND e.is_current_revision = 1 AND e.is_deleted = 0');

    $e->query->select('e.*'); // FIXME: seems too broad.
    $e->query->select('ov.label as activity_type, e.id as activity_id');

    $e->query->join("og", "!casMailingJoinType civicrm_option_group og ON og.name = 'activity_type'");
    $e->query->join("ov", "!casMailingJoinType civicrm_option_value ov ON e.activity_type_id = ov.value AND ov.option_group_id = og.id");

    // if CiviCase component is enabled, join for caseId.
    $compInfo = CRM_Core_Component::getEnabledComponents();
    if (array_key_exists('CiviCase', $compInfo)) {
      $e->query->select("civicrm_case_activity.case_id as case_id");
      $e->query->join('civicrm_case_activity', "LEFT JOIN `civicrm_case_activity` ON `e`.`id` = `civicrm_case_activity`.`activity_id`");
    }
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

    if (in_array($field, array('activity_date_time'))) {
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
