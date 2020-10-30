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

  public function upgrade_1000() {
    $this->ctx->log->info('Applying update 1000 - install schema.');
    // For early, early adopters who installed the extension pre-beta
    if (!CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE 'civicrm_search_display'")) {
      $this->executeSqlFile('sql/auto_install.sql');
    }
    CRM_Core_DAO::executeQuery("UPDATE civicrm_navigation SET url = 'civicrm/admin/search', name = 'search_kit' WHERE url = 'civicrm/search'");
    return TRUE;
  }

}
