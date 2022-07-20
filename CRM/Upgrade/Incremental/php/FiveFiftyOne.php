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

use Civi\Api4\MappingField;

/**
 * Upgrade logic for the 5.51.x series.
 *
 * Each minor version in the series is handled by either a `5.51.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_51_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFiftyOne extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_51_alpha1($rev): void {
    $this->addSnapshotTask('mappings', CRM_Utils_SQL_Select::from('civicrm_mapping'));
    $this->addSnapshotTask('fields', CRM_Utils_SQL_Select::from('civicrm_mapping_field'));
    $this->addSnapshotTask('queues', CRM_Utils_SQL_Select::from('civicrm_queue'));

    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask(ts('Convert import mappings to use names'), 'convertMappingFieldLabelsToNames', $rev);
    $this->addTask('Add column "civicrm_queue.status"', 'addColumn', 'civicrm_queue',
      'status', "varchar(16) NULL DEFAULT 'active' COMMENT 'Execution status'");
    $this->addTask('Add column "civicrm_queue.error"', 'addColumn', 'civicrm_queue',
      'error', "varchar(16) NULL COMMENT 'Fallback behavior for unhandled errors'");
    $this->addTask('Add column "civicrm_queue.is_template"', 'addColumn', 'civicrm_queue',
      'is_template', "tinyint NOT NULL DEFAULT 0 COMMENT 'Is this a template configuration (for use by other/future queues)?'");
    $this->addTask('Add column "civicrm_user_job.is_template"', 'addColumn', 'civicrm_user_job',
      'is_template', "tinyint NOT NULL DEFAULT 0 COMMENT 'Is this a template configuration (for use by other/future jobs)?'");
    $this->addTask('Backfill "civicrm_queue.status" and "civicrm_queue.error")', 'fillQueueColumns');
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_51_beta2($rev): void {
    $this->addTask('Convert UserJob table type_id to job_type', 'updateUserJobTable');
  }

  public static function fillQueueColumns($ctx): bool {
    // Generally, anything we do here is nonsensical because there shouldn't be much real world data,
    // and the goal is to require something specific going forward (for anything that has an automatic runner).
    // But this ensures that satisfy the invariant.
    //
    // What default value of "error" should apply to pre-existing queues (if they somehow exist)?
    // Go back to our heuristic "short-term/finite queue <=> abort" vs "long-term/infinite queue <=> log".
    // We don't have adequate data to differentiate these, so some will be wrong/suboptimal.
    // What's the impact of getting it wrong?
    // - For a finite/short-term queue, work has finished already (or will finish soon), so there is
    //   very limited impact to wrongly setting `error=delete`.
    // - For an infinite/long-term queue, work will continue indefinitely into the future. The impact
    //   of wrongly setting `error=abort` would continue indefinitely to the future.
    // Therefore, backfilling `error=log` is less-problematic than backfilling `error=abort`.
    CRM_Core_DAO::executeQuery('UPDATE civicrm_queue SET error = "delete" WHERE runner IS NOT NULL AND error IS NULL');
    CRM_Core_DAO::executeQuery('UPDATE civicrm_queue SET status = IF(runner IS NULL, NULL, "active")');
    return TRUE;
  }

  /**
   * Convert saved mapping fields for contribution imports to use name rather than
   * label.
   *
   * Currently the 'name' column in civicrm_mapping_field holds names like
   * 'First Name' or, more tragically 'Contact ID (match to contact)'.
   *
   * This updates them to hold the name - eg. 'total_amount'.
   *
   * @return bool
   * @throws \API_Exception
   */
  public static function convertMappingFieldLabelsToNames(): bool {
    // Contribution fields....
    $mappings = MappingField::get(FALSE)
      ->setSelect(['id', 'name'])
      ->addWhere('mapping_id.mapping_type_id:name', '=', 'Import Contribution')
      ->execute();
    $fields = CRM_Contribute_BAO_Contribution::importableFields('All', FALSE);
    $fieldMap = [];
    foreach ($fields as $fieldName => $field) {
      $fieldMap[$field['title']] = $fieldName;
      if (!empty($field['html']['label'])) {
        $fieldMap[$field['html']['label']] = $fieldName;
      }
    }
    $fieldMap[ts('Soft Credit')] = 'soft_credit';
    $fieldMap[ts('Pledge Payment')] = 'pledge_payment';
    $fieldMap[ts(ts('Pledge ID'))] = 'pledge_id';
    $fieldMap[ts(ts('Financial Type'))] = 'financial_type_id';
    $fieldMap[ts(ts('Payment Method'))] = 'payment_instrument_id';
    $fieldMap[ts('- do not import -')] = 'do_not_import';

    // Membership fields
    foreach ($mappings as $mapping) {
      if (!empty($fieldMap[$mapping['name']])) {
        MappingField::update(FALSE)
          ->addWhere('id', '=', $mapping['id'])
          ->addValue('name', $fieldMap[$mapping['name']])
          ->execute();
      }
    }

    // Membership fields...
    // Yes - I know they could be combined - but it's also less confusing this way.
    $mappings = MappingField::get(FALSE)
      ->setSelect(['id', 'name'])
      ->addWhere('mapping_id.mapping_type_id:name', '=', 'Import Membership')
      ->execute();
    $fields = CRM_Member_BAO_Membership::importableFields('All', FALSE);;
    $fieldMap = [];
    foreach ($fields as $fieldName => $field) {
      $fieldMap[$field['title']] = $fieldName;
      if (!empty($field['html']['label'])) {
        $fieldMap[$field['html']['label']] = $fieldName;
      }
    }
    $fieldMap[ts('- do not import -')] = 'do_not_import';

    foreach ($mappings as $mapping) {
      if (!empty($fieldMap[$mapping['name']])) {
        MappingField::update(FALSE)
          ->addWhere('id', '=', $mapping['id'])
          ->addValue('name', $fieldMap[$mapping['name']])
          ->execute();
      }
    }

    // Participant fields...
    // Yes - I know they could be combined - but it's also less confusing this way.
    $mappings = MappingField::get(FALSE)
      ->setSelect(['id', 'name'])
      ->addWhere('mapping_id.mapping_type_id:name', '=', 'Import Participant')
      ->execute();

    $fields = CRM_Event_BAO_Participant::importableFields('All', FALSE);
    $fields['event_id']['title'] = 'Event ID';
    $eventfields = CRM_Event_BAO_Event::fields();
    $fields['event_title'] = $eventfields['event_title'];

    $fieldMap = [];
    foreach ($fields as $fieldName => $field) {
      $fieldMap[$field['title']] = $field['name'] ?? $fieldName;
      if (!empty($field['html']['label'])) {
        $fieldMap[$field['html']['label']] = $field['name'] ?? $fieldName;
      }
    }
    $fieldMap[ts('- do not import -')] = 'do_not_import';
    $fieldMap[ts('Participant Status')] = 'status_id';
    $fieldMap[ts('Participant Role')] = 'role_id';
    $fieldMap[ts('Event Title')] = 'event_id';

    foreach ($mappings as $mapping) {
      if (!empty($fieldMap[$mapping['name']])) {
        MappingField::update(FALSE)
          ->addWhere('id', '=', $mapping['id'])
          ->addValue('name', $fieldMap[$mapping['name']])
          ->execute();
      }
    }

    // Activity fields...
    // Yes - I know they could be combined - but it's also less confusing this way.
    $mappings = MappingField::get(FALSE)
      ->setSelect(['id', 'name'])
      ->addWhere('mapping_id.mapping_type_id:name', '=', 'Import Activity')
      ->execute();

    $activityContact = CRM_Activity_BAO_ActivityContact::import();
    $activityTarget['target_contact_id'] = $activityContact['contact_id'];
    $fields = array_merge(CRM_Activity_BAO_Activity::importableFields(),
      $activityTarget
    );

    $fields = array_merge($fields, [
      'source_contact_id' => [
        'title' => ts('Source Contact'),
        'headerPattern' => '/Source.Contact?/i',
      ],
    ]);

    $fieldMap = [];
    foreach ($fields as $fieldName => $field) {
      $fieldMap[$field['title']] = $fieldName;
      if (!empty($field['html']['label'])) {
        $fieldMap[$field['html']['label']] = $fieldName;
      }
    }
    $fieldMap[ts('- do not import -')] = 'do_not_import';
    $fieldMap[ts('Activity Type Label')] = 'activity_type_id';

    foreach ($mappings as $mapping) {
      if (!empty($fieldMap[$mapping['name']])) {
        MappingField::update(FALSE)
          ->addWhere('id', '=', $mapping['id'])
          ->addValue('name', $fieldMap[$mapping['name']])
          ->execute();
      }
    }

    // Multiple custom
    $mappings = MappingField::get(FALSE)
      ->setSelect(['id', 'name'])
      ->addWhere('mapping_id.mapping_type_id:name', '=', 'Import Multi value custom data')
      ->execute();
    $allFields = civicrm_api3('custom_field', 'get', ['custom_group_id.is_multiple' => TRUE, 'return' => ['label', 'custom_group_id.title']])['values'];
    $fieldMap = [];
    foreach ($allFields as $field) {
      $label = $field['label'] . ' :: ' . $field['custom_group_id.title'];
      $fieldMap[$label] = 'custom_' . $field['id'];
    }

    $fieldMap[ts('- do not import -')] = 'do_not_import';
    $fieldMap[ts('Contact ID')] = 'contact_id';
    $fieldMap[ts('External Identifier')] = 'external_identifier';
    foreach ($mappings as $mapping) {
      if (!empty($fieldMap[$mapping['name']])) {
        MappingField::update(FALSE)
          ->addWhere('id', '=', $mapping['id'])
          ->addValue('name', $fieldMap[$mapping['name']])
          ->execute();
      }
    }

    return TRUE;
  }

  /**
   * Update user job table to use a text job_type not an integer type_id.
   *
   * This makes it easier for non-core classes to register types as
   * sequential is not required.
   *
   * @param $context
   *
   * @return bool
   */
  public static function updateUserJobTable($context): bool {
    self::addColumn($context, 'civicrm_user_job', 'job_type', 'varchar(64) NOT NULL');
    // This is really only for rc-upgraders. There has been no stable with type_id.
    CRM_Core_DAO::executeQuery(
      "UPDATE civicrm_user_job SET job_type =
      CASE
        WHEN type_id = 1 THEN 'contact_import'
        WHEN type_id = 2 THEN 'contribution_import'
        WHEN type_id = 3 THEN 'membership_import'
        WHEN type_id = 4 THEN 'activity_import'
        WHEN type_id = 5 THEN 'participant_import'
        WHEN type_id = 6 THEN 'custom_field_import'
      END
      "
    );
    self::dropColumn($context, 'civicrm_user_job', 'type_id');
    return TRUE;
  }

}
