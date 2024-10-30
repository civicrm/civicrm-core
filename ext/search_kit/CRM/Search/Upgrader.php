<?php
use CRM_Search_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Search_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Upgrade 1001 - normalize search display column keys
   * @return bool
   */
  public function upgrade_1001(): bool {
    // If you upgrade direct from 5.35 to 5.40+ then upgrade_1001 which is
    // from 5.36 triggers api4 to use the field that gets added in 5.40.
    // So rather than rewrite all these upgrades in straight SQL, let's just
    // add the field now, and then upgrade_1005 will be a no-op if upgrading
    // from 5.36 or earlier.
    $this->ctx->log->info('Applying update 1005 before 1001 to avoid chicken and egg problem.');
    $this->addColumn('civicrm_search_display', 'acl_bypass', "tinyint DEFAULT 0 COMMENT 'Skip permission checks and ACLs when running this display.'");

    $this->ctx->log->info('Applying update 1001 - normalize search display columns.');
    $savedSearches = \Civi\Api4\SavedSearch::get(FALSE)
      ->addWhere('api_params', 'IS NOT NULL')
      ->addChain('displays', \Civi\Api4\SearchDisplay::get()->addWhere('saved_search_id', '=', '$id'))
      ->execute();
    foreach ($savedSearches as $savedSearch) {
      $newAliases = [];
      foreach ($savedSearch['api_params']['select'] ?? [] as $i => $select) {
        if (str_contains($select, '(') && !str_contains($select, ' AS ')) {
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
   * Upgrade 1005 - add acl_bypass column.
   * @return bool
   */
  public function upgrade_1005(): bool {
    $this->ctx->log->info('Applying update 1005 - add acl_bypass column.');
    $this->addColumn('civicrm_search_display', 'acl_bypass', "tinyint DEFAULT 0 COMMENT 'Skip permission checks and ACLs when running this display.'");
    return TRUE;
  }

  /**
   * Upgrade 1006 - add image column type
   * @return bool
   */
  public function upgrade_1006(): bool {
    $this->ctx->log->info('Applying update 1006 - add image column type.');
    $displays = \Civi\Api4\SearchDisplay::get(FALSE)
      ->setSelect(['id', 'settings'])
      ->execute();
    foreach ($displays as $display) {
      $update = FALSE;
      foreach ($display['settings']['columns'] ?? [] as $c => $column) {
        if (!empty($column['image'])) {
          $display['settings']['columns'][$c]['type'] = 'image';
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
   * Add SearchSegment table
   * @return bool
   */
  public function upgrade_1007(): bool {
    $this->ctx->log->info('Applying update 1007 - add SearchSegment table.');
    if (!CRM_Core_DAO::checkTableExists('civicrm_search_segment')) {
      $createTable = "
CREATE TABLE `civicrm_search_segment` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique SearchSegment ID',
  `name` varchar(255) NOT NULL COMMENT 'Unique name',
  `label` varchar(255) NOT NULL COMMENT 'Label for identifying search segment (will appear as name of calculated field)',
  `description` varchar(255) COMMENT 'Description will appear when selecting SearchSegment in the fields dropdown.',
  `entity_name` varchar(255) NOT NULL COMMENT 'Entity for which this set is used.',
  `items` text COMMENT 'All items in set',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `UI_name`(name)
)
ENGINE=InnoDB ROW_FORMAT=DYNAMIC";
      CRM_Core_DAO::executeQuery($createTable, [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

}
