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
 * Upgrade logic for FiveThirtyOne
 */
class CRM_Upgrade_Incremental_php_FiveThirtyOne extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_31_alpha1($rev) {
    $this->addTask('Expand internal civicrm group title field to be 255 in length', 'groupTitleRestore');
    $this->addTask('Add in optional public title group table', 'addColumn', 'civicrm_group', 'frontend_title', "varchar(255)   DEFAULT NULL COMMENT 'Alternative public title for this Group.'", TRUE, '5.31.alpha1', FALSE);
    $this->addTask('Add in optional public description group table', 'addColumn', 'civicrm_group', 'frontend_description', "text   DEFAULT NULL COMMENT 'Alternative public description of the group.'", TRUE, '5.31.alpha1');
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Remove Eway Single Currency Payment Processor type if not used or install the new extension for it', 'enableEwaySingleExtension');
    $this->addTask('dev/core#1486 Remove FKs from ACL Cache tables', 'removeFKsFromACLCacheTables');
    $this->addTask('Activate core extension "Greenwich"', 'installGreenwich');
    $this->addTask('Add is_non_case_email_skipped column to civicrm_mail_settings', 'addColumn',
      'civicrm_mail_settings', 'is_non_case_email_skipped', "TINYINT DEFAULT 0 NOT NULL COMMENT 'Skip emails which do not have a Case ID or Case hash'");
    $this->addTask('Add is_contact_creation_disabled_if_no_match column to civicrm_mail_settings', 'addColumn',
      'civicrm_mail_settings', 'is_contact_creation_disabled_if_no_match', "TINYINT DEFAULT 0 NOT NULL COMMENT 'If this option is enabled, CiviCRM will not create new contacts when filing emails'");
  }

  public function upgrade_5_31_beta2($rev) {
    $this->addTask('Restore null-ity of "civicrm_group.title" field', 'groupTitleRestore');
    // Not used // $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
  }

  public static function enableEwaySingleExtension(CRM_Queue_TaskContext $ctx) {
    $eWAYPaymentProcessorType = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_payment_processor_type WHERE class_name = 'Payment_eWAY'");
    if ($eWAYPaymentProcessorType) {
      $ewayPaymentProcessorCount = CRM_Core_DAO::singleValueQuery("SELECT count(id) FROM civicrm_payment_processor WHERE payment_processor_type_id = %1", [1 => [$eWAYPaymentProcessorType, 'Positive']]);
      if ($ewayPaymentProcessorCount) {
        $insert = CRM_Utils_SQL_Insert::into('civicrm_extension')->row([
          'type' => 'module',
          'full_name' => 'ewaysingle',
          'name' => 'eway Single currency extension',
          'label' => 'eway Single currency extension',
          'file' => 'ewaysingle',
          'schema_version' => NULL,
          'is_active' => 1,
        ]);
        CRM_Core_DAO::executeQuery($insert->usingReplace()->toSQL());
        $managedEntity = CRM_Utils_SQL_Insert::into('civicrm_managed')->row([
          'name' => 'eWAY',
          'module' => 'ewaysingle',
          'entity_type' => 'PaymentProcessorType',
          'entity_id' => $eWAYPaymentProcessorType,
          'cleanup' => NULL,
        ]);
        CRM_Core_DAO::executeQuery($managedEntity->usingReplace()->toSQL());
      }
      else {
        CRM_Core_DAO::executeQuery("DELETE FROM civicrm_payment_processor_type WHERE id = %1", [1 => [$eWAYPaymentProcessorType, 'Positive']]);
      }
    }
    return TRUE;
  }

  public static function removeFKsFromACLCacheTables(CRM_Queue_TaskContext $ctx) {
    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_acl_contact_cache', 'FK_civicrm_acl_contact_cache_contact_id');
    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_acl_cache', 'FK_civicrm_acl_cache_contact_id');
    CRM_Core_BAO_SchemaHandler::createIndexes(['civicrm_acl_cache' => ['contact_id']]);
    return TRUE;
  }

  /**
   * Install greenwich extensions.
   *
   * This feature is restructured as a core extension - which is primarily a code cleanup step.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   */
  public static function installGreenwich(CRM_Queue_TaskContext $ctx) {
    // Install via direct SQL manipulation. Note that:
    // (1) This extension has no activation logic.
    // (2) On new installs, the extension is activated purely via default SQL INSERT.
    // (3) Caches are flushed at the end of the upgrade.
    // ($) Over long term, upgrade steps are more reliable in SQL. API/BAO sometimes don't work mid-upgrade.
    $insert = CRM_Utils_SQL_Insert::into('civicrm_extension')->row([
      'type' => 'module',
      'full_name' => 'greenwich',
      'name' => 'Theme: Greenwich',
      'label' => 'Theme: Greenwich',
      'file' => 'greenwich',
      'schema_version' => NULL,
      'is_active' => 1,
    ]);
    CRM_Core_DAO::executeQuery($insert->usingReplace()->toSQL());

    return TRUE;
  }

  /**
   * The prior task grouptitlefieldExpand went a bit too far in making the `title` NOT NULL.
   *
   * @link https://lab.civicrm.org/dev/translation/-/issues/58
   * @param \CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function groupTitleRestore(CRM_Queue_TaskContext $ctx) {
    $locales = CRM_Core_I18n::getMultilingual();
    $queries = [];
    if ($locales) {
      foreach ($locales as $locale) {
        $queries[] = "ALTER TABLE civicrm_group CHANGE `title_{$locale}` `title_{$locale}` varchar(255) DEFAULT NULL COMMENT 'Name of Group.'";
      }
    }
    else {
      $queries[] = "ALTER TABLE civicrm_group CHANGE `title` `title` varchar(255) DEFAULT NULL COMMENT 'Name of Group.'";
    }
    foreach ($queries as $query) {
      CRM_Core_DAO::executeQuery($query, [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

}
