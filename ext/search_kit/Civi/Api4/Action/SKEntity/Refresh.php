<?php

namespace Civi\Api4\Action\SKEntity;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Search\SKEntityGenerator;

/**
 * Store the results of a SearchDisplay as a SQL table.
 *
 * For displays of type `entity` which save to a DB table
 * rather than outputting anything to the user.
 *
 * @package Civi\Api4\Action\SKEntity
 */
class Refresh extends AbstractAction {

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

    // Build a new table with full data. Swap-in the new table and drop the old one.
    //
    // NOTE: This protocol destroys inbound FKs. But the prior protocol (TRUNCATE + INSERT SELECT)
    // also destroyed inbound FKs. To keep inbound FKs, you would probably wind up working on
    // something more incremental. (Maybe put new data into TEMPORARY table - and use INSERT/DELETE/UPDATE
    // to sync to the real table. But that requires guaranteeing the presence of a stable PK column(s),
    // and it would change the default ordering over time.)

    // Prepare a sketch of the process. Ensure metadata is well-formed.
    $sql = (new SKEntityGenerator())->createQuery($display['saved_search_id.api_entity'], $display['saved_search_id.api_params'], $display['settings']);
    $finalTable = _getSearchKitDisplayTableName($displayName);
    $columnSpecs = array_column($display['settings']['columns'], 'spec');
    $columns = implode(', ', array_column($columnSpecs, 'name'));
    $newTable = \CRM_Utils_SQL_TempTable::build()->setDurable()->setAutodrop(FALSE)->getName();
    $junkTable = \CRM_Utils_SQL_TempTable::build()->setDurable()->setAutodrop(FALSE)->getName();

    // Only one process should actually refresh this entity (at a given time).
    $lock = \Civi::lockManager()->acquire("data.skentity." . $display['id'], 1);
    if (!$lock->isAcquired()) {
      throw new \Civi\Search\Exception\RefreshInProgressException(sprintf('Refresh (%s) is already in progress', $this->getEntityName()));
    }
    $releaseLock = \CRM_Utils_AutoClean::with([$lock, 'release']);

    // Go!
    \CRM_Core_DAO::executeQuery("CREATE TABLE `$newTable` LIKE `$finalTable`");
    \CRM_Core_DAO::executeQuery("INSERT INTO `$newTable` ($columns) $sql");
    \CRM_Core_DAO::executeQuery(sprintf('RENAME TABLE `%s` TO `%s`, `%s` TO `%s`',
      $finalTable, $junkTable,
      $newTable, $finalTable
    ));
    \CRM_Core_DAO::executeQuery(sprintf('DROP TABLE `%s`', $junkTable));

    // All done
    $result[] = [
      'refresh_date' => \CRM_Core_DAO::singleValueQuery("SELECT NOW()"),
    ];
  }

}
