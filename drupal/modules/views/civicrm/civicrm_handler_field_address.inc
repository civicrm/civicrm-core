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
 * Generic field handler for address and location fields
 *
 * @ingroup civicrm_field_handlers
 */
class civicrm_handler_field_address extends civicrm_handler_field_location {
  static $_locationOps;
  function construct() {
    parent::construct();
    if (!self::$_locationOps) {
      if (!civicrm_initialize()) {
        return;
      }
      self::$_locationOps = array(0 => 'AND', 1 => 'OR');
    }
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['is_billing'] = array('default' => '');
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['is_billing'] = array(
      '#type' => 'checkbox',
      '#title' => 'Show only Billing Address record?',
      '#options' => array(0 => 'No', 1 => 'Yes'),
      '#description' => t('Check above box if you want only the <strong>Billing Address</strong> record displayed.'),
      '#default_value' => $this->options['is_billing'],
      '#fieldset' => 'location_choices',
    );
  }

  function ensure_my_table() {
    if (!isset($this->table_alias)) {
      if (!method_exists($this->query, 'ensure_table')) {
        vpr_trace();
        exit;
      }
      $extra = array();
      $extra = parent::location_extras();
      if (isset($this->options['is_billing']) && $this->options['is_billing']) {
        $extra[] = array(
          'value' => $this->options['is_billing'],
          'numeric' => TRUE,
          'field' => 'is_billing',
          'operator' => '=',
        );
      }
      if (isset($extra) && !empty($extra)) {
        $join              = $this->get_join();
        $join->extra       = $extra;
        $join->extra_type  = self::$_locationOps[$this->options['location_op']];
        $this->table_alias = $this->query->add_table($this->table, $this->relationship, $join);
      }
      else {
        $this->table_alias = $this->query->ensure_table($this->table, $this->relationship);
      }
    }
    return $this->table_alias;
  }
}


