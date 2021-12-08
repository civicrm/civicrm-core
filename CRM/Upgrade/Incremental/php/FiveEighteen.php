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
 * Upgrade logic for FiveEighteen
 */
class CRM_Upgrade_Incremental_php_FiveEighteen extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_18_alpha1($rev) {
    // Not used // $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('Update smart groups to reflect change of unique name for is_override', 'updateSmartGroups', [
      'renameField' => [
        ['old' => 'is_override', 'new' => 'member_is_override'],
      ],
    ]);
    $this->addTask('Remove Foreign Key from civicrm_dashboard on domain_id if exists', 'removeDomainIDFK');
    $this->addTask('Remove Index on domain_id that might have been randomly added in the same format as FK', 'dropIndex', 'civicrm_dashboard', 'FK_civicrm_dashboard_domain_id');
    $this->addTask('Re-Create Foreign key between civicrm_dashboard and civicrm_domain correctly', 'recreateDashboardFK');
    $this->addTask('Update smart groups to rename filters on pledge_payment_date to pledge_payment_scheduled_date', 'updateSmartGroups', [
      'renameField' => [
        ['old' => 'pledge_payment_date_relative', 'new' => 'pledge_payment_scheduled_date_relative'],
        ['old' => 'pledge_payment_date_high', 'new' => 'pledge_payment_scheduled_date_high'],
        ['old' => 'pledge_payment_date_low', 'new' => 'pledge_payment_scheduled_date_low'],
        ['old' => 'member_join_date_relative', 'new' => 'membership_join_date_relative'],
        ['old' => 'member_join_date_high', 'new' => 'membership_join_date_high'],
        ['old' => 'member_join_date_low', 'new' => 'membership_join_date_low'],
        ['old' => 'member_start_date_relative', 'new' => 'membership_start_date_relative'],
        ['old' => 'member_start_date_high', 'new' => 'membership_start_date_high'],
        ['old' => 'member_start_date_low', 'new' => 'membership_start_date_low'],
        ['old' => 'member_end_date_relative', 'new' => 'membership_end_date_relative'],
        ['old' => 'member_end_date_high', 'new' => 'membership_end_date_high'],
        ['old' => 'member_end_date_low', 'new' => 'membership_end_date_low'],
      ],
    ]);
    $this->addTask('Update smart groups where jcalendar fields have been converted to datepicker', 'updateSmartGroups', [
      'datepickerConversion' => [
        'pledge_payment_scheduled_date',
        'pledge_create_date',
        'pledge_end_date',
        'pledge_start_date',
        'membership_join_date',
        'membership_end_date',
        'membership_start_date',
      ],
    ]);
    $this->addTask('Update civicrm_mapping_field and civicrm_uf_field for change in join_date name', 'updateJoinDateMappingUF');
    $this->addTask('Update civicrm_report_instances for change in filter from join_date to membership_join_date', 'joinDateReportUpdate');
  }

  public static function removeDomainIDFK() {
    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_dashboard', 'FK_civicrm_dashboard_domain_id');
    return TRUE;
  }

  public static function recreateDashboardFK() {
    $sql = CRM_Core_BAO_SchemaHandler::buildForeignKeySQL([
      'fk_table_name' => 'civicrm_domain',
      'fk_field_name' => 'id',
      'name' => 'domain_id',
      'fk_attributes' => ' ON DELETE CASCADE',
    ], "\n", " ADD ", 'civicrm_dashboard');
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_dashboard " . $sql, [], TRUE, NULL, FALSE, FALSE);
    return TRUE;
  }

  public static function updateJoinDateMappingUF() {
    CRM_Core_DAO::executeQuery("UPDATE civicrm_mapping_field SET name = 'membership_join_date' WHERE name = 'join_date' AND contact_type = 'Membership'");
    CRM_Core_DAO::executeQuery("UPDATE civicrm_uf_field SET field_name = 'membership_join_date' WHERE field_name = 'join_date' AND field_type = 'Membership'");
    return TRUE;
  }

  public static function joinDateReportUpdate() {
    $report_templates = ['member/contributionDetail', 'member/Detail', 'member/Summary'];
    $substitutions = [
      'join_date_relative' => 'membership_join_date_relative',
      'join_date_from' => 'membership_join_date_from',
      'join_date_to' => 'membership_join_date_to',
    ];
    foreach ($report_templates as $report_template) {
      $reports = civicrm_api3('ReportInstance', 'get', [
        'report_id' => $report_template,
        'options' => ['limit' => 0],
      ])['values'];
      foreach ($reports as $report) {
        if (!is_array($report['form_values'])) {
          $form_values = unserialize($report['form_values']);
        }
        else {
          $form_values = $report['form_values'];
        }
        foreach ($form_values as $key => $value) {
          if (array_key_exists($key, $substitutions)) {
            $form_values[$substitutions[$key]] = $value;
            unset($form_values[$key]);
          }
        }
        $form_values = serialize($form_values);
        CRM_Core_DAO::executeQuery("UPDATE civicrm_report_instance SET form_values = %1 WHERE id = %2", [
          1 => [$form_values, 'String'],
          2 => [$report['id'], 'Positive'],
        ]);
      }
    }
    return TRUE;
  }

}
