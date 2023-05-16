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
 * Upgrade logic for the 5.61.x series.
 *
 * Each minor version in the series is handled by either a `5.61.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_61_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSixtyOne extends CRM_Upgrade_Incremental_Base {

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL): void {
    if ($rev === '5.61.alpha1' && CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_contribution_recur LIMIT 1')) {
      $documentationUrl = 'https://docs.civicrm.org/dev/en/latest/financial/recurring-contributions/';
      $documentationAnchor = 'target="_blank" href="' . htmlentities($documentationUrl) . '"';
      $extensionUrl = 'https://docs.civicrm.org/dev/en/latest/financial/recurring-contributions/';
      $extensionAnchor = 'target="_blank" href="' . htmlentities($extensionUrl) . '"';

      $preUpgradeMessage .= '<p>' .
        ts('This release contains a change to the behaviour of recurring contributions under some edge-case circumstances.')
        . ' ' . ts('Since 5.49 the amount and currency on the recurring contribution record changed when the amount of any contribution against it was changed, indicating a change in future intent.')
        . ' ' . ts('It is generally not possible to edit the amount for contributions linked to recurring contributions so for most sites this would never occur anyway.')
        . ' ' . ts('If you still want this behaviour you should install the <a %1>Recur future amounts extension</a>', [1 => $extensionAnchor])
        . ' ' . ts('Please <a %1>read about recurring contribution templates</a> for more information', [1 => $documentationAnchor])
        . '</p>';
    }
  }

  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev): void {
    if ($rev === '5.61.1') {
      if (defined('CIVICRM_UF') && CIVICRM_UF === 'Drupal8') {
        $postUpgradeMessage .= '<p>' . ts('You must do a one-time clear of Drupal caches now before visiting CiviCRM pages to rebuild the menu routes to avoid fatal errors. <a %1>Read more</a>.', [1 => 'href="https://civicrm.org/redirect/drupal-5.61" target="_blank"']) . '</p>';
      }
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_61_alpha1($rev): void {
    // First add `frontend_title` column *without* NOT NULL constraint
    $this->addTask('Add frontend_title to civicrm_payment_processor', 'addColumn',
      'civicrm_payment_processor', 'frontend_title', "varchar(255) COMMENT 'Name of processor when shown to users making a payment.'", TRUE, '5.61.alpha1'
    );
    // Sql contains updates to fill paymentProcessor title & frontend_title
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Make PaymentProcessor.name required', 'alterColumn', 'civicrm_payment_processor', 'name', "varchar(64) NOT NULL COMMENT 'Payment Processor Name.'");
    $this->addTask('Make PaymentProcessor.title required', 'alterColumn', 'civicrm_payment_processor', 'title', "varchar(255) NOT NULL COMMENT 'Name of processor when shown to CiviCRM administrators.'", TRUE);
    $this->addTask('Make PaymentProcessor.frontend_title required', 'alterColumn', 'civicrm_payment_processor', 'frontend_title', "varchar(255) NOT NULL COMMENT 'Name of processor when shown to users making a payment.'", TRUE);

    // Drop unused column
    $this->addTask('Drop column civicrm_custom_field.javascript', 'dropColumn', 'civicrm_custom_field', 'javascript');

    $this->addTask(ts('Dedupe cache table'), 'dedupeCache');
    $this->addTask(ts('Drop index %1', [1 => 'civicrm_cache.UI_group_path_date']), 'dropIndex', 'civicrm_cache', 'UI_group_path_date');
    $this->addTask(ts('Create index %1', [1 => 'civicrm_cache.UI_group_name_path']), 'addIndex', 'civicrm_cache', [['group_name', 'path']], 'UI');
    $this->addTask(ts('Create index %1', [1 => 'civicrm_cache.index_expired_date']), 'addIndex', 'civicrm_cache', [['expired_date']], 'index');
    $this->addTask(ts('Update Saved Mapping for contribution import', [1 => $rev]), 'convertMappingFieldsToApi4StyleNames', $rev);

    $this->addTask(ts('Drop index %1', [1 => 'civicrm_campaign.UI_campaign_name']), 'dropIndex', 'civicrm_campaign', 'UI_campaign_name');
    $this->addTask(ts('Create index %1', [1 => 'civicrm_campaign.UI_name']), 'addIndex', 'civicrm_campaign', 'name', 'UI');
  }

  /**
   * Needs to exist for postUpgradeMessage to get called.
   */
  public function upgrade_5_61_1($rev): void {
  }

  /**
   * Remove extraneous/duplicate records from `civicrm_cache`.
   *
   * Formally, the cache table allowed multiple (key,value) pairs if created at different times.
   * In practice, this cleanup should generally do nothing -- the `SqlGroup::set()` has had duplicate
   * prevention, and the cache will flush at the end of the upgrade anyway. Never-the-less, if
   * duplicates are somehow in there, then we should cleanly remove them rather than let the upgrade fail.
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @return bool
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function dedupeCache($ctx): bool {
    $duplicates = CRM_Core_DAO::executeQuery('
      SELECT c.id FROM civicrm_cache c
      LEFT JOIN (SELECT group_name, path, MAX(created_date) newest FROM civicrm_cache GROUP BY group_name, path) recent
        ON (c.group_name=recent.group_name AND c.path=recent.path AND c.created_date=recent.newest)
      WHERE recent.newest IS NULL')
      ->fetchMap('id', 'id');
    if ($duplicates) {
      CRM_Utils_SQL_Delete::from('civicrm_cache')
        ->where('id in (@IDS)')
        ->param('IDS', $duplicates)
        ->execute();
    }
    return TRUE;
  }

  /**
   * @return bool
   * @throws \CRM_Core_Exception
   * @noinspection PhpUnused
   */
  public static function convertMappingFieldsToApi4StyleNames(): bool {
    $mappings = MappingField::get(FALSE)
      ->setSelect(['id', 'name'])
      ->addWhere('mapping_id.mapping_type_id:name', '=', 'Import Contribution')
      ->execute();

    $fieldMap = [
      'contribution_cancel_date' => 'cancel_date',
      'contribution_check_number' => 'check_number',
      'contribution_campaign_id' => 'campaign_id',
    ];
    $fieldMap += CRM_Core_DAO::executeQuery('
      SELECT CONCAT("custom_", fld.id) AS old, CONCAT(grp.name, ".", fld.name) AS new
      FROM civicrm_custom_field fld, civicrm_custom_group grp
      WHERE grp.id = fld.custom_group_id AND grp.extends = "Contribution"
    ')->fetchMap('old', 'new');

    // Update the mapped fields.
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

}
