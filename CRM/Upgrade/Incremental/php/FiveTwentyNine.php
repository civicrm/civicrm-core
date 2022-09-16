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
 * Upgrade logic for FiveTwentyNine
 */
class CRM_Upgrade_Incremental_php_FiveTwentyNine extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed beforeupgrade.
   *
   * Note: This function is called iteratively for each upcoming
   * revision to the database.
   *
   * @param string $preUpgradeMessage
   * @param string $rev
   *   a version number, e.g. '4.4.alpha1', '4.4.beta3', '4.4.0'.
   * @param null $currentVer
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    if ($rev == '5.29.beta1') {
      if (CIVICRM_UF === 'Drupal8') {
        $preUpgradeMessage .= '<p>' . ts('<em>Pre-announcement for upcoming version 5.30</em>: If your composer configuration or composer.json does not enable patching, you MUST do that BEFORE running composer to update your files to version 5.30. Either by using `composer config \'extra.enable-patching\' true`, or updating the top level composer.json\'s extra section with `"enable-patching": true`. See %1 for details.', [1 => '<a href="' . CRM_Utils_System::docURL2('installation/drupal8', TRUE) . '">Drupal 8 installation guide</a>']) . '</p>';
      }
    }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_29_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Install eventcart extension', 'installEventCart');

    [$minId, $maxId] = CRM_Core_DAO::executeQuery("SELECT coalesce(min(id),0), coalesce(max(id),0)
      FROM civicrm_relationship ")->getDatabaseResult()->fetchRow();
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = ts("Upgrade DB to %1: Fill civicrm_relationship_cache (%2 => %3)", [
        1 => $rev,
        2 => $startId,
        3 => $endId,
      ]);
      $this->addTask($title, 'populateRelationshipCache', $startId, $endId);
    }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_29_beta1($rev) {
    // Not used // $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Make label field non required on price field value', 'priceFieldValueLabelNonRequired');
  }

  /**
   * Make the price field value label column non required
   * @return bool
   */
  public static function priceFieldValueLabelNonRequired() {
    $locales = CRM_Core_I18n::getMultilingual();
    if ($locales) {
      foreach ($locales as $locale) {
        CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_price_field_value CHANGE `label_{$locale}` `label_{$locale}` varchar(255) DEFAULT NULL  COMMENT 'Price field option label'", [], TRUE, NULL, FALSE, FALSE);
        CRM_Core_DAO::executeQuery("UPDATE civicrm_price_field_value SET label_{$locale} = NULL WHERE label_{$locale} = 'null'", [], TRUE, NULL, FALSE, FALSE);
      }
    }
    else {
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_price_field_value CHANGE `label` `label` varchar(255) DEFAULT NULL  COMMENT 'Price field option label'", [], TRUE, NULL, FALSE, FALSE);
      CRM_Core_DAO::executeQuery("UPDATE civicrm_price_field_value SET label = NULL WHERE label = 'null'", [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

  /**
   * Install sequentialCreditNotes extension.
   *
   * This feature is restructured as a core extension - which is primarily a code cleanup step.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   */
  public static function installEventCart(CRM_Queue_TaskContext $ctx) {
    // Install via direct SQL manipulation. Note that:
    // (1) This extension has no activation logic.
    // (2) On new installs, the extension is activated purely via default SQL INSERT.
    // (3) Caches are flushed at the end of the upgrade.
    // ($) Over long term, upgrade steps are more reliable in SQL. API/BAO sometimes don't work mid-upgrade.
    $insert = CRM_Utils_SQL_Insert::into('civicrm_extension')->row([
      'type' => 'module',
      'full_name' => 'eventcart',
      'name' => 'eventcart',
      'label' => 'Event Cart',
      'file' => 'eventcart',
      'schema_version' => NULL,
      'is_active' => 1,
    ]);
    CRM_Core_DAO::executeQuery($insert->usingReplace()->toSQL());

    return TRUE;
  }

  /**
   * @param \CRM_Queue_TaskContext $ctx
   * @param int $startId
   *   The lowest relationship ID that should be updated.
   * @param int $endId
   *   The highest relationship ID that should be updated.
   * @return bool
   *   TRUE on success
   */
  public static function populateRelationshipCache(CRM_Queue_TaskContext $ctx, $startId, $endId) {
    // NOTE: We duplicate CRM_Contact_BAO_RelationshipCache::$mappings in case
    // the schema evolves over multiple releases.
    $mappings = [
      'a_b' => [
        'relationship_id' => 'rel.id',
        'relationship_type_id' => 'rel.relationship_type_id',
        'orientation' => '"a_b"',
        'near_contact_id' => 'rel.contact_id_a',
        'near_relation' => 'reltype.name_a_b',
        'far_contact_id' => 'rel.contact_id_b',
        'far_relation' => 'reltype.name_b_a',
        'start_date' => 'rel.start_date',
        'end_date' => 'rel.end_date',
        'is_active' => 'rel.is_active',
      ],
      'b_a' => [
        'relationship_id' => 'rel.id',
        'relationship_type_id' => 'rel.relationship_type_id',
        'orientation' => '"b_a"',
        'near_contact_id' => 'rel.contact_id_b',
        'near_relation' => 'reltype.name_b_a',
        'far_contact_id' => 'rel.contact_id_a',
        'far_relation' => 'reltype.name_a_b',
        'start_date' => 'rel.start_date',
        'end_date' => 'rel.end_date',
        'is_active' => 'rel.is_active',
      ],
    ];
    $keyFields = ['relationship_id', 'orientation'];

    foreach ($mappings as $mapping) {
      $query = CRM_Utils_SQL_Select::from('civicrm_relationship rel')
        ->join('reltype', 'INNER JOIN civicrm_relationship_type reltype ON rel.relationship_type_id = reltype.id')
        ->syncInto('civicrm_relationship_cache', $keyFields, $mapping)
        ->where('rel.id >= #START AND rel.id <= #END', [
          '#START' => $startId,
          '#END' => $endId,
        ]);
      $query->execute();
    }

    return TRUE;
  }

}
