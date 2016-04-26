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
 * Field handler to provide a formatted monetary string
 *
 * @ingroup civicrm_field_handlers
 */
class civicrm_handler_field_money extends views_handler_field {
  function construct() {
    parent::construct();
    if (!civicrm_initialize()) {
      return;
    }
    require_once 'CRM/Utils/Money.php';

    if ($this->definition['currency field']) {
      $this->additional_fields['currency'] = array('field' => $this->definition['currency field']);
    }
  }

  function render($values) {
    $value = $this->get_value($values);
    $currency = $this->get_value($values, 'currency');

    switch ($this->options ['display_format']) {
      case 'formatted':
        return CRM_Utils_Money::format($value, $currency);

      case 'raw':
        return $value;
    }
  }

  function option_definition() {
    $options = parent::option_definition();

    $options ['display_format'] = array('default' => 'formatted');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form ['display_format'] = array(
      '#type' => 'select',
      '#title' => t('Display format'),
      '#options' => array(
        'formatted' => t('Currency formatted amount ($123.45)'),
        'raw' => t('Raw amount (123.45)'),
      ),
      '#default_value' => $this->options ['display_format'],
    );
  }
}
