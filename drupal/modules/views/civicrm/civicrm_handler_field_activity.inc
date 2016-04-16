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
 * Field handler for activity contact field
 *
 * @ingroup civicrm_field_handlers
 */
class civicrm_handler_field_activity extends civicrm_handler_field {
  static $_recordType;
  function construct() {
    parent::construct();
    if (!self::$_recordType) {
      if (!civicrm_initialize()) {
        return;
      }
      self::$_recordType = CRM_Core_PseudoConstant::get('CRM_Activity_DAO_ActivityContact', 'record_type_id');
    }
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['record_type'] = array('default' => 0);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $recordTypes = array(0 => 'Any');
    foreach (self::$_recordType as $id => $type) {
      $recordTypes[$id] = $type;
    }
    $form['record_type'] = array(
      '#type' => 'radios',
      '#title' => 'Record type for this field',
      '#options' => $recordTypes,
      '#description' => t('Record type to be displayed for this field'),
      '#default_value' => $this->options['record_type'],
      '#fieldset' => 'record_type_choices',
    );
  }

  /**
   * Called to link activity contact with civicrm_contact in a query.
   */
  function query() {

    // Figure out what base table
    $table_data        = views_fetch_data($this->definition['base']);
    $base_field        = empty($this->definition['base field']) ? $table_data['table']['base']['field'] : $this->definition['base field'];
    $this->table_alias = $this->query->add_table($this->table, $this->relationship);

    // Make sure the join to civicrm_contact form the civicrm_activity_contact table
    // Otherwise the displayed field will always be the same as the base table's record
    if ($this->table == 'civicrm_activity_contact' && isset($this->view->query->table_queue[$this->table_alias]['join']->field)) {
      $this->view->query->table_queue[$this->table_alias]['join']->field = $this->definition['relationship field'];
    }

    $def = $this->definition;

    $leftField         = $this->definition['base'] == 'civicrm_activity_contact' ? $this->field : $this->definition['other_field'];
    $def['table']      = $this->definition['base'];
    $def['field']      = $this->definition['base field'];
    $def['left_table'] = $this->table_alias;
    $def['left_field'] = $leftField;
    if (!empty($this->options['required'])) {
      $def['type'] = 'INNER';
    }

    if (!empty($def['join_handler']) && class_exists($def['join_handler'])) {
      $join = new $def['join_handler'];
    }
    else {
      $join = new views_join();
    }

    $join->definition = $def;
    $join->construct();
    $join->adjusted = TRUE;

    // Add a join condition to the on clause to narrow down the relationship type shown
    if (isset($this->options['record_type']) && $this->options['record_type']) {
      $this->query->table_queue[$this->table_alias]['join']->extra[] = array(
        'value' => $this->options['record_type'],
        'numeric' => TRUE,
        'field' => 'record_type_id',
        'operator' => '=',
      );
    }

    // use a short alias for this:
    $alias = $def['table'] . '_' . $this->table;

    $this->alias = $this->query->add_relationship($alias, $join, $this->definition['base'], $this->relationship);
  }
}

