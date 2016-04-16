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
 * Field handler to provide relationship to CiviCRM email.
 *
 * @ingroup civicrm_relationship_handlers
 */
class civicrm_handler_relationship_location extends views_handler_relationship {

  static $_locationTypes;
  static $_locationOps;
  static $location_op;

  function construct() {
    parent::construct();
    if (!self::$_locationTypes) {
      if (!civicrm_initialize()) {
        return;
      }
      require_once 'CRM/Core/PseudoConstant.php';
      self::$_locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
      self::$_locationOps = array(0 => 'AND', 1 => 'OR');
    }
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['location_type'] = array('default' => 0);
    $options['location_op'] = array('default' => 0);
    $options['is_primary'] = array('default' => '');
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $locationOptions = array(0 => 'Any');
    foreach (self::$_locationTypes as $id => $type) {
      $locationOptions[$id] = $type;
    }
    $label = $this->definition['title'];
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#default_value' => $label,
      '#dependency' => array(
        'edit-options-custom-label' => array(1),
      ),
      '#weight' => -102,
    );
    $form['location_choices'] = array(
      '#type' => 'fieldset',
      '#title' => t('CiviCRM Location Relationship Options'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => '<strong>Note:</strong> it is possible to choose relationship options that result in no rows being displayed',
      '#weight' => 1,
    );
    $form['location_op'] = array(
      '#type' => 'select',
      '#title' => 'And/Or',
      '#options' => self::$_locationOps,
      '#description' => t('Relationship option are joined by And/Or?'),
      '#default_value' => $this->options['location_op'],
      '#fieldset' => 'location_choices',
    );
    $form['location_type'] = array(
      '#type' => 'radios',
      '#title' => 'Location type for this relationship',
      '#options' => $locationOptions,
      '#description' => t('Location type to be used for this relationship'),
      '#default_value' => $this->options['location_type'],
      '#fieldset' => 'location_choices',
    );
    $form['is_primary'] = array(
      '#type' => 'checkbox',
      '#title' => 'Use only Primary record?',
      '#options' => array(0 => 'No', 1 => 'Yes'),
      '#description' => t('Check above box if you want only the <strong>Primary</strong> record used in this relationship.'),
      '#default_value' => $this->options['is_primary'],
      '#fieldset' => 'location_choices',
    );
  }


  function location_extras() {
    $extra = array();
    if (!empty($this->options['location_type'])) {
      $extra[] = array(
        'value' => $this->options['location_type'],
        'numeric' => TRUE,
        'field' => 'location_type_id',
        'operator' => '=',
      );
    }
    if (!empty($this->options['is_primary'])) {
      $extra[] = array(
        'value' => $this->options['is_primary'],
        'numeric' => TRUE,
        'field' => 'is_primary',
        'operator' => '=',
      );
    }
    return $extra;
  }

  /**
   * Called to implement a relationship in a query.
   */
  function query() {
    // Just ensure the one table. This is briefer than parent::query(), which
    // attempts to create an additional join.
    $this->ensure_my_table();
  }
}

