<?php
use CRM_Search_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Search_Upgrader extends CRM_Search_Upgrader_Base {

  /**
   * Add menu item when enabled.
   */
  public function enable() {
    \Civi\Api4\Navigation::create(FALSE)
      ->addValue('parent_id:name', 'Search')
      ->addValue('label', E::ts('Search Kit'))
      ->addValue('name', 'search_kit')
      ->addValue('url', 'civicrm/admin/search')
      ->addValue('icon', 'crm-i fa-search-plus')
      ->addValue('has_separator', 2)
      ->addValue('weight', 99)
      ->addValue('permission', 'administer CiviCRM data')
      ->execute();
  }

  /**
   * Delete menu item when disabled.
   */
  public function disable() {
    \Civi\Api4\Navigation::delete(FALSE)
      ->addWhere('name', '=', 'search_kit')
      ->addWhere('domain_id', '=', 'current_domain')
      ->execute();
  }

  /**
   * Upgrade 1000 - install schema
   * @return bool
   */
  public function upgrade_1000(): bool {
    $this->ctx->log->info('Applying update 1000 - install schema.');
    // For early, early adopters who installed the extension pre-beta
    if (!CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE 'civicrm_search_display'")) {
      $this->executeSqlFile('sql/auto_install.sql');
    }
    CRM_Core_DAO::executeQuery("UPDATE civicrm_navigation SET url = 'civicrm/admin/search', name = 'search_kit' WHERE url = 'civicrm/search'");
    return TRUE;
  }

  /**
   * Upgrade 1001 - normalize search display column keys
   * @return bool
   */
  public function upgrade_1001(): bool {
    $this->ctx->log->info('Applying update 1001 - normalize search display columns.');
    $savedSearches = \Civi\Api4\SavedSearch::get(FALSE)
      ->addWhere('api_params', 'IS NOT NULL')
      ->addChain('displays', \Civi\Api4\SearchDisplay::get()->addWhere('saved_search_id', '=', '$id'))
      ->execute();
    foreach ($savedSearches as $savedSearch) {
      $newAliases = [];
      foreach ($savedSearch['api_params']['select'] ?? [] as $i => $select) {
        if (strstr($select, '(') && !strstr($select, ' AS ')) {
          $alias = CRM_Utils_String::munge(str_replace(')', '', $select), '_', 256);
          $newAliases[$select] = $alias;
          $savedSearch['api_params']['select'][$i] = $select . ' AS ' . $alias;
        }
      }
      if ($newAliases) {
        \Civi\Api4\SavedSearch::update(FALSE)
          ->setValues(array_diff_key($savedSearch, ['displays' => 0]))
          ->execute();
      }
      foreach ($savedSearch['displays'] ?? [] as $display) {
        foreach ($display['settings']['columns'] ?? [] as $c => $column) {
          $key = $newAliases[$column['expr']] ?? $column['expr'];
          unset($display['settings']['columns'][$c]['expr']);
          $display['settings']['columns'][$c]['key'] = explode(' AS ', $key)[1] ?? $key;
          $display['settings']['columns'][$c]['type'] = 'field';
        }
        \Civi\Api4\SearchDisplay::update(FALSE)
          ->setValues($display)
          ->execute();
      }
    }
    return TRUE;
  }

  /**
   * Upgrade 1002 - embellish search display link data
   * @return bool
   */
  public function upgrade_1002(): bool {
    $this->ctx->log->info('Applying update 1002 - embellish search display link data.');
    $displays = \Civi\Api4\SearchDisplay::get(FALSE)
      ->setSelect(['id', 'settings'])
      ->execute();
    foreach ($displays as $display) {
      $update = FALSE;
      foreach ($display['settings']['columns'] ?? [] as $c => $column) {
        if (!empty($column['link'])) {
          $display['settings']['columns'][$c]['link'] = ['path' => $column['link']];
          $update = TRUE;
        }
      }
      if ($update) {
        \Civi\Api4\SearchDisplay::update(FALSE)
          ->setValues($display)
          ->execute();
      }
    }
    return TRUE;
  }

  /**
   * Upgrade 1003 - update APIv4 join syntax in saved searches
   * @return bool
   */
  public function upgrade_1003(): bool {
    $this->ctx->log->info('Applying 1003 - update APIv4 join syntax in saved searches.');
    $savedSearches = \Civi\Api4\SavedSearch::get(FALSE)
      ->addSelect('id', 'api_params')
      ->addWhere('api_params', 'IS NOT NULL')
      ->execute();
    foreach ($savedSearches as $savedSearch) {
      foreach ($savedSearch['api_params']['join'] ?? [] as $i => $join) {
        $savedSearch['api_params']['join'][$i][1] = empty($join[1]) ? 'LEFT' : 'INNER';
      }
      if (!empty($savedSearch['api_params']['join'])) {
        \Civi\Api4\SavedSearch::update(FALSE)
          ->setValues($savedSearch)
          ->execute();
      }
    }
    return TRUE;
  }

  /**
   * Upgrade 1004 - fix menu permission.
   * @return bool
   */
  public function upgrade_1004(): bool {
    $this->ctx->log->info('Applying update 1004 - fix menu permission.');
    CRM_Core_DAO::executeQuery("UPDATE civicrm_navigation SET permission = 'administer CiviCRM data' WHERE url = 'civicrm/admin/search'");
    return TRUE;
  }

  /**
   * Upgrade 1005 - add acl_bypass column.
   * @return bool
   */
  public function upgrade_1005(): bool {
    $this->ctx->log->info('Applying update 1005 - add acl_bypass column.');
    $this->addTask('Add Cancel Button Setting to the Profile', 'addColumn',
      'civicrm_search_display', 'acl_bypass', "tinyint DEFAULT 0 COMMENT 'Skip permission checks and ACLs when running this display.'");
    return TRUE;
  }

  /**
   * Add a column to a table if it doesn't already exist
   *
   * FIXME: Move to a shared class, delegate to CRM_Upgrade_Incremental_Base::addColumn
   *
   * @param string $table
   * @param string $column
   * @param string $properties
   *
   * @return bool
   */
  public static function addColumn($table, $column, $properties) {
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists($table, $column, FALSE)) {
      $query = "ALTER TABLE `$table` ADD COLUMN `$column` $properties";
      CRM_Core_DAO::executeQuery($query, [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

}
