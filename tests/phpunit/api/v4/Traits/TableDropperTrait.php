<?php

namespace api\v4\Traits;

trait TableDropperTrait {

  /**
   * @param $prefix
   */
  protected function dropByPrefix($prefix) {
    $sql = "SELECT CONCAT( 'DROP TABLE ', GROUP_CONCAT(table_name) , ';' ) " .
      "AS statement FROM information_schema.tables " .
      "WHERE table_name LIKE '%s%%' AND table_schema = DATABASE();";
    $sql = sprintf($sql, $prefix);
    $dropTableQuery = \CRM_Core_DAO::executeQuery($sql);
    $dropTableQuery->fetch();
    $dropTableQuery = $dropTableQuery->statement;

    if ($dropTableQuery) {
      \CRM_Core_DAO::executeQuery($dropTableQuery);
    }
  }

}
