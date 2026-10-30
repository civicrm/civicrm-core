<?php

/**
 * Implements hook_civicrm_searchKitTasks().
 *
 * Tasks declared with empty params, which reach the browser as a JSON array.
 */
function searchkit_apibatch_test_civicrm_searchKitTasks(array &$tasks, bool $checkPermissions, ?int $userID) {
  $tasks['Activity']['emptyParamsDelete'] = [
    'title' => 'Empty Params Delete',
    'icon' => 'fa-trash',
    'apiBatch' => [
      'action' => 'delete',
      'params' => [],
    ],
  ];
  $tasks['Activity']['emptyParamsUpdate'] = [
    'title' => 'Empty Params Update',
    'icon' => 'fa-pencil',
    'apiBatch' => [
      'action' => 'update',
      'params' => [],
      'fields' => [['name' => 'subject', 'default_value' => 'Updated in batch']],
    ],
  ];
}
