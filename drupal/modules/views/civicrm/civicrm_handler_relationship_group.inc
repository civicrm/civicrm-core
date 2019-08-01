<?php

/**
 * This relationship handler is used when joining the civicrm_group_contact table
 * to the civicrm_contact table. This handler allows us to optionally add conditions
 * to the join clause based on group_id, status, group title, is_active, group is standard group.
 */
class civicrm_handler_relationship_group extends views_handler_relationship {
  public static $civi_group_types;
  private $civi_groups;
  protected $is_standard;

  /**
   * Preload the list of group_types & titles and store in the static variables
   */
  public function construct() {
    parent::construct();

    if (!civicrm_initialize()) {
      return;
    }

    self::$civi_group_types = CRM_Contact_BAO_Group::buildOptions('group_type');

    $standard_or_smart = ($this->is_standard ? 'IS NULL' : 'IS NOT NULL');
    $result = civicrm_api3('Group', 'get', [
      'return' => ["name", "title", "group_type"],
      'is_hidden' => 0,
      'is_active' => 1,
      'saved_search_id' => [$standard_or_smart => 1],
      'options' => ['limit' => 0, 'sort' => "title"],
    ]);
    $this->civi_groups = [];
    foreach ($result['values'] as $group) {
      $group_types = [];
      if (!empty($group['group_type'])) {
        foreach ((array) $group['group_type'] as $type) {
          if (!empty($type)) {
            $group_types[] = (isset(self::$civi_group_types[$type]) ? self::$civi_group_types[$type] : $type);
          }
        }
      }

      if (isset($group['name'])) {
        $group_type = '';
        if (!empty($group_types)) {
          $group_type = implode(', ', $group_types);
        }
        $this->civi_groups[$group['name']] = $group['title'] . ' | ' . $group_type;
      }
    }
  }

  /**
   * Add additional options
   * to the view. By defining these here, Views will take care of saving the
   * values submitted from the options form.
   */
  public function option_definition() {
    $options = parent::option_definition();
    $options['civi_group_type'] = ['default' => NULL];
    $options['civi_group_name'] = ['default' => NULL];
    return $options;
  }

  /**
   * Relationship configuration form.
   */
  public function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['civi_group_type'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => 'Choose a specific group type',
      '#options' => self::$civi_group_types,
      '#description' => t('Choose to limit this relationship to one or more specific types of CiviCRM group.'),
      '#default_value' => $this->options['civi_group_type'],
    ];
    $form['civi_group_name'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => 'Choose a specific group',
      '#options' => $this->civi_groups,
      '#description' => t('Choose to limit this relationship to one or more specific CiviCRM groups.'),
      '#default_value' => $this->options['civi_group_name'],
    ];
  }

  public function join_required($join = []) {
    if (!empty($this->options['required'])) {
      $join->type = 'INNER';
    }
    return $join;
  }

  public function join_group_type($join = []) {
    $extra = [];
    if (isset($join->extra)) {
      $extra = $join->extra;
    }
    if (isset($this->options['civi_group_type']) && $this->options['civi_group_type']) {
      $sep = CRM_Core_DAO::VALUE_SEPARATOR;

      $extra[] = [
        'value' => "($sep" . implode("$sep|$sep", $this->options['civi_group_type']) . "$sep)",
        'numeric' => FALSE,
        'field' => 'group_type',
        'operator' => 'RLIKE',
      ];
    }
    if (!empty($extra)) {
      $join->extra = $extra;
    }
    return $join;
  }

  public function join_group_name($join = []) {
    $extra = [];
    if (isset($join->extra)) {
      $extra = $join->extra;
    }
    if (isset($this->options['civi_group_name']) && $this->options['civi_group_name']) {
      $extra[] = [
        'value' => $this->options['civi_group_name'],
        'numeric' => FALSE,
        'field' => 'name',
      ];
    }
    if (!empty($extra)) {
      $join->extra = $extra;
    }
    return $join;
  }

  public function join_group_active_not_hidden($join = []) {
    $extra = [];
    if (isset($join->extra)) {
      $extra = $join->extra;
    }
    $extra[] = [
      'value' => 1,
      'numeric' => TRUE,
      'field' => 'is_active',
    ];
    $extra[] = [
      'value' => 0,
      'numeric' => TRUE,
      'field' => 'is_hidden',
    ];

    $join->extra = $extra;
    return $join;
  }

  public function get_join() {
    $join = parent::get_join();
    $join = $this->join_group_active_not_hidden($join);
    $join = $this->join_required($join);
    $join = $this->join_group_type($join);
    $join = $this->join_group_name($join);
    return $join;
  }

  /**
   * Called to implement a relationship in a query.
   */
  public function query() {
    $join = $this->get_join();
    $this->alias = $this->query->add_table($this->table, $this->relationship, $join);
    //register relationship
    $this->view->relationship[$this->options['id']]->alias = $this->alias;
    $this->query->relationships[$this->alias] = [
      'link' => $this->relationship,
      'table' => $this->table,
      'base' => $this->table,
    ];

  }

}
