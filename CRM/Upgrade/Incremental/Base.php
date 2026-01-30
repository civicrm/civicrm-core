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

use Civi\Core\Exception\DBQueryException;
use Civi\Core\SettingsBag;

/**
 * Base class for incremental upgrades
 */
class CRM_Upgrade_Incremental_Base {
  const BATCH_SIZE = 5000;

  /**
   * @var string|null
   */
  protected $majorMinor;

  /**
   * Get the major and minor version for this class (based on English-style class name).
   *
   * @return string
   *   Ex: '5.34' or '4.7'
   */
  public function getMajorMinor() {
    if (!$this->majorMinor) {
      $className = explode('_', static::CLASS);
      $numbers = preg_split("/([[:upper:]][[:lower:]]+)/", array_pop($className), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
      $major = CRM_Utils_EnglishNumber::toInt(array_shift($numbers));
      $minor = CRM_Utils_EnglishNumber::toInt(implode('', $numbers));
      $this->majorMinor = $major . '.' . $minor;
    }
    return $this->majorMinor;
  }

  /**
   * Get a list of revisions (PATCH releases) related to this class.
   *
   * @return array
   *   Ex: ['4.5.6', '4.5.7']
   * @throws \ReflectionException
   */
  public function getRevisionSequence() {
    $revList = [];

    $sqlGlob = implode(DIRECTORY_SEPARATOR, [dirname(__FILE__), 'sql', $this->getMajorMinor() . '.*.mysql.tpl']);
    $sqlFiles = glob($sqlGlob);
    foreach ($sqlFiles as $file) {
      $revList[] = basename($file, '.mysql.tpl');
    }

    $c = new ReflectionClass(static::class);
    foreach ($c->getMethods() as $method) {
      /** @var \ReflectionMethod $method */
      if (preg_match(';^upgrade_([0-9_alphabeta]+)$;', $method->getName(), $m)) {
        $revList[] = str_replace('_', '.', $m[1]);
      }
    }

    $revList = array_unique($revList);
    usort($revList, 'version_compare');
    return $revList;
  }

  /**
   * Compute any messages which should be displayed before upgrade.
   *
   * Downstream classes should implement this method to generate their messages.
   *
   * This method will be invoked multiple times. Implementations MUST consult the `$rev`
   * before deciding what messages to add. See the examples linked below.
   *
   * @see \CRM_Upgrade_Incremental_php_FiveTwenty::setPreUpgradeMessage()
   *
   * @param string $preUpgradeMessage
   *   Accumulated list of messages. Alterable.
   * @param string $rev
   *   The incremental version number. (Called repeatedly, once for each increment.)
   *
   *   Ex: Suppose the system upgrades from 5.7.3 to 5.10.0. The method FiveEight::setPreUpgradeMessage()
   *   will be called for each increment of '5.8.*' ('5.8.alpha1' => '5.8.beta1' =>  '5.8.0').
   * @param null $currentVer
   *   This is the penultimate version targeted by the upgrader.
   *   Equivalent to CRM_Utils_System::version().
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
  }

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * Downstream classes should implement this method to generate their messages.
   *
   * This method will be invoked multiple times. Implementations MUST consult the `$rev`
   * before deciding what messages to add. See the examples linked below.
   *
   * @see \CRM_Upgrade_Incremental_php_FiveTwentyOne::setPostUpgradeMessage()
   *
   * @param string $postUpgradeMessage
   *   Accumulated list of messages. Alterable.
   * @param string $rev
   *   The incremental version number. (Called repeatedly, once for each increment.)
   *
   *   Ex: Suppose the system upgrades from 5.7.3 to 5.10.0. The method FiveEight::setPreUpgradeMessage()
   *   will be called for each increment of '5.8.*' ('5.8.alpha1' => '5.8.beta1' =>  '5.8.0').
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
   * Add a task to store a snapshot of some data (if upgrade-snapshots are supported).
   *
   * If there is a large amount of data, this may actually add multiple tasks.
   *
   * Ex :$this->addSnapshotTask('event_dates', CRM_Utils_SQL_Select::from('civicrm_event')
   *      ->select('id, start_date, end_date'));
   *
   * @param string $name
   *   Logical name for the snapshot. This will become part of the table.
   * @param \CRM_Utils_SQL_Select $select
   * @throws \CRM_Core_Exception
   */
  protected function addSnapshotTask(string $name, CRM_Utils_SQL_Select $select): void {
    CRM_Upgrade_Snapshot::createTableName('civicrm', $this->getMajorMinor(), $name);
    // ^^ To simplify QA -- we should always throw an exception for bad snapshot names, even if the local policy doesn't use snapshots.

    if (!empty(CRM_Upgrade_Snapshot::getActivationIssues())) {
      return;
    }

    $queue = CRM_Queue_Service::singleton()->load([
      'type' => 'Sql',
      'name' => CRM_Upgrade_Form::QUEUE_NAME,
    ]);
    foreach (CRM_Upgrade_Snapshot::createTasks('civicrm', $this->getMajorMinor(), $name, $select) as $task) {
      $queue->createItem($task, ['weight' => -1]);
    }
  }

  /**
   * Add a task to activate an extension. It will use the full, normal installation process
   * (invoking `hook_install`, `hook_enable`, and so on). To ensure that the installation process
   * can rely on regular core services and APIs, it will run after the core-upgrade-steps.
   *
   * This is more suited to green-field extensions (which started life as an extension).
   * If you have a brown-field extension which doesn't have install-logic (i.e. it arises from
   * rearranging pre-existing core-core functionality), then consider `addSimpleExtensionTask()`.
   *
   * @param string $title
   * @param string[] $keys
   *   List of extensions to enable.
   * @param int $weight
   *   A weight > 1500 will install after extension upgrades run. Do this for brand-new extensions.
   *   A weight < 1500 will install before extension upgrades. Do this if the extension may
   *   have previously been enabled.
   */
  protected function addExtensionTask(string $title, array $keys, int $weight = 2000): void {
    Civi::queue(CRM_Upgrade_Form::QUEUE_NAME)->createItem(
      new CRM_Queue_Task([static::CLASS, 'enableExtension'], [$keys], $title),
      ['weight' => $weight]
    );
  }

  /**
   * Add a task to uninstall an extension. It will use the full, uninstallation process
   * (invoking `hook_uninstall`, `hook_disable`, and so on).
   *
   * @param string $title
   * @param string[] $keys
   *   List of extensions to uninstall.
   * @param int $weight
   */
  protected function addUninstallTask(string $title, array $keys, int $weight = 1000): void {
    Civi::queue(CRM_Upgrade_Form::QUEUE_NAME)->createItem(
      new CRM_Queue_Task([static::CLASS, 'uninstallExtension'], [$keys], $title),
      ['weight' => $weight]
    );
  }

  /**
   * Add a task to activate an extension. It will use a simple (low-tech) installation process
   * (skipping events like `hook_install`; instead, it merely updates `civicrm_extension` and
   * `CRM_Extension_ClassLoader`). The extension should not now (or in the future) use
   * `hook_install`. Simple installations can run at any point during the upgrade process.
   *
   * This is more suited to brown-field extensions (which arise from rearranging pre-existing
   * core-core functionality). If you have a green-field extension (which has always been an
   * extension), then consider `addExtensionTask()` instead.
   *
   * @param string $title
   * @param string|string[] $keys
   *   List of extensions to enable.
   */
  protected function addSimpleExtensionTask(string $title, $keys): void {
    $this->addTask($title, 'enableSimpleExtension', $keys);
  }

  /**
   * This callback is used to enable one or more extensions which have no install or upgrade code,
   * and whose autoloaders are needed right away.
   *
   * It was written to facilitate migrating core code into extensions.
   * Moving a class into an extension means it is no longer loaded by the core autoloader.
   * Upgrade code that relies on it could crash if classes disappear during the upgrade,
   * so this function sets the extension status to enabled and installs its autoloader;
   * both of which are important depending on the upgrade interface:
   * - The web UI does each step as a separate ajax request, so inserting/enabling the extension in the db
   * ensures it is loaded on subsequent requests.
   * - The CLI upgrader does everything in a single request so its autoloader should be installed right away.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string|array $keys
   * @return bool
   * @throws CRM_Extension_Exception
   * @throws DBQueryException
   */
  public static function enableSimpleExtension(CRM_Queue_TaskContext $ctx, $keys): bool {
    $keys = (array) $keys;

    // Find out current situation
    $system = CRM_Extension_System::singleton();
    $statuses = CRM_Utils_SQL_Select::from('civicrm_extension')
      ->select(['full_name, is_active'])
      ->execute(NULL, FALSE)
      ->fetchAll();
    $byStatus = CRM_Utils_Array::index(['is_active', 'full_name'], $statuses);
    $disabled = array_intersect($keys, array_keys($byStatus[0] ?? []));
    $uninstalled = array_diff($keys, array_keys($byStatus[0] ?? []), array_keys($byStatus[1] ?? []));

    // Make a plan
    $toUpdate = $disabled;
    $toInsert = [];
    foreach ($uninstalled as $key) {
      $info = $system->getMapper()->keyToInfo($key);
      $toInsert[] = [
        'full_name' => $info->key,
        'type' => $info->type,
        'name' => $info->name,
        'label' => $info->label,
        'file' => $info->file,
        'is_active' => 1,
      ];
    }

    // Execute the plan
    if ($toUpdate) {
      $updateSql = 'UPDATE civicrm_extension SET is_active = 1 WHERE full_name IN ("' . implode('", "', array_keys($toUpdate)) . '")';
      CRM_Core_DAO::executeQuery($updateSql, [], TRUE, NULL, FALSE, FALSE);
    }
    if ($toInsert) {
      $insertSql = CRM_Utils_SQL_Insert::into('civicrm_extension')
        ->rows($toInsert)
        ->toSQL();
      CRM_Core_DAO::executeQuery($insertSql, [], TRUE, NULL, FALSE, FALSE);
    }
    foreach (array_merge($disabled, $uninstalled) as $key) {
      $info = $system->getMapper()->keyToInfo($key);
      $path = $system->getMapper()->keyToPath($key);
      $system->getClassLoader()->installExtension($info, dirname($path));
    }
    return TRUE;
  }

  /**
   * @param \CRM_Queue_TaskContext $ctx
   * @param string[] $keys
   *   List of extensions to enable.
   * @return bool
   */
  public static function enableExtension(CRM_Queue_TaskContext $ctx, array $keys): bool {
    // The `enableExtension` has a very high value of `weight`, so this runs after all
    // core DB schema updates have been resolved. We can use high-level services.

    CRM_Upgrade_DispatchPolicy::assertActive('upgrade.finish');

    $manager = CRM_Extension_System::singleton()->getManager();
    $manager->enable($manager->findInstallRequirements($keys));

    // Hrm, `enable()` normally does these things... but not during upgrade...
    // Note: A good test-scenario is to install 5.45; enable logging and CiviGrant; disable searchkit+afform; then upgrade to 5.47.
    $schema = new CRM_Logging_Schema();
    if ($schema->isEnabled()) {
      $schema->fixSchemaDifferences();
    }

    Civi::rebuild(['*' => TRUE, 'triggers' => FALSE, 'sessions' => FALSE])->execute();
    // sessionReset is FALSE because upgrade status/postUpgradeMessages are needed by the page. We reset later in doFinish().

    return TRUE;
  }

  /**
   * @param \CRM_Queue_TaskContext $ctx
   * @param string[] $extensionKeys
   *   List of extensions to enable.
   * @return bool
   */
  public static function uninstallExtension(CRM_Queue_TaskContext $ctx, array $extensionKeys): bool {
    // Assert this task was run with sufficient weight to use high-level services.
    CRM_Upgrade_DispatchPolicy::assertActive('upgrade.finish');

    $manager = CRM_Extension_System::singleton()->getManager();
    $manager->disable($extensionKeys);
    $manager->uninstall($extensionKeys);

    Civi::rebuild(['*' => TRUE, 'triggers' => FALSE, 'sessions' => FALSE])->execute();
    // sessionReset is FALSE because upgrade status/postUpgradeMessages are needed by the page. We reset later in doFinish().

    return TRUE;
  }

  /**
   * Remove a payment processor if not in use
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $name
   * @return bool
   * @throws \CRM_Core_Exception
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
   * Task to add or change a column definition, based on the php schema spec.
   *
   * @param $ctx
   * @param string $entityName
   * @param string $fieldName
   * @param array $fieldSpec
   *   As definied in the .entityType.php file for $entityName
   * @param string|null $position
   *   E.g. "AFTER `another_column_name`" or "FIRST"
   * @param string|null $version CiviCRM version to use if rebuilding multilingual schema
   * @param bool $triggerRebuild should we trigger the rebuild of the multilingual schema
   *
   * @return bool
   * @throws CRM_Core_Exception
   */
  public static function alterSchemaField($ctx, string $entityName, string $fieldName, array $fieldSpec, ?string $position = NULL, ?string $version = NULL, bool $triggerRebuild = TRUE): bool {
    $tableName = Civi::entity($entityName)->getMeta('table');
    $fieldSql = Civi::schemaHelper()->arrayToSql($fieldSpec);
    if ($position) {
      $fieldSql .= " $position";
    }
    if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists($tableName, $fieldName)) {
      return self::alterColumn($ctx, $tableName, $fieldName, $fieldSql, !empty($fieldSpec['localizable']));
    }
    else {
      return self::addColumn($ctx, $tableName, $fieldName, $fieldSql, !empty($fieldSpec['localizable']), $version, $triggerRebuild);
    }
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
   * @param bool $triggerRebuild should we trigger the rebuild of the multilingual schema
   *
   * @return bool
   */
  public static function addColumn($ctx, $table, $column, $properties, $localizable = FALSE, $version = NULL, $triggerRebuild = TRUE) {
    $locales = CRM_Core_I18n::getMultilingual();
    $queries = [];
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists($table, $column, FALSE)) {
      if ($locales) {
        if ($localizable) {
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
      $schema = new CRM_Logging_Schema();
      if ($schema->isEnabled()) {
        $schema->fixSchemaDifferencesFor($table, [], TRUE);
      }
    }
    if ($locales && $triggerRebuild) {
      CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales, $version, TRUE);
    }
    return TRUE;
  }

  /**
   * Add the specified option group, gracefully if it already exists.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param array $params
   * @param array $options
   *
   * @return bool
   */
  public static function addOptionGroup(CRM_Queue_TaskContext $ctx, $params, $options): bool {
    $defaults = ['is_active' => 1];
    $optionDefaults = ['is_active' => 1];
    $optionDefaults['option_group_id'] = \CRM_Core_BAO_OptionGroup::ensureOptionGroupExists(array_merge($defaults, $params));

    foreach ($options as $option) {
      \CRM_Core_BAO_OptionValue::ensureOptionValueExists(array_merge($optionDefaults, $option));
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
   * Updated a message token within a template.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $workflowName
   * @param string $old
   * @param string $new
   * @param $version
   *
   * @return bool
   */
  public static function updateMessageToken($ctx, string $workflowName, string $old, string $new, $version):bool {
    $messageObj = new CRM_Upgrade_Incremental_MessageTemplates($version);
    if (!empty($workflowName)) {
      $messageObj->replaceTokenInTemplate($workflowName, $old, $new);
    }
    else {
      $messageObj->replaceTokenInMessageTemplates($old, $new);
    }
    return TRUE;
  }

  /**
   * Updated a message token within a scheduled reminder.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $old
   * @param string $new
   * @param $version
   *
   * @return bool
   */
  public static function updateActionScheduleToken($ctx, string $old, string $new, $version):bool {
    $messageObj = new CRM_Upgrade_Incremental_MessageTemplates($version);
    $messageObj->replaceTokenInActionSchedule($old, $new);
    return TRUE;
  }

  /**
   * Updated a message token within a label.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $old
   * @param string $new
   * @param $version
   *
   * @return bool
   */
  public static function updatePrintLabelToken($ctx, string $old, string $new, $version):bool {
    $messageObj = new CRM_Upgrade_Incremental_MessageTemplates($version);
    $messageObj->replaceTokenInPrintLabel($old, $new);
    return TRUE;
  }

  /**
   * Updated a message token within greeting options.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $old
   * @param string $new
   * @param $version
   *
   * @return bool
   */
  public static function updateGreetingOptions($ctx, string $old, string $new, $version):bool {
    $messageObj = new CRM_Upgrade_Incremental_MessageTemplates($version);
    $messageObj->replaceTokenInGreetingOptions($old, $new);
    return TRUE;
  }

  /**
   * Updated a currency in civicrm_currency and related configurations
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $old_name
   * @param string $new_name
   *
   * @return bool
   */
  public static function updateCurrencyName($ctx, string $old_name, string $new_name): bool {
    CRM_Core_DAO::executeQuery('UPDATE civicrm_currency SET name = %1 WHERE name = %2', [
      1 => [$new_name, 'String'],
      2 => [$old_name, 'String'],
    ]);

    $oid = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_option_group WHERE name = 'currencies_enabled'");
    if ($oid) {
      CRM_Core_DAO::executeQuery('UPDATE civicrm_option_value SET value = %1 WHERE value = %2 AND option_group_id = %3', [
        1 => [$new_name, 'String'],
        2 => [$old_name, 'String'],
        3 => [$oid, 'String'],
      ]);
    }

    CRM_Core_DAO::executeQuery('UPDATE civicrm_participant SET fee_currency = %1 WHERE fee_currency = %2', [
      1 => [$new_name, 'String'],
      2 => [$old_name, 'String'],
    ]);

    $tables = [
      'civicrm_contribution',
      'civicrm_contribution_page',
      'civicrm_contribution_recur',
      'civicrm_contribution_soft',
      'civicrm_event',
      'civicrm_financial_item',
      'civicrm_financial_trxn',
      'civicrm_grant',
      'civicrm_pcp',
      'civicrm_pledge_payment',
      'civicrm_pledge',
      'civicrm_product',
    ];

    foreach ($tables as $table) {
      CRM_Core_DAO::executeQuery('UPDATE %3 SET currency = %1 WHERE currency = %2', [
        1 => [$new_name, 'String'],
        2 => [$old_name, 'String'],
        3 => [$table, 'MysqlColumnNameOrAlias'],
      ]);
    }

    return TRUE;
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
  public static function updateSmartGroups($ctx, $actions) {
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
    $schema = new CRM_Logging_Schema();
    if ($schema->isEnabled()) {
      $schema->fixSchemaDifferencesFor($table, [], TRUE);
    }
    $locales = CRM_Core_I18n::getMultilingual();
    if ($locales) {
      CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales, NULL, TRUE);
    }
    return TRUE;
  }

  /**
   * Add a index to a table column.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $table
   * @param string|array $columns
   * @param string $prefix
   * @return bool
   */
  public static function addIndex($ctx, $table, $columns, $prefix = 'index') {
    $tables = [$table => (array) $columns];
    CRM_Core_BAO_SchemaHandler::createIndexes($tables, $prefix);

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
   * Drop a table if it exists.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $tableName
   * @return bool
   */
  public static function dropTable($ctx, $tableName) {
    CRM_Core_BAO_SchemaHandler::dropTable($tableName);
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
    $locales = CRM_Core_I18n::getMultilingual();
    if ($locales) {
      CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales, $version);
    }
    return TRUE;
  }

  public static function alterColumn($ctx, $table, $column, $properties, $localizable = FALSE): bool {
    $locales = CRM_Core_I18n::getMultilingual();
    $queries = [];
    if ($localizable && $locales) {
      foreach ($locales as $locale) {
        $queries[] = "ALTER TABLE `$table` CHANGE `{$column}_{$locale}` `{$column}_{$locale}` $properties";
      }
    }
    else {
      $queries[] = "ALTER TABLE `$table` CHANGE `$column` `$column` $properties";
    }
    foreach ($queries as $query) {
      try {
        CRM_Core_DAO::executeQuery($query, [], TRUE, NULL, FALSE, FALSE);
      }
      catch (DBQueryException $e) {
        throw new CRM_Core_Exception($e->getSQLErrorCode() . "\n" . $e->getDebugInfo());
      }
    }
    $schema = new CRM_Logging_Schema();
    if ($schema->isEnabled()) {
      $schema->fixSchemaDifferencesFor($table, [], TRUE);
    }
    if ($locales) {
      CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales, NULL, TRUE);
    }
    return TRUE;
  }

  /**
   * Installs a newly-added core entity.
   *
   * The entityType.php file should be copied into CRM/Upgrade/Incremental/schema
   * and prefixed with the version-added.
   *
   * @param $ctx
   * @param string $fileName
   * @return bool
   * @throws DBQueryException
   */
  public static function createEntityTable($ctx, string $fileName): bool {
    $filePath = __DIR__ . "/schema/$fileName";
    $entityDefn = include $filePath;
    if (empty($entityDefn)) {
      throw new \CRM_Core_Exception("Upgrader cannot create table. File $filePath is malformed or nonexistent.");
    }
    $sql = Civi::schemaHelper()->arrayToSql($entityDefn);
    CRM_Core_DAO::executeQuery($sql);
    return TRUE;
  }

}
