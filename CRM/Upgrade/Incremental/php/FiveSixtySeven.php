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
 * Upgrade logic for the 5.67.x series.
 *
 * Each minor version in the series is handled by either a `5.67.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_67_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSixtySeven extends CRM_Upgrade_Incremental_Base {

  const MAILING_BATCH_SIZE = 500000;

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    parent::setPreUpgradeMessage($preUpgradeMessage, $rev, $currentVer);
    if ($rev === '5.67.alpha1') {
      $customPrivacy = CRM_Core_DAO::executeQuery('
        SELECT value, label
        FROM civicrm_option_value
        WHERE is_active = 1 AND value NOT IN ("0", "1")
          AND option_group_id = (SELECT id FROM civicrm_option_group WHERE name = "note_privacy")')
        ->fetchMap('value', 'label');
      if ($customPrivacy) {
        $preUpgradeMessage .= '<p>'
          . ts('This site has custom note privacy options (%1) which may not work correctly after the upgrade, due to the deprecation of hook_civicrm_notePrivacy. If you are using this hook, see <a %2>developer documentation on updating your code</a>.', [1 => '"' . implode('", "', $customPrivacy) . '"', 2 => 'href="https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_notePrivacy/" target="_blank"']) .
          '</p>';
      }
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_67_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Make Note.privacy required', 'alterColumn', 'civicrm_note', 'privacy', "varchar(255) NOT NULL DEFAULT 0 COMMENT 'Foreign Key to Note Privacy Level (which is an option value pair and hence an implicit FK)'");
    $this->addTask('Make EntityFile.entity_table required', 'alterColumn', 'civicrm_entity_file', 'entity_table', "varchar(64) NOT NULL COMMENT 'physical tablename for entity being joined to file, e.g. civicrm_contact'");
    $this->addExtensionTask('Enable Authx extension', ['authx'], 1101);
    $this->addExtensionTask('Enable Afform extension', ['org.civicrm.afform'], 1102);
    $this->addTask('Add "civicrm_note" to "note_used_for" option group', 'addNoteNote');
    $this->addTask('Add cache_fill_took column to Group table', 'addColumn', 'civicrm_group', 'cache_fill_took',
      'DOUBLE DEFAULT NULL COMMENT "Seconds taken to fill smart group cache, not always related to cache_date"',
      FALSE);
    $this->addTask('Update civicrm_mailing_event_queue to permit deleting records from civicrm_mailing_job', 'updateMailingEventQueueTable');
    $this->addTask('Update CiviMail menus labels', 'updateMailingMenuLabels');
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_67_beta2($rev): void {
    // Repeat step from 5.66 because it was added late in the release-cycle
    $this->addTask('Make ActionSchedule.name required', 'alterColumn', 'civicrm_action_schedule', 'name', "varchar(128) NOT NULL COMMENT 'Name of the scheduled action'");
    $this->addTask('Make Discount.entity_table required', 'alterColumn', 'civicrm_discount', 'entity_table', "varchar(64) NOT NULL COMMENT 'physical tablename for entity being joined to discount, e.g. civicrm_event'");
  }

  /**
   * Some time ago, the labels for Mailing menu items were simplified for new
   * installs. Now that the old strings have been removed from Transifex, it
   * breaks translations, so we force the update, but only if the label was not
   * customized (if name=label).
   */
  public static function updateMailingMenuLabels(CRM_Queue_TaskContext $ctx): bool {
    $changes = [
      'Draft and Unscheduled Mailings' => 'Draft Mailings',
      'Scheduled and Sent Mailings' => 'Sent Mailings',
    ];
    foreach ($changes as $old => $new) {
      CRM_Core_DAO::executeQuery('UPDATE civicrm_navigation SET label = %1 WHERE name = %2 AND label = %3', [
        1 => [$new, 'String'],
        2 => [$old, 'String'],
        3 => [$old, 'String'],
      ]);
    }
    return TRUE;
  }

  /**
   * We want to add 2 columns & fix one index. This function allows us to
   * do it in less sql statements (given they might be slow).
   */
  public static function updateMailingEventQueueTable(CRM_Queue_TaskContext $ctx): bool {
    $sql = ["MODIFY job_id int unsigned null comment 'Mailing Job'"];
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_mailing_event_queue', 'mailing_id')) {
      $sql[] = "ADD COLUMN mailing_id int(10) unsigned DEFAULT NULL COMMENT 'Related mailing. Used for reporting on mailing success, if present.'";
      $sql[] = 'ADD CONSTRAINT FOREIGN KEY (`mailing_id`) REFERENCES `civicrm_mailing` (`id`) ON DELETE SET NULL';
    }
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_mailing_event_queue', 'is_test')) {
      $sql[] = 'ADD COLUMN is_test tinyint(4) NOT NULL DEFAULT 0';
    }
    if (CRM_Core_BAO_SchemaHandler::checkFKExists('civicrm_mailing_event_queue', 'FK_civicrm_mailing_event_queue_job_id')) {
      $sql[] = 'DROP FOREIGN KEY FK_civicrm_mailing_event_queue_job_id';
    }
    if (empty($sql)) {
      // If someone pre-upgraded to better manage a potentially slow query.
      return TRUE;
    }
    try {
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_mailing_event_queue ' . implode(', ', $sql), [], FALSE, FALSE, FALSE, FALSE);
      CRM_Core_DAO::executeQuery('
      ALTER TABLE `civicrm_mailing_event_queue`
      ADD CONSTRAINT `FK_civicrm_mailing_event_queue_job_id`
      FOREIGN KEY (`job_id`)
      REFERENCES `civicrm_mailing_job`(`id`) ON DELETE SET NULL
    ', [], FALSE, FALSE, FALSE, FALSE);

      [$minId, $maxId] = CRM_Core_DAO::executeQuery("SELECT coalesce(min(id),0), coalesce(max(id),0)
      FROM civicrm_mailing_event_queue ")->getDatabaseResult()->fetchRow();
      for ($startId = $minId; $startId <= $maxId; $startId += self::MAILING_BATCH_SIZE) {
        $endId = min($maxId, $startId + self::MAILING_BATCH_SIZE - 1);
        $task = new CRM_Queue_Task([static::class, 'fillMailingEvents'],
          [$startId, $endId],
          sprintf('Backfill civicrm_mailing_event_queue (%d => %d)', $startId, $endId));
        $ctx->queue->createItem($task, ['weight' => -1]);
      }
    }
    catch (\Civi\Core\Exception\DBQueryException $e) {
      throw new CRM_Core_Exception(
        'db error message' . $e->getDBErrorMessage()
        . 'message ' . $e->getMessage()
        . "\n"
        . 'sql ' . $e->getSQL()
        . "\n"
        . 'user info ' . $e->getUserInfo()
        . "\n"
        . 'debug info ' . $e->getCause()
        . "\n"
        . 'debug info ' . $e->getDebugInfo()
        . "\n");
    }
    return TRUE;
  }

  public static function fillMailingEvents(CRM_Queue_TaskContext $ctx, int $startId, int $endId): bool {
    CRM_Core_DAO::executeQuery('
UPDATE  civicrm_mailing_event_queue q
INNER JOIN civicrm_mailing_job job ON job.id = q.job_id
SET q.mailing_id = job.mailing_id, q.is_test=job.is_test
WHERE q.id >= %1 AND q.id <= %2 AND q.mailing_id IS NULL',
      [
        1 => [$startId, 'Int'],
        2 => [$endId, 'Int'],
      ]
    );
    return TRUE;
  }

  public static function addNoteNote(CRM_Queue_TaskContext $ctx): bool {
    CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => 'note_used_for',
      'label' => ts('Notes'),
      'name' => 'Note',
      'value' => 'civicrm_note',
    ]);
    return TRUE;
  }

}
