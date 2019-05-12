<?php

function civicrm_rules_admin_settings($form_state = NULL) {

  $form['civicrm_rules_post_entities'] = array(
    '#title' => t("Entities enabled for Post Rules"),
    '#type' => 'checkboxes',
    '#options' => _civicrm_rules_admin_getEntities(),
    '#description' => t('Which Entities Do you want POST rules activated for'),
    '#default_value' => variable_get('civicrm_rules_post_entities', array()),
  );

  return system_settings_form($form);
}

function _civicrm_rules_admin_getEntities() {
  //Todo Use civicrm_api('Entities', 'Get', array('version' => 3));
  // to extend entities to all API entities
  return array('event' => 'Event', 'participant' => 'Participant');
}
