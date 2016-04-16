<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                               |
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
 * Field handler for phone field
 *
 * @ingroup civicrm_field_handlers
 */
class civicrm_handler_field_phone extends civicrm_handler_field_location {
  static $_phoneType;
  function construct() {
    parent::construct();
    if (!self::$_phoneType) {
      if (!civicrm_initialize()) {
        return;
      }
      self::$_phoneType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    }
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['phone_type'] = array('default' => 0);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $phoneOptions = array(0 => 'Any');
    foreach (self::$_phoneType as $id => $type) {
      $phoneOptions[$id] = $type;
    }
    $form['phone_type'] = array(
      '#type' => 'radios',
      '#title' => 'Phone type for this field',
      '#options' => $phoneOptions,
      '#description' => t('Phone type to be displayed for this field'),
      '#default_value' => $this->options['phone_type'],
      '#fieldset' => 'location_choices',
    );
  }

  function ensure_my_table() {
    if (!isset($this->table_alias)) {
      if (!method_exists($this->query, 'ensure_table')) {
        vpr_trace();
        exit;
      }
      if (isset($this->options['phone_type']) && $this->options['phone_type']) {
        $join    = $this->get_join();
        $extra   = parent::location_extras();
        $extra[] = array(
          'value' => $this->options['phone_type'],
          'numeric' => TRUE,
          'field' => 'phone_type_id',
          'operator' => '=',
        );
        $join->extra       = $extra;
        $join->extra_type  = self::$_locationOps[$this->options['location_op']];
        $this->table_alias = $this->query->add_table($this->table, $this->relationship, $join);
      }
      else {
        $join              = $this->get_join();
        $join->extra       = parent::location_extras();
        $join->extra_type  = self::$_locationOps[$this->options['location_op']];
        $this->table_alias = $this->query->add_table($this->table, $this->relationship, $join);
      }
    }
    return $this->table_alias;
  }
}

