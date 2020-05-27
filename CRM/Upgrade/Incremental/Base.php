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

use Civi\Core\SettingsBag;

/**
 * Base class for incremental upgrades
 */
class CRM_Upgrade_Incremental_Base {
  const BATCH_SIZE = 5000;

  /**
   * Verify DB state.
   *
   * @param $errors
   *
   * @return bool
   */
  public function verifyPreDBstate(&$errors) {
    return TRUE;
  }

  /**
   * Compute any messages which should be displayed before upgrade.
   *
   * Note: This function is called iteratively for each upcoming
   * revision to the database.
   *
   * @param $preUpgradeMessage
   * @param string $rev
   *   a version number, e.g. '4.8.alpha1', '4.8.beta3', '4.8.0'.
   * @param null $currentVer
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
  }

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
  }

  /**
   * (Queue Task Callback)
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @param string $rev
   *
   * @return bool
   */
  public static function runSql(CRM_Queue_TaskContext $ctx, $rev) {
    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);

    return TRUE;
  }

  /**
   * Syntactic sugar for adding a task.
   *
   * Task is (a) in this class and (b) has a high priority.
   *
   * After passing the $funcName, you can also pass parameters that will go to
   * the function. Note that all params must be serializable.
   *
   * @param string $title
   * @param string $funcName
   */
  protected function addTask($title, $funcName) {
    $queue = CRM_Queue_Service::singleton()->load([
      'type' => 'Sql',
      'name' => CRM_Upgrade_Form::QUEUE_NAME,
    ]);

    $args = func_get_args();
    $title = array_shift($args);
    $funcName = array_shift($args);
    $task = new CRM_Queue_Task(
      [get_class($this), $funcName],
      $args,
      $title
    );
    $queue->createItem($task, ['weight' => -1]);
  }

  /**
   * Remove a payment processor if not in use
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $name
   * @return bool
   * @throws \CiviCRM_API3_Exception
   */
  public static function removePaymentProcessorType(CRM_Queue_TaskContext $ctx, $name) {
    $processors = civicrm_api3('PaymentProcessor', 'getcount', ['payment_processor_type_id' => $name]);
    if (empty($processors['result'])) {
      $result = civicrm_api3('PaymentProcessorType', 'get', [
        'name' => $name,
        'return' => 'id',
      ]);
      if (!empty($result['id'])) {
        civicrm_api3('PaymentProcessorType', 'delete', ['id' => $result['id']]);
      }
    }
    return TRUE;
  }

  /**
   * @param string $table_name
   * @param string $constraint_name
   * @return bool
   */
  public static function checkFKExists($table_name, $constraint_name) {
    return CRM_Core_BAO_SchemaHandler::checkFKExists($table_name, $constraint_name);
  }

  /**
   * Add a column to a table if it doesn't already exist
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $table
   * @param string $column
   * @param string $properties
   * @param bool $localizable is this a field that should be localized
   * @param string|null $version CiviCRM version to use if rebuilding multilingual schema
   *
   * @return bool
   */
  public static function addColumn($ctx, $table, $column, $properties, $localizable = FALSE, $version = NULL) {
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    $queries = [];
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists($table, $column, FALSE)) {
      if ($domain->locales) {
        if ($localizable) {
          $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
          foreach ($locales as $locale) {
            if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists($table, "{$column}_{$locale}", FALSE)) {
              $queries[] = "ALTER TABLE `$table` ADD COLUMN `{$column}_{$locale}` $properties";
            }
          }
        }
        else {
          $queries[] = "ALTER TABLE `$table` ADD COLUMN `$column` $properties";
        }
      }
      else {
        $queries[] = "ALTER TABLE `$table` ADD COLUMN `$column` $properties";
      }
      foreach ($queries as $query) {
        CRM_Core_DAO::executeQuery($query, [], TRUE, NULL, FALSE, FALSE);
      }
    }
    if ($domain->locales) {
      $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
      CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales, $version, TRUE);
    }
    return TRUE;
  }

  /**
   * Do any relevant message template updates.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $version
   */
  public static function updateMessageTemplates($ctx, $version) {
    $messageTemplateObject = new CRM_Upgrade_Incremental_MessageTemplates($version);
    $messageTemplateObject->updateTemplates();

  }

  /**
   * Re-save any valid values from contribute settings into the normal setting
   * format.
   *
   * We render the array of contribution_invoice_settings and any that have
   * metadata defined we add to the correct key. This is safe to run even if no
   * settings are to be converted, per the test in
   * testConvertUpgradeContributeSettings.
   *
   * @param $ctx
   *
   * @return bool
   */
  public static function updateContributeSettings($ctx) {
    // Use a direct query as api now does some handling on this.
    $settings = CRM_Core_DAO::executeQuery("SELECT value, domain_id FROM civicrm_setting WHERE name = 'contribution_invoice_settings'");

    while ($settings->fetch()) {
      $contributionSettings = (array) CRM_Utils_String::unserialize($settings->value);
      foreach (array_merge(SettingsBag::getContributionInvoiceSettingKeys(), ['deferred_revenue_enabled' => 'deferred_revenue_enabled']) as $possibleKeyName => $settingName) {
        if (!empty($contributionSettings[$possibleKeyName]) && empty(Civi::settings($settings->domain_id)->getExplicit($settingName))) {
          Civi::settings($settings->domain_id)->set($settingName, $contributionSettings[$possibleKeyName]);
        }
      }
    }
    return TRUE;
  }

  /**
   * Do any relevant smart group updates.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param array $actions
   *
   * @return bool
   */
  public function updateSmartGroups($ctx, $actions) {
    $groupUpdateObject = new CRM_Upgrade_Incremental_SmartGroups();
    $groupUpdateObject->updateGroups($actions);
    return TRUE;
  }

  /**
   * Drop a column from a table if it exist.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $table
   * @param string $column
   * @return bool
   */
  public static function dropColumn($ctx, $table, $column) {
    if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists($table, $column)) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `$table` DROP COLUMN `$column`",
        [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

  /**
   * Add a index to a table column.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $table
   * @param string|array $column
   * @return bool
   */
  public static function addIndex($ctx, $table, $column) {
    $tables = [$table => (array) $column];
    CRM_Core_BAO_SchemaHandler::createIndexes($tables);

    return TRUE;
  }

  /**
   * Drop a index from a table if it exist.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $table
   * @param string $indexName
   * @return bool
   */
  public static function dropIndex($ctx, $table, $indexName) {
    CRM_Core_BAO_SchemaHandler::dropIndexIfExists($table, $indexName);

    return TRUE;
  }

  /**
   * Drop a table... but only if it's empty.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $table
   * @return bool
   */
  public static function dropTableIfEmpty($ctx, $table) {
    if (CRM_Core_DAO::checkTableExists($table)) {
      if (!CRM_Core_DAO::checkTableHasData($table)) {
        CRM_Core_BAO_SchemaHandler::dropTable($table);
      }
      else {
        $ctx->log->warning("dropTableIfEmpty($table): Found data. Preserved table.");
      }
    }

    return TRUE;
  }

  /**
   * Rebuild Multilingual Schema.
   * @param CRM_Queue_TaskContext $ctx
   * @param string|null $version CiviCRM version to use if rebuilding multilingual schema
   *
   * @return bool
   */
  public static function rebuildMultilingalSchema($ctx, $version = NULL) {
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    if ($domain->locales) {
      $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
      CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales, $version);
    }
    return TRUE;
  }

}
