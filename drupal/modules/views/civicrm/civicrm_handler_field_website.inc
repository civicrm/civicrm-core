<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                 |
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
 * Field handler for website field
 *
 * @ingroup civicrm_field_handlers
 */
class civicrm_handler_field_website extends civicrm_handler_field {
  static $_websiteType;

  public function construct() {
    parent::construct();
    if (!self::$_websiteType) {
      if (!civicrm_initialize()) {
        return;
      }
      self::$_websiteType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Website', 'website_type_id');
    }
  }

  public function option_definition() {
    $options = parent::option_definition();
    $options['website_type'] = array('default' => 0);
    return $options;
  }

  public function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $websiteOptions = array(0 => 'Any');
    foreach (self::$_websiteType as $id => $type) {
      $websiteOptions[$id] = $type;
    }
    $form['website_type'] = array(
      '#type' => 'radios',
      '#title' => 'Website type for this field',
      '#options' => $websiteOptions,
      '#description' => t('Website type to be displayed for this field'),
      '#default_value' => $this->options['website_type'],
      '#fieldset' => 'location_choices',
    );
  }

  public function join_website($join = array()) {
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

  public function get_join() {
    $join = parent::get_join();
    $join = $this->join_website($join);
    return $join;
  }

  public function ensure_my_table() {
    if (!isset($this->table_alias)) {
      if (!method_exists($this->query, 'ensure_table')) {
        vpr_trace();
        exit;
      }
      $join = $this->get_join();
      if (empty($this->relationship)) {
        //https://www.drupal.org/node/2617032
        $this->table_alias = $this->query->add_table($this->table, $this->relationship, $join);
      }
      else {
        $this->table_alias = $this->query->ensure_table($this->table, $this->relationship, $join);
      }
    }
    return $this->table_alias;
  }

}
