<?php

/**
 * This relationship handler is used when joining the civicrm_participant table
 * to the civicrm_event table. This handler allows us to optionally add conditions
 * to the join clause based on relationship_type_id and is_active.
 */
class civicrm_handler_relationship_participant extends views_handler_relationship {
  public static $participant_types;

  /**
   * Preload the list of participant roles and store in the static variable
   * $participant_types
   */
  public function construct() {
    parent::construct();

    if (!civicrm_initialize()) {
      return;
    }

    $rawParticipantRoles = civicrm_api3('Participant', 'getoptions', [
      'field' => "participant_role_id",
    ]);

    self::$participant_types = $rawParticipantRoles['values'];
  }

  /**
   * Add additional options for participant_type options to the view. By
   * defining these here, Views will take care of saving the
   * values submitted from the options form.
   */
  public function option_definition() {
    $options = parent::option_definition();
    $options['participant_type'] = array('default' => 0);
    return $options;
  }

  /**
   * Add participant_type dropdown to relationship configuration form.
   */
  public function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['participant_type'] = array(
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => 'Choose a specific Participant type',
      '#options' => self::$participant_types,
      '#description' => t('Choose to limit this relationship to one or more specific types of CiviCRM Participant.'),
      '#default_value' => $this->options['participant_type'],
    );
  }

  /**
   * Modify the default views relationship query to optionally specify
   * join conditions for participant_type
   */
  public function query() {
    parent::query();

    // Specify the type of participants to join
    if (isset($this->options['participant_type']) && $this->options['participant_type']) {
      $this->query->table_queue[$this->alias]['join']->extra[] = array(
        'value' => $this->options['participant_type'],
        'numeric' => TRUE,
        'field' => 'role_id',
      );
    }
  }

}
