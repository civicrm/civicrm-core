<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2017
 */
class CRM_Utils_Check_Component_Timestamps extends CRM_Utils_Check_Component {

  const DOCTOR_WHEN = 'https://github.com/civicrm/org.civicrm.doctorwhen';

  /**
   * Check that various columns are TIMESTAMP and not DATETIME. (CRM-9683, etal)
   *
   * @return array
   */
  public function checkSchema() {
    $problems = array();
    foreach (self::getConvertedTimestamps() as $target) {
      if (self::isFieldType($target['table'], $target['column'], 'datetime')) {
        if ($target['changed']) {
          $problems[] = sprintf('<em>%s.%s</em> (New sites default to TIMESTAMP in %s+)', $target['table'], $target['column'], $target['changed']);
        }
        else {
          $problems[] = sprintf('<em>%s.%s</em> (Experimental suggestion)', $target['table'], $target['column']);
        }
      }
    }

    $messages = array();
    if ($problems) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__ . md5(implode(',', $problems)),
        '<p>' .
        ts('This system includes some SQL columns with type "DATETIME". These <b>may</b> work better as "TIMESTAMP".') .
        '</p>' .
        '<ul><li>' .
        implode('</li><li>', $problems) .
        '</li></ul>' .
        '<p>' .
        ts('For further discussion, please visit %1', array(
          1 => sprintf('<a href="%s" target="_blank">%s</a>', self::DOCTOR_WHEN, self::DOCTOR_WHEN),
        )) .
        '</p>',
        ts('Timestamp Schema'),
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
    $dao->free();
    return $result;
  }

  public static function getConvertedTimestamps() {
    return array(
      array('table' => 'civicrm_cache', 'column' => 'created_date', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683'),
      array('table' => 'civicrm_cache', 'column' => 'expired_date', 'changed' => '4.7.20', 'jira' => 'CRM-9683'),
      array('table' => 'civicrm_job', 'column' => 'last_run', 'changed' => '4.7.20', 'jira' => 'CRM-9683'),
      array('table' => 'civicrm_mailing_event_bounce', 'column' => 'time_stamp', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683'),
      array('table' => 'civicrm_mailing_event_confirm', 'column' => 'time_stamp', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683'),
      array('table' => 'civicrm_mailing_event_delivered', 'column' => 'time_stamp', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683'),
      array('table' => 'civicrm_mailing_event_forward', 'column' => 'time_stamp', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683'),
      array('table' => 'civicrm_mailing_event_opened', 'column' => 'time_stamp', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683'),
      array('table' => 'civicrm_mailing_event_reply', 'column' => 'time_stamp', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683'),
      array('table' => 'civicrm_mailing_event_subscribe', 'column' => 'time_stamp', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683'),
      array('table' => 'civicrm_mailing_event_trackable_url_open', 'column' => 'time_stamp', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683'),
      array('table' => 'civicrm_mailing_event_unsubscribe', 'column' => 'time_stamp', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683'),
      array('table' => 'civicrm_mailing', 'column' => 'created_date', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683'),
      array('table' => 'civicrm_mailing', 'column' => 'scheduled_date', 'changed' => '4.7.20', 'jira' => 'CRM-9683'),
      array('table' => 'civicrm_mailing', 'column' => 'approval_date', 'changed' => '4.7.20', 'jira' => 'CRM-9683'),
      array('table' => 'civicrm_mailing_abtest', 'column' => 'created_date', 'changed' => '4.7.20', 'default' => 'CURRENT_TIMESTAMP', 'jira' => 'CRM-9683'),
      array('table' => 'civicrm_mailing_job', 'column' => 'scheduled_date', 'changed' => '4.7.20', 'jira' => 'CRM-9683'),
      array('table' => 'civicrm_mailing_job', 'column' => 'start_date', 'changed' => '4.7.20', 'jira' => 'CRM-9683'),
      array('table' => 'civicrm_mailing_job', 'column' => 'end_date', 'changed' => '4.7.20', 'jira' => 'CRM-9683'),
      array('table' => 'civicrm_mailing_spool', 'column' => 'added_at', 'changed' => '4.7.20', 'jira' => 'CRM-9683'),
      array('table' => 'civicrm_mailing_spool', 'column' => 'removed_at', 'changed' => '4.7.20', 'jira' => 'CRM-9683'),
    );
  }

}
