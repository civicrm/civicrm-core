<?php

namespace Civi\Api4\Action\SKEntity;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Search\SKEntityGenerator;
use CRM_Search_ExtensionUtil as E;

/**
 * Store the results of a SearchDisplay as a SQL table.
 *
 * For displays of type `entity` which save to a DB table
 * rather than outputting anything to the user.
 *
 * @method $this setMode(?string $chain)
 * @method ?string getMode()
 *
 * @package Civi\Api4\Action\SKEntity
 */
class Refresh extends AbstractAction {

  /**
   * @var string|null
   * @optionsCallback getModeOptions
   *
   * One of:
   *   truncate: Remove all records from the table. Refill it.
   *   swap: Prepare a new table in the background. Then atomically swap with the old table.
   *   NULL: Let the system choose a default.
   */
  protected ?string $mode = NULL;

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @throws \CRM_Core_Exception
   */
  public function _run(Result $result) {
    [, $displayName] = explode('_', $this->getEntityName(), 2);
    $display = \Civi\Api4\SearchDisplay::get(FALSE)
      ->setSelect(['settings', 'saved_search_id.api_entity', 'saved_search_id.api_params'])
      ->addWhere('type', '=', 'entity')
      ->addWhere('name', '=', $displayName)
      ->execute()->single();

    if (($display['settings']['data_mode'] ?? 'table') !== 'table') {
      return;
    }

    // Prepare a sketch of the process. Ensure metadata is well-formed.
    $query = (new SKEntityGenerator())->createQuery($display['saved_search_id.api_entity'], $display['saved_search_id.api_params'], $display['settings']);
    $sql = $query->getSql();
    $finalTable = _getSearchKitDisplayTableName($displayName);
    $columnSpecs = array_column($display['settings']['columns'], 'spec');
    $columns = implode(', ', array_column($columnSpecs, 'name'));

    // Only one process should actually refresh this entity (at a given time).
    $lock = \Civi::lockManager()->acquire("data.skentity." . $display['id'], 1);
    if (!$lock->isAcquired()) {
      throw new \Civi\Search\Exception\RefreshInProgressException(sprintf('Refresh (%s) is already in progress', $this->getEntityName()));
    }
    $releaseLock = \CRM_Utils_AutoClean::with([$lock, 'release']);

    // Go!
    $mode = $this->getMode() ?: \Civi::settings()->get('search_kit_entity_refresh');
    switch ($mode) {
      // Build a new table with full data. Swap-in the new table and drop the old one.
      case 'swap':
        $newTable = \CRM_Utils_SQL_TempTable::build()->setDurable()->setAutodrop(FALSE)->getName();
        $junkTable = \CRM_Utils_SQL_TempTable::build()->setDurable()->setAutodrop(FALSE)->getName();

        // The schema 'CREATE' logic is entwined with an event listener (hard to call). We'll just imitate the output.
        \CRM_Core_DAO::executeQuery("CREATE TABLE `$newTable` LIKE `$finalTable`");
        \CRM_Core_DAO::executeQuery("INSERT INTO `$newTable` ($columns) $sql");
        \CRM_Core_DAO::executeQuery(sprintf('RENAME TABLE `%s` TO `%s`, `%s` TO `%s`',
          $finalTable, $junkTable,
          $newTable, $finalTable
        ));
        \CRM_Core_DAO::executeQuery(sprintf('DROP TABLE `%s`', $junkTable));
        break;

      // case 'sync':
      //   There is a limitation with both 'swap' and 'truncate' -- they break inbound FKs
      //   and fire triggers. If that's a problem, then another option would be... put the
      //   query-results into a TEMPORARY table, and then synchronize with the main table
      //   (INSERT/UPDATE/DELETE). However, doing this would require supporting data
      //   (suitable PK columns and modified_date or revision-id).

      // Remove all data from the table and re-fill it.
      case 'truncate':
      case '':
      default:
        \CRM_Core_DAO::executeQuery("TRUNCATE TABLE `$finalTable`");
        \CRM_Core_DAO::executeQuery("INSERT INTO `$finalTable` ($columns) $sql");
        break;
    }

    // All done
    $result[] = [
      'refresh_date' => \CRM_Core_DAO::singleValueQuery("SELECT NOW()"),
    ];
  }

  /**
   * Get a list of layout options.
   *
   * @return array
   *   Array (string $machineName => string $label).
   */
  public static function getModeOptions(): array {
    return [
      'swap' => E::ts('Build anew and swap'),
      'truncate' => E::ts('Truncate and re-fill'),
    ];
  }

}
