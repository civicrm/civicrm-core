<?php

namespace Civi\Api4\Action\SKEntity;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 * Get the date the stored data was last refreshed for $ENTITY
 *
 * @package Civi\Api4\Action\SKEntity
 */
class GetRefreshDate extends AbstractAction {

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @throws \CRM_Core_Exception
   */
  public function _run(Result $result) {
    [, $displayName] = explode('_', $this->getEntityName(), 2);
    $tableName = _getSearchKitDisplayTableName($displayName);
    $dbPath = explode('/', parse_url(CIVICRM_DSN, PHP_URL_PATH));
    $dbName = end($dbPath);

    $result[] = [
      'refresh_date' => \CRM_Core_DAO::singleValueQuery("
        SELECT UPDATE_TIME
        FROM information_schema.tables
        WHERE TABLE_SCHEMA = '$dbName'
        AND TABLE_NAME = '$tableName'"),
    ];
  }

}
