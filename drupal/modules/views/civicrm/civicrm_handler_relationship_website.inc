<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 * Field handler to provide relationship to CiviCRM Website.
 *
 * @ingroup civicrm_relationship_handlers
 */
class civicrm_handler_relationship_website extends civicrm_handler_relationship_location {

  static $_websiteType;
  function construct() {
    parent::construct();
    if (!self::$_websiteType) {
      if (!civicrm_initialize()) {
        return;
      }
      self::$_websiteType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Website', 'website_type_id');
    }
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['website_type'] = array('default' => 0);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $websiteOptions = array(0 => 'Any');
    foreach (self::$_websiteType as $id => $type) {
      $websiteOptions[$id] = $type;
    }
    $form['website_type'] = array(
      '#type' => 'radios',
      '#title' => 'Website type for this relationship',
      '#options' => $websiteOptions,
      '#description' => t('Website type to be used for this relationship'),
      '#default_value' => $this->options['website_type'],
      '#fieldset' => 'location_choices',
    );
  }

  function join_website($join = array()) {
    $extra = array();
    if (isset($join->extra)) {
      $extra = $join->extra;
    }
    if (isset($this->options['website_type']) && $this->options['website_type']) {
      $extra[] = array(
          'value' => $this->options['website_type'],
          'numeric' => TRUE,
          'field' => 'website_type_id',
          'operator' => '=',
      );
    }
    if (!empty($extra)) {
      $join->extra = $extra;
    }
    return $join;
  }

  function get_join() {
    $join = parent::get_join();
    $join = $this->join_website($join);
    return $join;
  }

  function ensure_my_table() {
    if (!isset($this->table_alias)) {
      if (!method_exists($this->query, 'ensure_table')) {
        vpr(t('Ensure my table called but query has no ensure_table method.'));
        return;
      }
      $join = $this->get_join();
      $this->table_alias = $this->query->ensure_table($this->table, $this->relationship, $join);
    }
    return $this->table_alias;
  }

  /**
   * Called to implement a relationship in a query.
   */
  function query() {
    // Just ensure the one table. This is briefer than parent::query(), which
    // attempts to create an additional join.
    $this->alias = $this->ensure_my_table();
    //register relationship
    $this->view->relationship[$this->options['id']]->alias = $this->alias;
    $this->query->relationships[$this->alias] = array(
        'link' => $this->relationship,
        'table' => $this->table,
        'base' => $this->table,
    );
  }
}

