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
   *
   * @throws \CRM_Core_Exception
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
   * @throws \CRM_Core_Exception
   */
  public static function convertMappingFieldLabelsToNames(): bool {
    // Contribution fields....
    $mappings = MappingField::get(FALSE)
      ->setSelect(['id', 'name'])
      ->addWhere('mapping_id.mapping_type_id:name', '=', 'Import Contribution')
      ->execute();
    $fields = self::importableContributionFields('All');
    $fieldMap = [];
    foreach ($fields as $fieldName => $field) {
      $fieldMap[$field['title']] = $fieldName;
      if (!empty($field['html']['label'])) {
        $fieldMap[$field['html']['label']] = $fieldName;
      }
    }
    $fieldMap[ts('Soft Credit')] = 'soft_credit';
    $fieldMap[ts('Pledge Payment')] = 'pledge_payment';
    $fieldMap[ts('Pledge ID')] = 'pledge_id';
    $fieldMap[ts('Financial Type')] = 'financial_type_id';
    $fieldMap[ts('Payment Method')] = 'payment_instrument_id';
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
    $fields = self::getImportableMembershipFields('All');
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

    $fields = self::getImportableParticipantFields('All', FALSE);
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
    $fields = array_merge(self::getImportableActivityFields(),
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
   * Combine all the importable fields from the lower levels object.
   *
   * @return array
   *   array of importable Fields
   */
  protected static function getImportableParticipantFields(): array {
    $fields = ['' => ['title' => ts('- do not import -')]];
    $tmpFields = CRM_Event_DAO_Participant::import();

    $note = [
      'participant_note' => [
        'title' => ts('Participant Note'),
        'name' => 'participant_note',
        'headerPattern' => '/(participant.)?note$/i',
        'data_type' => CRM_Utils_Type::T_TEXT,
      ],
    ];

    // Split status and status id into 2 fields
    // Fixme: it would be better to leave as 1 field and intelligently handle both during import
    // note import undoes this - it is still here in case the search usage uses it.
    $participantStatus = [
      'participant_status' => [
        'title' => ts('Participant Status'),
        'name' => 'participant_status',
        'data_type' => CRM_Utils_Type::T_STRING,
      ],
    ];
    $tmpFields['participant_status_id']['title'] = ts('Participant Status Id');

    // Split role and role id into 2 fields
    // Fixme: it would be better to leave as 1 field and intelligently handle both during import
    // note import undoes this - it is still here in case the search usage uses it.
    $participantRole = [
      'participant_role' => [
        'title' => ts('Participant Role'),
        'name' => 'participant_role',
        'data_type' => CRM_Utils_Type::T_STRING,
      ],
    ];
    $tmpFields['participant_role_id']['title'] = ts('Participant Role Id');

    $eventType = [
      'event_type' => [
        'title' => ts('Event Type'),
        'name' => 'event_type',
        'data_type' => CRM_Utils_Type::T_STRING,
      ],
    ];

    $tmpContactField = [];
    $contactFields = CRM_Contact_BAO_Contact::importableFields('All', NULL);

    // Using new Dedupe rule.
    $ruleParams = [
      'contact_type' => 'All',
      'used' => 'Unsupervised',
    ];
    $fieldsArray = CRM_Dedupe_BAO_DedupeRule::dedupeRuleFields($ruleParams);

    if (is_array($fieldsArray)) {
      foreach ($fieldsArray as $value) {
        $customFieldId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
          $value,
          'id',
          'column_name'
        );
        $value = $customFieldId ? 'custom_' . $customFieldId : $value;
        $tmpContactField[trim($value)] = $contactFields[trim($value)] ?? NULL;
        $title = $tmpContactField[trim($value)]['title'] . ' (match to contact)';

        $tmpContactField[trim($value)]['title'] = $title;
      }
    }
    $extIdentifier = $contactFields['external_identifier'] ?? NULL;
    if ($extIdentifier) {
      $tmpContactField['external_identifier'] = $extIdentifier;
      $tmpContactField['external_identifier']['title'] = ($extIdentifier['title'] ?? '') . ' (match to contact)';
    }
    $tmpFields['participant_contact_id']['title'] = $tmpFields['participant_contact_id']['title'] . ' (match to contact)';

    $fields = array_merge($fields, $tmpContactField);
    $fields = array_merge($fields, $tmpFields);
    $fields = array_merge($fields, $note, $participantStatus, $participantRole, $eventType);
    $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Participant', FALSE, FALSE, FALSE, FALSE));

    return $fields;
  }

  /**
   * @param string $contactType
   *
   * @return array|mixed
   * @throws \CRM_Core_Exception
   */
  protected static function getImportableMembershipFields($contactType = 'Individual') {
    $fields = Civi::cache('fields')->get('upgrade_membership_importable_fields' . $contactType);
    if (!$fields) {
      $fields = ['' => ['title' => '- ' . ts('do not import') . ' -']];

      $tmpFields = CRM_Member_DAO_Membership::import();
      $contactFields = CRM_Contact_BAO_Contact::importableFields($contactType, NULL);

      // Using new Dedupe rule.
      $ruleParams = [
        'contact_type' => $contactType,
        'used' => 'Unsupervised',
      ];
      $fieldsArray = CRM_Dedupe_BAO_DedupeRule::dedupeRuleFields($ruleParams);

      $tmpContactField = [];
      if (is_array($fieldsArray)) {
        foreach ($fieldsArray as $value) {
          $customFieldId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
            $value,
            'id',
            'column_name'
          );
          $value = trim($customFieldId ? 'custom_' . $customFieldId : $value);
          $tmpContactField[trim($value)] = $contactFields[$value] ?? NULL;
          $title = $tmpContactField[trim($value)]['title'] . ' ' . ts('(match to contact)');
          $tmpContactField[trim($value)]['title'] = $title;
        }
      }
      $tmpContactField['external_identifier'] = $contactFields['external_identifier'];
      $tmpContactField['external_identifier']['title'] = $contactFields['external_identifier']['title'] . ' ' . ts('(match to contact)');

      $tmpFields['membership_contact_id']['title'] .= ' ' . ts('(match to contact)');

      $fields = array_merge($fields, $tmpContactField);
      $fields = array_merge($fields, $tmpFields);
      $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Membership'));
      Civi::cache('fields')->set('upgrade_membership_importable_fields' . $contactType, $fields);
    }
    return $fields;
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
    if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_user_job', 'type_id')) {
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
    }
    return TRUE;
  }

  /**
   * Combine all the importable fields from the lower levels object.
   *
   * The ordering is important, since currently we do not have a weight
   * scheme. Adding weight is super important and should be done in the
   * next week or so, before this can be called complete.
   *
   * @return array
   *   array of importable Fields
   */
  private static function getImportableActivityFields(): array {
    if (empty(Civi::$statics[__CLASS__][__FUNCTION__])) {
      Civi::$statics[__CLASS__][__FUNCTION__] = [];
      $fields = ['' => ['title' => ts('- do not import -')]];

      $tmpFields = CRM_Activity_DAO_Activity::import();
      $contactFields = CRM_Contact_BAO_Contact::importableFields('Individual', NULL);

      // Using new Dedupe rule.
      $ruleParams = [
        'contact_type' => 'Individual',
        'used' => 'Unsupervised',
      ];
      $fieldsArray = CRM_Dedupe_BAO_DedupeRule::dedupeRuleFields($ruleParams);

      $tmpConatctField = [];
      if (is_array($fieldsArray)) {
        foreach ($fieldsArray as $value) {
          $customFieldId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
            $value,
            'id',
            'column_name'
          );
          $value = $customFieldId ? 'custom_' . $customFieldId : $value;
          $tmpConatctField[trim($value)] = $contactFields[trim($value)];
          $tmpConatctField[trim($value)]['title'] = $tmpConatctField[trim($value)]['title'] . " (match to contact)";
        }
      }
      $tmpConatctField['external_identifier'] = $contactFields['external_identifier'];
      $tmpConatctField['external_identifier']['title'] = $contactFields['external_identifier']['title'] . " (match to contact)";
      $fields = array_merge($fields, $tmpConatctField);
      $fields = array_merge($fields, $tmpFields);
      $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Activity'));
      Civi::$statics[__CLASS__][__FUNCTION__] = $fields;
    }
    return Civi::$statics[__CLASS__][__FUNCTION__];
  }

  /**
   * Historical copy of Contribution importable fields function.
   *
   * @param string $contactType
   *
   * @return array
   *   array of importable Fields
   */
  private static function importableContributionFields($contactType = 'Individual'): array {
    $fields = ['' => ['title' => ts('- do not import -')]];
    $note = CRM_Core_DAO_Note::import();
    $tmpFields = CRM_Contribute_DAO_Contribution::import();
    unset($tmpFields['option_value']);
    $contactFields = CRM_Contact_BAO_Contact::importableFields($contactType, NULL);

    // Using new Dedupe rule.
    $ruleParams = [
      'contact_type' => $contactType,
      'used' => 'Unsupervised',
    ];
    $fieldsArray = CRM_Dedupe_BAO_DedupeRule::dedupeRuleFields($ruleParams);
    $tmpContactField = [];
    if (is_array($fieldsArray)) {
      foreach ($fieldsArray as $value) {
        //skip if there is no dupe rule
        if ($value === 'none') {
          continue;
        }
        $customFieldId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
          $value,
          'id',
          'column_name'
        );
        $value = trim($customFieldId ? 'custom_' . $customFieldId : $value);
        $tmpContactField[$value] = $contactFields[$value];
        $title = $tmpContactField[$value]['title'] . ' ' . ts('(match to contact)');

        $tmpContactField[$value]['title'] = $title;
      }
    }

    $tmpContactField['external_identifier'] = $contactFields['external_identifier'];
    $tmpContactField['external_identifier']['title'] = $contactFields['external_identifier']['title'] . ' ' . ts('(match to contact)');
    $tmpFields['contribution_contact_id']['title'] = $tmpFields['contribution_contact_id']['html']['label'] = $tmpFields['contribution_contact_id']['title'] . ' ' . ts('(match to contact)');
    $fields = array_merge($fields, $tmpContactField);
    $fields = array_merge($fields, $tmpFields);
    $fields = array_merge($fields, $note);
    $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Contribution'));
    return $fields;
  }

}
