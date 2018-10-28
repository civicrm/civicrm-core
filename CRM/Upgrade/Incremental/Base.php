<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5  .alpha1                                         |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

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
    $queue = CRM_Queue_Service::singleton()->load(array(
      'type' => 'Sql',
      'name' => CRM_Upgrade_Form::QUEUE_NAME,
    ));

    $args = func_get_args();
    $title = array_shift($args);
    $funcName = array_shift($args);
    $task = new CRM_Queue_Task(
      array(get_class($this), $funcName),
      $args,
      $title
    );
    $queue->createItem($task, array('weight' => -1));
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
    $processors = civicrm_api3('PaymentProcessor', 'getcount', array('payment_processor_type_id' => $name));
    if (empty($processors['result'])) {
      $result = civicrm_api3('PaymentProcessorType', 'get', array(
        'name' => $name,
        'return' => 'id',
      ));
      if (!empty($result['id'])) {
        civicrm_api3('PaymentProcessorType', 'delete', array('id' => $result['id']));
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
   * @return bool
   */
  public static function addColumn($ctx, $table, $column, $properties, $localizable = FALSE) {
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    $queries = array();
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists($table, $column)) {
      if ($domain->locales) {
        if ($localizable) {
          $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
          foreach ($locales as $locale) {
            if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists($table, "{$column}_{$locale}")) {
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
        CRM_Core_DAO::executeQuery($query, array(), TRUE, NULL, FALSE, FALSE);
      }
    }
    if ($domain->locales) {
      $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
      CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales, NULL, TRUE);
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
        array(), TRUE, NULL, FALSE, FALSE);
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
    $tables = array($table => (array) $column);
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
   * Rebuild Multilingual Schema.
   * @param CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function rebuildMultilingalSchema($ctx) {
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    if ($domain->locales) {
      $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
      CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales);
    }
    return TRUE;
  }

}
