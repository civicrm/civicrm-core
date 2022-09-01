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
 * Upgrade logic for the 5.54.x series.
 *
 * Each minor version in the series is handled by either a `5.54.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_54_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFiftyFour extends CRM_Upgrade_Incremental_Base {

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    parent::setPreUpgradeMessage($preUpgradeMessage, $rev, $currentVer);
    if ($rev === '5.54.alpha1') {
      if (\Civi::settings()->get('civicaseActivityRevisions')) {
        $preUpgradeMessage .= '<p>' . ts('The setting that used to be at <em>Administer &gt; CiviCase &gt; CiviCase Settings</em> for <strong>Enable deprecated Embedded Activity Revisions</strong> is enabled, but is no longer functional.<ul><li>For more information see this <a %1>Lab Snippet</a>.</li></ul>', [1 => 'target="_blank" href="https://lab.civicrm.org/-/snippets/85"']) . '</p>';
      }
      $preUpgradeMessage .= ($this->renderQueueMessage() ?: '');
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_54_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add "created_id" column to "civicrm_participant"', 'addCreatedIDColumnToParticipant');
    $this->addTask('Convert timestamps in civicrm_queue_item', 'updateQueueTimestamps');
    $this->addTask('Increase field length of civicrm_dedupe_rule_group.name', 'alterDedupeRuleGroupName');
    $this->addTask('Add index civicrm_dedupe_rule_group.UI_name', 'addIndex', 'civicrm_dedupe_rule_group', 'name', 'UI');
    $this->addTask('Install Elavon Payment Processor Extension as needed', 'installElavonPaymentProcessorExtension');
    $this->addTask('Convert field names for contribution import saved mappings', 'updateContributionMappings');
  }

  public static function addCreatedIDColumnToParticipant($ctx): bool {
    CRM_Upgrade_Incremental_Base::addColumn($ctx, 'civicrm_participant', 'created_id', 'int(10) UNSIGNED DEFAULT NULL COMMENT "Created by Contact ID"');
    if (!CRM_Core_BAO_SchemaHandler::checkFKExists('civicrm_participant', 'FK_civicrm_participant_created_id')) {
      CRM_Core_DAO::executeQuery('ALTER TABLE `civicrm_participant` ADD CONSTRAINT `FK_civicrm_participant_created_id` FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL;');
    }
    return TRUE;
  }

  /**
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function alterDedupeRuleGroupName(CRM_Queue_TaskContext $ctx) {
    CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_dedupe_rule_group` CHANGE COLUMN `name` `name` varchar(255) COMMENT 'Unique name of rule group'", [], TRUE, NULL, FALSE, FALSE);
    CRM_Core_DAO::executeQuery("UPDATE `civicrm_dedupe_rule_group` g1, `civicrm_dedupe_rule_group` g2 SET g1.name = CONCAT(g1.name, '_', g1.id) WHERE g1.name = g2.name AND g1.id > g2.id", [], TRUE, NULL, FALSE, FALSE);
    return TRUE;
  }

  /**
   * @param CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function installElavonPaymentProcessorExtension(CRM_Queue_TaskContext $ctx) {
    $paymentProcessors = CRM_Core_DAO::singleValueQuery("SELECT count(cpp.id) FROM civicrm_payment_processor cpp
      INNER JOIN civicrm_payment_processor_type cppt ON cppt.id = cpp.payment_processor_type_id
      WHERE cppt.name = 'Elavon'");
    if ($paymentProcessors >= 1) {
      $paymentProcessorType = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_payment_processor_type WHERE name = 'Elavon'");
      $insert = CRM_Utils_SQL_Insert::into('civicrm_extension')->row([
        'type' => 'module',
        'full_name' => 'elavon',
        'name' => 'Elavon Payment Processor',
        'label' => 'Elavon Payment Processor',
        'file' => 'elavon',
        'schema_version' => NULL,
        'is_active' => 1,
      ]);
      CRM_Core_DAO::executeQuery($insert->usingReplace()->toSQL());
      $mgdInert = CRM_Utils_SQL_Insert::into('civicrm_managed')->row([
        'module' => 'elavon',
        'name' => 'PaymentProcessorType_Elavon',
        'entity_type' => 'PaymentProcessorType',
        'entity_id' => $paymentProcessorType,
        'cleanup' => NULL,
      ]);
      CRM_Core_DAO::executeQuery($mgdInert->usingReplace()->toSQL());
      CRM_Extension_System::singleton()->getManager()->refresh();
    }
    else {
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_payment_processor_type WHERE name = 'Elavon'");
    }
    return TRUE;
  }

  public function renderQueueMessage(): ?string {
    $taskCounts = CRM_Core_DAO::executeQuery('SELECT queue_name, count(*) as count FROM civicrm_queue_item GROUP BY queue_name')
      ->fetchMap('queue_name', 'count');
    unset($taskCounts[CRM_Upgrade_Form::QUEUE_NAME]);
    if (empty($taskCounts)) {
      return NULL;
    }

    $delayedCounts = CRM_Core_DAO::executeQuery('SELECT queue_name, count(*) as count FROM civicrm_queue_item WHERE (release_time IS NOT NULL) GROUP BY queue_name')
      ->fetchMap('queue_name', 'count');

    $status = ts('<strong>Queue Timezone</strong>: The system has queued tasks, and some tasks may be scheduled for future execution. The upgrade will use your personal timezone (<code>%1</code>) to interpret these tasks. If this timezone is incorrect, the task schedule could shift. The system has %3 queue(s) with %2 pending task(s):', [
      1 => htmlentities(CRM_Core_Config::singleton()->userSystem->getTimeZoneOffset()),
      2 => array_sum($taskCounts),
      3 => count($taskCounts),
    ]);

    $listItems = [];
    // $trRows = [];
    foreach ($taskCounts as $queueName => $itemCount) {
      $delayedCount = $delayedCounts[$queueName] ?? 0;
      // $trRows[] = sprintf('<tr><td>%s</td><td>%s</td><td>%s</td></tr>', htmlentities($queueName), $delayedCount, $itemCount - $delayedCount);

      $listItems[] = '<li>' . ts('"<code>%1</code>" has %2 task(s), including %3 time-delayed task(s).', [
        1 => htmlentities($queueName),
        2 => $itemCount,
        3 => $delayedCount,
      ]) . '</li>';
    }

    // $header = sprintf('<tr><th>%s</th><th>%s</th><th>%s</th></tr>', ts('Queue'), ts('Time-Delayed Tasks'), ts('Other Tasks'));
    // return sprintf('<p>%s</p><table><thead>%s</thead><tbody>%s</tbody></table>', $status, $header, implode("\n", $trRows));
    return sprintf('<p>%s</p><ul>%s</ul>', $status, implode("\n", $listItems));
  }

  public static function updateQueueTimestamps(CRM_Queue_TaskContext $ctx): bool {
    // We want to run timestamp conversions in the regular SQL connection, which has @time_zone configured.
    // So this is NOT going through `*.mysql.tpl`.
    CRM_Core_DAO::executeQuery('ALTER TABLE `civicrm_queue_item`
      CHANGE `submit_time` `submit_time` timestamp NOT NULL COMMENT \'date on which this item was submitted to the queue\',
      CHANGE `release_time` `release_time` timestamp NULL DEFAULT NULL COMMENT \'date on which this job becomes available; null if ASAP\'
    ');
    return TRUE;
  }

  /**
   * Update saved mappings for contribution imports to use apiv4 style field names.
   *
   * In time we will do this to the other imports.
   *
   * @return true
   */
  public static function updateContributionMappings(): bool {
    $mappingTypeID = (int) CRM_Core_DAO::singleValueQuery("
      SELECT option_value.value
      FROM civicrm_option_value option_value
        INNER JOIN civicrm_option_group option_group
        ON option_group.id = option_value.option_group_id
        AND option_group.name =  'mapping_type'
      WHERE option_value.name = 'Import Contribution'");

    $mappingFields = CRM_Core_DAO::executeQuery('
      SELECT field.id, field.name FROM civicrm_mapping_field field
        INNER JOIN civicrm_mapping mapping
          ON field.mapping_id = mapping.id
          AND mapping_type_id = ' . $mappingTypeID
    );
    // Only dedupe fields could be stored. Phone number, email, address fields & custom fields
    // is a realistic set. The impact of missing something is pretty minor as saved field mappings
    // are easy to update during import & people normally do a visual check - so hard coding a list
    // feels more future-proof than doing it by code.
    $fieldsToConvert = [
      'email' => 'email_primary.email',
      'phone' => 'phone_primary.phone',
      'street_address' => 'address_primary.street_address',
      'supplemental_address_1' => 'address_primary.supplemental_address_1',
      'supplemental_address_2' => 'address_primary.supplemental_address_2',
      'supplemental_address_3' => 'address_primary.supplemental_address_3',
      'city' => 'address_primary.city',
      'county_id' => 'address_primary.county_id',
      'state_province_id' => 'address_primary.state_province_id',
      'country_id' => 'address_primary.country_id',
    ];
    $customFields = CRM_Core_DAO::executeQuery('
      SELECT custom_field.id, custom_field.name, custom_group.name as custom_group_name
      FROM civicrm_custom_field custom_field INNER JOIN civicrm_custom_group custom_group
      ON custom_field.custom_group_id = custom_group.id
      WHERE extends IN ("Contact", "Individual", "Organization", "Household")
    ');
    while ($customFields->fetch()) {
      $fieldsToConvert['custom_' . $customFields->id] = $customFields->custom_group_name . '.' . $customFields->name;
    }
    while ($mappingFields->fetch()) {
      // Convert the field.
      if (isset($fieldsToConvert[$mappingFields->name])) {
        CRM_Core_DAO::executeQuery(' UPDATE civicrm_mapping_field SET name = %1 WHERE id = %2', [
          1 => [$fieldsToConvert[$mappingFields->name], 'String'],
          2 => [$mappingFields->id, 'Integer'],
        ]);
      }
    }
    return TRUE;
  }

}
