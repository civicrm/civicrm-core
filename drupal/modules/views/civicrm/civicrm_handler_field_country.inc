<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
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
 * Heavily based on anonymous code contribution. No claim.
 *
 * civicrm_handler_field_country.inc
 * Displays Country after grabbing the label from the database
 *
 */
class civicrm_handler_field_country extends civicrm_handler_field_address {
  static $_countries;
  static $_countries_full;
  public function construct() {
    parent::construct();
    if (!self::$_countries) {
      if (!civicrm_initialize()) {
        return;
      }
      require_once 'CRM/Core/PseudoConstant.php';
      self::$_countries = CRM_Core_PseudoConstant::countryIsoCode();
      self::$_countries_full = CRM_Core_PseudoConstant::country();
    }
  }

  public function render($values) {
    $sid = $values->{$this->field_alias};
    if (empty($sid) ||
      (int ) $sid <= 0
    ) {
      return NULL;
    }

    if ($this->options['country_display'] == 1) {
      return self::$_countries[$values->{$this->field_alias}];
    }
    else {
      return self::$_countries_full[$values->{$this->field_alias}];
    }
  }

  public function option_definition() {
    $options = parent::option_definition();
    $options['country_display'] = array('default' => '');
    return $options;
  }

  public function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['country_display'] = array(
      '#type' => 'radios',
      '#title' => 'Type of display for country field',
      '#options' => array(1 => 'Display two letter country code', 2 => 'Display full country name'),
      '#description' => t('Display full or abbreviated country name'),
      '#default_value' => $this->options['country_display'],
    );
  }

}
