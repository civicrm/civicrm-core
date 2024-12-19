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

    $query = (new SKEntityGenerator())->createQuery($display['saved_search_id.api_entity'], $display['saved_search_id.api_params'], $display['settings']);
    $sql = $query->getSql();
    $tableName = _getSearchKitDisplayTableName($displayName);
    $columnSpecs = array_column($display['settings']['columns'], 'spec');
    $columns = implode(', ', array_column($columnSpecs, 'name'));
    \CRM_Core_DAO::executeQuery("TRUNCATE TABLE `$tableName`");
    \CRM_Core_DAO::executeQuery("INSERT INTO `$tableName` ($columns) $sql");
    $result[] = [
      'refresh_date' => \CRM_Core_DAO::singleValueQuery("SELECT NOW()"),
    ];
  }

}
