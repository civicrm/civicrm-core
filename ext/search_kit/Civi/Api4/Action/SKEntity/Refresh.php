<?php

namespace Civi\Api4\Action\SKEntity;

use Civi\API\Request;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\Query\Api4SelectQuery;

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

    $apiParams = $display['saved_search_id.api_params'];
    // Add orderBy to api params
    foreach ($display['settings']['sort'] ?? [] as $item) {
      $apiParams['orderBy'][$item[0]] = $item[1];
    }
    // Set select clause to match display columns
    $select = [];
    foreach ($display['settings']['columns'] as $column) {
      foreach ($apiParams['select'] as $selectExpr) {
        if ($selectExpr === $column['key'] || str_ends_with($selectExpr, " AS {$column['key']}")) {
          $select[] = $selectExpr;
          continue 2;
        }
      }
    }
    $apiParams['select'] = $select;
    $api = Request::create($display['saved_search_id.api_entity'], 'get', $apiParams);
    $query = new Api4SelectQuery($api);
    $query->forceSelectId = FALSE;
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
