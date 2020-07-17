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
      ->addValue('label', E::ts('Create Search...'))
      ->addValue('name', 'create_search')
      ->addValue('url', 'civicrm/search')
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
      ->addWhere('name', '=', 'create_search')
      ->addWhere('domain_id', '=', 'current_domain')
      ->execute();
  }

}
