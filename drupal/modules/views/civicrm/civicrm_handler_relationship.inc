<?php

/**
 * @file
 * Views' relationship handlers.
 */

/**
 * @defgroup views_relationship_handlers Views' relationship handlers
 * @{
 * Handlers to tell Views how to create alternate relationships.
 */

/**
 * Simple relationship handler that allows a new version of the primary table
 * to be linked in.
 *
 * The base relationship handler can only handle a single join. Some relationships
 * are more complex and might require chains of joins; for those, you must
 * utilize a custom relationship handler.
 *
 * Definition items:
 * - base: The new base table this relationship will be adding. This does not
 *   have to be a declared base table, but if there are no tables that
 *   utilize this base table, it won't be very effective.
 * - base field: The field to use in the relationship; if left out this will be
 *   assumed to be the primary field.
 * - relationship table: The actual table this relationship operates against.
 *   This is analogous to using a 'table' override.
 * - relationship field: The actual field this relationship operates against.
 *   This is analogous to using a 'real field' override.
 * - label: The default label to provide for this relationship, which is
 *   shown in parentheses next to any field/sort/filter/argument that uses
 *   the relationship.
 */
class civicrm_handler_relationship extends views_handler_relationship {
  static $_relationshipType;

  /**
   * Init handler to let relationships live on tables other than
   * the table they operate on.
   */
  public function init(&$view, &$options) {
    parent::init($view, $options);
  }

  public function construct() {
    parent::construct();
    if (!civicrm_initialize()) {
      return;
    }
    require_once 'CRM/Core/PseudoConstant.php';
    $relationshipType_array = CRM_Core_PseudoConstant::relationshipType('name');

    // relationshipType() returns information about relations as array with fields
    // 'name_a_b', 'name_b_a', 'contact_type_a' and 'contact_type_b'.
    $options = array(0 => 'Any');
    foreach ($relationshipType_array as $id => $value_array) {
      if ($this->definition['real field'] == 'contact_id_b') {
        $options[$id] = $value_array['name_b_a'];
      }
      else {
        $options[$id] = $value_array['name_a_b'];
      }
    }

    self::$_relationshipType = $options;
  }

  /**
   * Get this field's label.
   */
  public function label() {
    if (!isset($this->options['label'])) {
      return '';
    }
    return $this->options['label'];
  }

  public function option_definition() {
    $options = parent::option_definition();
    $label = !empty($this->definition['label']) ? $this->definition['label'] : $this->definition['title'];
    $options['label'] = array('default' => $label, 'translatable' => TRUE);
    $options['relationship_type'] = array('default' => 0);
    $options['relationship_state'] = array('default' => 1);
    return $options;
  }

  /**
   * Default options form that provides the label widget that all fields
   * should have.
   */
  public function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Identifier'),
      '#default_value' => isset($this->options['label']) ? $this->options['label'] : $this->definition['label'],
      '#description' => t('Edit the administrative label displayed when referencing this relationship form filters, etc.'),
    );

    $form['relationship_type'] = array(
      '#type' => 'select',
      '#title' => 'Choose a specific relationship type',
      '#options' => self::$_relationshipType,
      '#description' => t('Use this drop down to limit this relatioship to a specific type of CiviCRM relationship'),
      '#default_value' => $this->options['relationship_type'],
    );

    $form['relationship_state'] = array(
      '#type' => 'checkbox',
      '#title' => t('Limit results only to active relationships?'),
      '#default_value' => $this->options['relationship_state'],
      '#description' => t('Exclude relationships that are inactive.'),
    );
  }

  /**
   * Called to implement a relationship in a query.
   */
  public function query() {

    // Figure out what base table this relationship brings to the party.
    $table_data        = views_fetch_data($this->definition['base']);
    $base_field        = empty($this->definition['base field']) ? $table_data['table']['base']['field'] : $this->definition['base field'];
    $this->table_alias = $this->query->add_table($this->table, $this->relationship);

    // Make sure the join to civicrm_contact form the civicrm_relationship table
    // Is is to the opposite field (contact_id_a vs contact_id_b or vice versa)
    // Otherwise the displayed field will always be the same as the base table's record
    if ($this->table == 'civicrm_relationship' && isset($this->view->query->table_queue[$this->table_alias]['join']->field)) {
      $this->view->query->table_queue[$this->table_alias]['join']->field = $this->definition['relationship field'];
    }

    $def = $this->definition;

    $leftField         = $this->definition['base'] == 'civicrm_relationship' ? $this->field : $this->definition['other_field'];
    $def['table']      = $this->definition['base'];
    $def['field']      = $this->definition['field'];
    $def['left_table'] = $this->table_alias;
    $def['left_field'] = $leftField;
    if (!empty($this->options['required'])) {
      $def['type'] = 'INNER';
    }

    if (!empty($def['join_handler']) && class_exists($def['join_handler'])) {
      $join = new $def['join_handler']();
    }
    else {
      $join = new views_join();
    }

    $join->definition = $def;
    $join->construct();
    $join->adjusted = TRUE;

    // Add a join condition to the on clause to narrow down the relationship type shown
    if (isset($this->options['relationship_type']) && $this->options['relationship_type']) {
      $this->query->table_queue[$this->table_alias]['join']->extra[] = array(
        'value' => $this->options['relationship_type'],
        'numeric' => TRUE,
        'field' => 'relationship_type_id',
        'operator' => '=',
      );
    }

    if (isset($this->options['relationship_state']) && $this->options['relationship_state']) {
      $this->query->table_queue[$this->table_alias]['join']->extra[] = array(
        'value' => 1,
        'numeric' => TRUE,
        'field' => 'is_active',
        'operator' => '=',
      );
    }

    // use a short alias for this:
    $alias = $def['table'] . '_' . $this->table;

    $this->alias = $this->query->add_relationship($alias, $join, $this->definition['base'], $this->relationship);
  }

}
