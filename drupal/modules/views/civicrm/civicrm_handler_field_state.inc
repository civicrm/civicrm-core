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
 * Field handler to provide acess control for the state field (which is a lookup)
 *
 * @ingroup civicrm_field_handlers
 */
class civicrm_handler_field_state extends civicrm_handler_field_address {
  static $_states;
  static $_states_full;
  public function construct() {
    parent::construct();
    if (!self::$_states || !self::$_states_full) {
      if (!civicrm_initialize()) {
        return;
      }
      require_once 'CRM/Core/PseudoConstant.php';
      self::$_states = CRM_Core_PseudoConstant::stateProvinceAbbreviation();
      self::$_states_full = CRM_Core_PseudoConstant::stateProvince();
    }
  }

  public function render($values) {
    $sid = $values->{$this->field_alias};
    if (empty($sid) || (int) $sid <= 0) {
      return NULL;
    }

    if ($this->options['prov_display'] == 1) {
      return isset(self::$_states[$values->{$this->field_alias}])
        ? self::$_states[$values->{$this->field_alias}] : NULL;
    }
    else {
      return isset(self::$_states_full[$values->{$this->field_alias}])
        ? self::$_states_full[$values->{$this->field_alias}] : NULL;
    }
  }

  public function option_definition() {
    $options = parent::option_definition();
    $options['prov_display'] = array('default' => '');
    return $options;
  }

  public function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['prov_display'] = array(
      '#type' => 'radios',
      '#title' => 'Display complete name or abbreviation',
      '#options' => array(1 => 'Display state/province abbreviations', 2 => 'Display full state/province name'),
      '#description' => t('Display full or abbreviated province name'),
      '#default_value' => $this->options['prov_display'],
    );
  }

}
