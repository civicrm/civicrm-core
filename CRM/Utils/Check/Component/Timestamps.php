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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Check_Component_Timestamps extends CRM_Utils_Check_Component {

  const DOCTOR_WHEN = 'https://github.com/civicrm/org.civicrm.doctorwhen';

  /**
   * Check that various columns are TIMESTAMP and not DATETIME. (CRM-9683, etal)
   *
   * @return array
   */
  public function checkSchema() {
    $problems = [];
    foreach (self::getConvertedTimestamps() as $target) {
      if (self::isFieldType($target['table'], $target['column'], 'datetime')) {
        $phrases = [];
        $phrases[] = sprintf('<em>%s.%s</em>', $target['table'], $target['column']);

        if ($target['changed']) {
          $phrases[] = sprintf('(New sites default to TIMESTAMP in v%s+)', $target['changed']);
        }
        else {
          $phrases[] = '(Experimental suggestion)';
        }

        if (isset($target['jira'])) {
          $phrases[] = sprintf(' [<a href="https://issues.civicrm.org/jira/browse/%s" target="_blank">%s</a>]', $target['jira'], $target['jira']);
        }

        $problems[] = implode(' ', $phrases);
      }
    }

    $messages = [];
    if ($problems) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__ . md5(implode(',', $problems)),
        '<p>' .
        ts('This MySQL database stores certain fields with data-type "DATETIME". To improve timezone support, you <em>may</em> want to change these from "DATETIME" to "TIMESTAMP".') .
        '</p>' .
        '<ul><li>' .
        implode('</li><li>', $problems) .
        '</li></ul>' .
        '<p>' .
        ts('Changing should improve data-quality for organizations working in multiple timezones. However, if you do change, then you may need to re-test any customizations or processes that reference these fields. Changing is <em>suggested</em> but not <em>required</em>.') .
        '</p>' .
        '<p>' .
        ts('For further discussion, please visit %1', [
          1 => sprintf('<a href="%s" target="_blank">%s</a>', self::DOCTOR_WHEN, self::DOCTOR_WHEN),
        ]) .
        '</p>',
        ts('Timestamps and Timezones'),
        \Psr\Log\LogLevel::NOTICE,
        'fa-clock-o'
      );
    }
    return $messages;
  }

  /**
   * @param string $table
   *   Ex: 'civicrm_log'.
   * @param string $column
   *   Ex: 'modified_date'.
   * @param string $expectType
   *   Ex: 'datetime' or 'timestamp'.
   * @return bool
   */
  public static function isFieldType($table, $column, $expectType) {
    $result = FALSE;
    $dao = CRM_Core_DAO::executeQuery('DESC ' . $table);
    while ($dao->fetch()) {
      if ($dao->Field === $column && strtolower($dao->Type) === $expectType) {
        $result = TRUE;
      }
    }
    return $result;
  }

  public static function getConvertedTimestamps() {
    return [
      ['table' => 'civicrm_cache', 'column' => 'created_date', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683', 'comment' => 'When was the cache item created'],
      ['table' => 'civicrm_cache', 'column' => 'expired_date', 'changed' => '4.7.20', 'jira' => 'CRM-9683', 'comment' => 'When should the cache item expire'],
      ['table' => 'civicrm_job', 'column' => 'last_run', 'changed' => '4.7.20', 'jira' => 'CRM-9683', 'comment' => 'When was this cron entry last run'],
      ['table' => 'civicrm_mailing_event_bounce', 'column' => 'time_stamp', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683', 'comment' => 'When this bounce event occurred.'],
      ['table' => 'civicrm_mailing_event_confirm', 'column' => 'time_stamp', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683', 'comment' => 'When this confirmation event occurred.'],
      ['table' => 'civicrm_mailing_event_delivered', 'column' => 'time_stamp', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683', 'comment' => 'When this delivery event occurred.'],
      ['table' => 'civicrm_mailing_event_forward', 'column' => 'time_stamp', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683', 'comment' => 'When this forward event occurred.'],
      ['table' => 'civicrm_mailing_event_opened', 'column' => 'time_stamp', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683', 'comment' => 'When this open event occurred.'],
      ['table' => 'civicrm_mailing_event_reply', 'column' => 'time_stamp', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683', 'comment' => 'When this reply event occurred.'],
      ['table' => 'civicrm_mailing_event_subscribe', 'column' => 'time_stamp', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683', 'comment' => 'When this subscription event occurred.'],
      ['table' => 'civicrm_mailing_event_trackable_url_open', 'column' => 'time_stamp', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683', 'comment' => 'When this trackable URL open occurred.'],
      ['table' => 'civicrm_mailing_event_unsubscribe', 'column' => 'time_stamp', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683', 'comment' => 'When this delivery event occurred.'],
      ['table' => 'civicrm_mailing', 'column' => 'created_date', 'changed' => '4.7.20', 'jira' => 'CRM-9683', 'comment' => 'Date and time this mailing was created.'],
      ['table' => 'civicrm_mailing', 'column' => 'scheduled_date', 'changed' => '4.7.20', 'jira' => 'CRM-9683', 'comment' => 'Date and time this mailing was scheduled.'],
      ['table' => 'civicrm_mailing', 'column' => 'approval_date', 'changed' => '4.7.20', 'jira' => 'CRM-9683', 'comment' => 'Date and time this mailing was approved.'],
      ['table' => 'civicrm_mailing_abtest', 'column' => 'created_date', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683', 'comment' => 'When was this item created'],
      ['table' => 'civicrm_mailing_job', 'column' => 'scheduled_date', 'changed' => '4.7.20', 'jira' => 'CRM-9683', 'comment' => 'date on which this job was scheduled.'],
      ['table' => 'civicrm_mailing_job', 'column' => 'start_date', 'changed' => '4.7.20', 'jira' => 'CRM-9683', 'comment' => 'date on which this job was started.'],
      ['table' => 'civicrm_mailing_job', 'column' => 'end_date', 'changed' => '4.7.20', 'jira' => 'CRM-9683', 'comment' => 'date on which this job ended.'],
      ['table' => 'civicrm_mailing_spool', 'column' => 'added_at', 'changed' => '4.7.20', 'jira' => 'CRM-9683', 'comment' => 'date on which this job was added.'],
      ['table' => 'civicrm_mailing_spool', 'column' => 'removed_at', 'changed' => '4.7.20', 'jira' => 'CRM-9683', 'comment' => 'date on which this job was removed.'],
      ['table' => 'civicrm_subscription_history', 'column' => 'date', 'changed' => '4.7.27', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-21157', 'comment' => 'Date of the (un)subscription'],
    ];
  }

}
