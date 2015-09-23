<?php

/**
 * Implementation of hook_drush_command().
 *
 * In this hook, you specify which commands your
 * drush module makes available, what it does and
 * description.
 *
 * Notice how this structure closely resembles how
 * you define menu hooks.
 *
 * @See drush_parse_command() for a list of recognized keys.
 *
 * @return
 *   An associative array describing your command(s).
 */
function civicrm_member_roles_drush_command() {
  $items = array();
  // the key in the $items array is the name of the command.
  $items['civicrm-member-role-sync'] = array(
    'description' => 'Run CiviCRM Member Role Sync',
    'examples' =>
    array(
      'drush civicrm-member-role-sync --uid=8',
      'drush civicrm-member-role-sync --contact_id=89',
    ),
    'options' => array(
      'uid' => 'Optional User ID',
      'contact_id' => 'Optional Contact ID',
    ),
    'aliases' => array('cmrs'),
  );
  return $items;
}
/**
 * (Drush callback)
 *
 * Implementation of command 'civicrm-member-role-sync'
 */
function drush_civicrm_member_roles_civicrm_member_role_sync($uid = NULL, $contact_id = NULL) {
  return _civicrm_member_roles_sync(drush_get_option('uid'), drush_get_option('contact_id'));
}