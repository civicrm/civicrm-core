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
 * Upgrade logic for the 6.18.x series.
 *
 * Each minor version in the series is handled by either a `6.18.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_18_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixEighteen extends CRM_Upgrade_Incremental_Base {

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL): void {
    parent::setPreUpgradeMessage($preUpgradeMessage, $rev, $currentVer);
    if ($rev === '6.18.alpha1') {
      $preUpgradeMessage .= '<p>' .
        ts('The FormBuilder HTML Editor extension has been removed. Editing is now possible directly in FormBuilder; grant users the "FormBuilder: edit raw HTML markup" permission for access.') .
        '</p>';
      $customPHPDir = CRM_Core_Config::singleton()->customPHPPathDir;
      if (!empty($customPHPDir)) {
        if (file_exists(CRM_Utils_File::addTrailingSlash($customPHPDir) . 'civicrmHooks.php')) {
          $message = ts('This installation contains a legacy civicrmHooks.php file within the customPHPDir. This will no longer be used by CiviCRM, System Administrators should work on migrating the hooks into an extension');
          $preUpgradeMessage .= "<p>{$message}</p>";
        }
        $activityClassFound = FALSE;
        $activityTypes = CRM_Core_DAO::executeQuery("SELECT ov.name FROM civicrm_option_value ov INNER JOIN civicrm_option_group og ON og.id = ov.option_group_id WHERE og.name = 'activity_type'");
        while ($activityTypes->fetch()) {
          if (!$activityClassFound &&
            (file_exists(CRM_Utils_File::addTrailingSlash($customPHPDir) . "CRM/Activity/Form/Activity/{$activityTypes->name}.php")
              || file_exists(CRM_Utils_File::addTrailingSlash($customPHPDir) . "CRM/Case/Form/Activity/{$activityTypes->name}.php"))) {
            $activityClassFound = TRUE;
          }
        }
        if ($activityClassFound) {
          $message = ts('This installation contains custom code for Activity Type forms that are within a legacy custom PHP directory. They should be moved to a custom extension, using the same directory structure.');
          $preUpgradeMessage .= "<p>{$message}</p>";
        }
      }
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_18_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add column "RelationshipType.weight"', 'alterSchemaField', 'RelationshipType', 'weight', [
      'title' => ts('Order'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Ordering of the relationship types.'),
      'add' => '6.18',
      'default' => 0,
    ]);
    $this->addTask(ts('Initialize relationship type weights'), 'initializeRelationshipTypeWeights');
    $this->addTask('Change column "Mailing.name" to varchar(255)', 'alterSchemaField', 'Mailing', 'name', [
      'sql_type' => 'varchar(255)',
      'description' => ts('Mailing Name.'),
    ]);
    $this->addTask(ts('Create index %1', [1 => 'civicrm_membership_status.UI_name']), 'addIndex', 'civicrm_membership_status', 'name', 'UI');
    $this->addTask(ts('Create index %1', [1 => 'civicrm_participant_status_type.UI_name']), 'addIndex', 'civicrm_participant_status_type', 'name', 'UI');

    $this->addTask('Add unique index to Currency.name', 'addIndex', 'civicrm_currency', 'name', 'UI');

    // Add FK to currency fields
    $entitiesWithCurrency = [
      'Contribution' => 'currency',
      'ContributionPage' => 'currency',
      'ContributionRecur' => 'currency',
      'ContributionSoft' => 'currency',
      'Product' => 'currency',
      'Event' => 'currency',
      'Participant' => 'fee_currency',
      'FinancialItem' => 'currency',
      'FinancialTrxn' => 'currency',
      'PCP' => 'currency',
      'Pledge' => 'currency',
      'PledgePayment' => 'currency',
    ];
    foreach ($entitiesWithCurrency as $entityName => $fieldName) {
      $this->addTask("Add foreign key to $entityName.$fieldName", 'addCurrencyFk', $entityName, $fieldName);
    }

    $this->addTask('Add CustomField.control_field column', 'alterSchemaField', 'CustomField', 'control_field', [
      'title' => ts('Depends on'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'description' => ts('Name of the field that this field depends on.'),
      'add' => '6.18',
      'default' => NULL,
    ], 'AFTER in_selector');

    $this->addTask(ts('Recreate Mysql Full Text Search indices if necessary'), 'recreateFtsIndexIfNeeded');
  }

  /**
   * Initialize relationship type weights.
   *
   * @param CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function initializeRelationshipTypeWeights(CRM_Queue_TaskContext $ctx): bool {
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_relationship_type
      SET weight = id
      WHERE weight = 0
    ");

    return TRUE;
  }

  public static function addCurrencyFk($ctx, $entityName, $fieldName): bool {
    $tableName = Civi::entity($entityName)->getMeta('table');

    // Safety check, remove any invalid currency
    CRM_Core_DAO::executeQuery("UPDATE `$tableName` SET `$fieldName` = NULL WHERE `$fieldName` IS NOT NULL AND `$fieldName` NOT IN (SELECT `name` FROM `civicrm_currency`)", i18nRewrite: FALSE);

    Civi::schemaHelper()->createForeignKey($tableName, $fieldName, [
      'entity_reference' => [
        'entity' => 'Currency',
        'key' => 'name',
        'on_delete' => 'SET NULL',
      ],
    ]);

    return TRUE;
  }

  public static function recreateFtsIndexIfNeeded(CRM_Queue_TaskContext $ctx): bool {
    // drop `contact_name` index if added with old def in 6.17
    CRM_Core_BAO_SchemaHandler::dropIndexIfExists('civicrm_contact', 'contact_name');
    // ensure `contact_names` index is added (no op if FTS is disable)
    self::createMissingFtsIndices();

    return TRUE;
  }

}
