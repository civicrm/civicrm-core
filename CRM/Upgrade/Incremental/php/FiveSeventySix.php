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
 * Upgrade logic for the 5.76.x series.
 *
 * Each minor version in the series is handled by either a `5.76.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_76_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSeventySix extends CRM_Upgrade_Incremental_Base {

  const MAILING_BATCH_SIZE = 500;

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_76_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add start_date to civicrm_mailing table', 'addColumn', 'civicrm_mailing', 'start_date', "timestamp NULL DEFAULT NULL COMMENT 'date on which this mailing was started.'");
    $this->addTask('Add end_date to civicrm_mailing table', 'addColumn', 'civicrm_mailing', 'end_date', "timestamp NULL DEFAULT NULL COMMENT 'date on which this mailing was completed.'");
    $this->addTask('Add status to civicrm_mailing table', 'addColumn', 'civicrm_mailing', 'status', "varchar(32) DEFAULT 'Draft' COMMENT 'The status of this Mailing'");
    $this->addTask('Alter translation to make string non-required', 'alterColumn', 'civicrm_translation', 'string',
      "longtext NULL COMMENT 'Translated string'"
    );
    $this->addTask('Install SiteToken entity', 'createEntityTable', '5.76.alpha1.SiteToken.entityType.php');
    $this->addTask(ts('Create index %1', [1 => 'civicrm_site_token.UI_name_domain_id']), 'addIndex', 'civicrm_site_token', [['name', 'domain_id']], 'UI');
    $this->addTask('Create "message header" token', 'create_mesage_header_token');
    if (!CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_extension WHERE full_name = "eventcart"')) {
      $this->addTask('Remove data related to disabled even cart extension', 'removeEventCartAssets');
    }
    else {
      $this->addTask('Migrate event cart ID', 'migrateEventCartID');
    }
    $this->addTask('Update civicrm_mailing to permit deleting records from civicrm_mailing_job', 'updateNewCiviMailFields');
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_76_beta1($rev): void {
    $this->addTask('Fix default for status in civicrm_mailing table', 'alterColumn', 'civicrm_mailing', 'status', "varchar(12) DEFAULT 'Draft' COMMENT 'The status of this Mailing'");
    if (CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_mailing WHERE `status` = NULL')) {
      $this->addTask('Update civicrm_mailing to permit deleting records from civicrm_mailing_job', 'updateNewCiviMailFields');
    }
  }

  public static function create_mesage_header_token() {
    $query = CRM_Core_DAO::executeQuery('SELECT id FROM civicrm_domain');
    $domains = $query->fetchAll();
    foreach ($domains as $domain) {
      CRM_Core_DAO::executeQuery(
        "INSERT IGNORE INTO civicrm_site_token (domain_id, name, label, body_html, body_text, is_reserved, is_active)
      VALUES(
       " . $domain['id'] . ",
       'message_header',
       '" . ts('Message Header') . "',
     '<!-- " . ts('This is the %1 token HTML content.', [1 => '{site.message_header}']) . " -->',
      '', 1, 1)"
      );
    }
    return TRUE;
  }

  public static function updateNewCiviMailFields(CRM_Queue_TaskContext $ctx): bool {
    [$minId, $maxId] = CRM_Core_DAO::executeQuery("SELECT coalesce(min(id),0), coalesce(max(id),0)
      FROM civicrm_mailing ")->getDatabaseResult()->fetchRow();
    if (!$maxId) {
      return TRUE;
    }
    for ($startId = $minId; $startId <= $maxId; $startId += self::MAILING_BATCH_SIZE) {
      $endId = min($maxId, $startId + self::MAILING_BATCH_SIZE - 1);
      $task = new CRM_Queue_Task([static::class, 'fillMailingData'],
        [$startId, $endId],
        sprintf('Backfill civicrm_mailing start_date, end_date, status (%d => %d)', $startId, $endId));
      $ctx->queue->createItem($task, ['weight' => -1]);
    }
    return TRUE;
  }

  /**
   * Drop tables, disable the message template as they relate to event carts.
   *
   * It would be nice to delete the message template but who knows there could be a gotcha.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function removeEventCartAssets(): bool {
    try {
      CRM_Core_DAO::executeQuery("UPDATE civicrm_msg_template SET is_active = 0 WHERE workflow_name = 'event_registration_receipt'");
      if (!CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_events_in_carts LIMIT 1')) {
        CRM_Core_DAO::executeQuery('DROP table civicrm_events_in_carts');
      }
      if (!CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_event_carts LIMIT 1')) {
        \CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_participant', 'FK_civicrm_participant_cart_id');
        CRM_Core_DAO::executeQuery('DROP table civicrm_event_carts');
      }
      if (!CRM_Core_DAO::singleValueQuery('SELECT cart_id FROM civicrm_participant WHERE cart_id > 0 LIMIT 1')) {
        \CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_participant', 'FK_civicrm_participant_cart_id');
        \CRM_Core_BAO_SchemaHandler::dropColumn('civicrm_participant', 'cart_id', FALSE, TRUE);
      }
    }
    catch (CRM_Core_Exception $e) {
      // hmm what could possibly go wrong. A few stray artifacts is not as bad as a fail here I guess.
    }
    return TRUE;
  }

  /**
   * Drop tables, disable the message template as they relate to event carts.
   *
   * It would be nice to delete the message template but who knows there could be a gotcha.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function migrateEventCartID(): bool {
    try {
      CRM_Core_DAO::executeQuery("CREATE TABLE IF NOT EXISTS `civicrm_event_cart_participant` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Event Cart Participant ID',
  `participant_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to Participant ID',
  `cart_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to Event Cart ID',
  PRIMARY KEY (`id`),
  KEY `FK_civicrm_event_cart_participant_participant_id` (`participant_id`),
  KEY `FK_civicrm_event_cart_participant_cart_id` (`cart_id`),
  CONSTRAINT `FK_civicrm_event_cart_participant_cart_id` FOREIGN KEY (`cart_id`) REFERENCES `civicrm_event_carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_civicrm_event_cart_participant_participant_id` FOREIGN KEY (`participant_id`) REFERENCES `civicrm_participant` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC");

      CRM_Core_DAO::executeQuery('INSERT INTO civicrm_event_cart_participant (participant_id, cart_id)
       SELECT id as participant_id, cart_id FROM civicrm_participant WHERE cart_id > 0');
      \CRM_Core_BAO_SchemaHandler::dropColumn('civicrm_participant', 'cart_id', FALSE, TRUE);
    }
    catch (CRM_Core_Exception $e) {
      // hmm what could possibly go wrong. A few stray artifacts is not as bad as a fail here I guess.
    }
    return TRUE;
  }

  public static function fillMailingData(CRM_Queue_TaskContext $ctx, int $startId, int $endId): bool {
    CRM_Core_DAO::executeQuery('
UPDATE civicrm_mailing m
LEFT JOIN
    (SELECT MIN(job.start_date) as start_date, job.mailing_id FROM civicrm_mailing_job job GROUP BY mailing_id) as job
ON job.mailing_id = m.id
SET m.start_date = job.start_date
WHERE m.id BETWEEN %1 AND %2
AND m.start_date IS NULL
   ', [1 => [$startId, 'Integer'], 2 => [$endId, 'Integer']]);

    CRM_Core_DAO::executeQuery('
UPDATE civicrm_mailing m
LEFT JOIN
    (SELECT MIN(job.start_date) as start_date, MAX(job.end_date) as end_date,
  job.mailing_id FROM civicrm_mailing_job job GROUP BY job.mailing_id
)
as job
ON job.mailing_id = m.id
SET m.end_date = job.end_date, m.status = "Complete"
WHERE m.is_completed = 1
   AND m.id BETWEEN %1 AND %2
   AND (m.status IS NULL OR m.status = "Draft")
   ', [1 => [$startId, 'Integer'], 2 => [$endId, 'Integer']]);

    CRM_Core_DAO::executeQuery('
UPDATE  civicrm_mailing m
INNER JOIN
  (
  SELECT
  job.mailing_id FROM civicrm_mailing_job job
  WHERE status = "Paused"
)
as job
ON job.mailing_id = m.id
SET m.status = "Paused"
WHERE (m.status IS NULL OR m.status = "Draft")
   AND m.id BETWEEN %1 AND %2', [1 => [$startId, 'Integer'], 2 => [$endId, 'Integer']]);

    CRM_Core_DAO::executeQuery('
UPDATE  civicrm_mailing m
INNER JOIN
  (
    SELECT MAX(job.end_date) as end_date, mailing_id
    FROM civicrm_mailing_job job
    WHERE status = "Cancelled"
    GROUP BY job.mailing_id
  ) as job

ON job.mailing_id = m.id
SET m.status = "Canceled"
WHERE (m.status IS NULL OR m.status = "Draft")
   AND m.id BETWEEN %1 AND %2', [1 => [$startId, 'Integer'], 2 => [$endId, 'Integer']]);

    CRM_Core_DAO::executeQuery('
UPDATE  civicrm_mailing m
INNER JOIN
    (SELECT MAX(job.end_date) as end_date,
  job.mailing_id FROM civicrm_mailing_job job
  WHERE status = "Running"
  GROUP BY job.mailing_id
)
as job
ON job.mailing_id = m.id
SET m.status = "Running"
WHERE (m.status IS NULL OR m.status = "Draft")
   AND m.id BETWEEN %1 AND %2', [1 => [$startId, 'Integer'], 2 => [$endId, 'Integer']]);

    CRM_Core_DAO::executeQuery('
UPDATE  civicrm_mailing m
INNER JOIN
    (SELECT MIN(job.start_date) as start_date, MAX(job.end_date) as end_date,
  job.mailing_id FROM civicrm_mailing_job job
  WHERE job.status = "Scheduled"
  GROUP BY job.mailing_id
)
as job
ON job.mailing_id = m.id
SET m.status = "Scheduled"
WHERE (m.status IS NULL OR m.status = "Draft")
   AND m.id BETWEEN %1 AND %2', [1 => [$startId, 'Integer'], 2 => [$endId, 'Integer']]);

    // It seems some older records are missing the is_completed so any that have not yet been
    // picked up with a different status but have completed records should be completed.
    CRM_Core_DAO::executeQuery('
UPDATE  civicrm_mailing m
INNER JOIN
    (SELECT MIN(job.start_date) as start_date, MAX(job.end_date) as end_date,
  job.mailing_id FROM civicrm_mailing_job job
  WHERE job.status = "Complete"
  GROUP BY job.mailing_id
)
as job
ON job.mailing_id = m.id
SET m.status = "Complete",
    m.start_date = job.start_date,
    m.end_date = job.end_date,
    is_completed = 1
WHERE (m.status IS NULL OR m.status = "Draft")
   AND m.id BETWEEN %1 AND %2', [1 => [$startId, 'Integer'], 2 => [$endId, 'Integer']]);

    // For sites that upgraded to the rc the default of status will have been NULL
    // initially so we need to set those to Draft.
    CRM_Core_DAO::executeQuery('
UPDATE  civicrm_mailing m
SET `status` = "Draft" WHERE `status` IS NULL
AND m.id BETWEEN %1 AND %2', [1 => [$startId, 'Integer'], 2 => [$endId, 'Integer']]);
    return TRUE;
  }

}
