<?php

/**
 * Get details for the target and assignee contact of an activity.
 *
 * This is "simple" in that it is only appropriate for activities in which the business-process
 * guarantees that there is only one target and one assignee. If the business-process permits
 * multiple targets or multiple assignees, then consider the more versatile (but less sugary)
 * function "crmAPI".
 *
 * Note: This will perform like a dog, but who cares -- at most, we deal with O(100) iterations
 * as part of a background task.
 *
 * @param array $params
 *   , Array with keys:
 *   - activity_id: int, required
 *   - target_var: string, optional; name of a variable which will store the first/only target contact; default "target"
 *   - assignee_var: string, optional; name of a variable which will store the first/only assignee contact; default "assignee"
 *   - return: string, optional; comma-separated list of fields to return for each contact
 *
 * @param CRM_Core_Smarty $smarty
 *
 * @return string
 */
function smarty_function_simpleActivityContacts($params, &$smarty) {
  if (empty($params['activity_id'])) {
    trigger_error('simpleActivityContacts: missing &#039;activity_id&#039; parameter', E_USER_ERROR);
  }
  if (!isset($params['target_var'])) {
    $params['target_var'] = 'target';
  }
  if (!isset($params['assignee_var'])) {
    $params['assignee_var'] = 'assignee';
  }
  if (!isset($params['return'])) {
    $params['return'] = 'contact_id,contact_type,display_name,sort_name,first_name,last_name';
  }

  require_once 'api/api.php';
  require_once 'api/v3/utils.php';
  $activity = civicrm_api('activity', 'getsingle', [
    'version' => 3,
    'id' => $params['activity_id'],
    'return.target_contact_id' => 1,
    'return.assignee_contact_id' => 1,
  ]);

  $baseContactParams = ['version' => 3];
  foreach (explode(',', $params['return']) as $field) {
    $baseContactParams['return.' . $field] = 1;
  }

  foreach (['target', 'assignee'] as $role) {
    $contact = [];
    if (!empty($activity[$role . '_contact_id'])) {
      $contact_id = array_shift($activity[$role . '_contact_id']);
      $contact = civicrm_api('contact', 'getsingle', $baseContactParams + [
        'contact_id' => $contact_id,
      ]);
    }
    $smarty->assign($params[$role . '_var'], $contact);
  }

  return '';
}
