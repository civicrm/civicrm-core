<?php

/**
 * Get a list of afforms.
 *
 * This API exists solely for the purpose of entityRef widgets.
 * All other Afform api functionality is v4.
 *
 * @param array $params
 *
 * @return array
 *   API result
 */
function civicrm_api3_afform_get($params) {
  /** @var \CRM_Afform_AfformScanner $scanner */
  $scanner = \Civi::service('afform_scanner');

  $names = array_keys($scanner->findFilePaths());
  $result = [];

  foreach ($names as $name) {
    $info = [
      'name' => $name,
      'module_name' => _afform_angular_module_name($name, 'camel'),
      'directive_name' => _afform_angular_module_name($name, 'dash'),
    ];
    $record = $scanner->getMeta($name);
    $result[$name] = array_merge($record, $info);
  }

  $allFields = [];
  _civicrm_api3_afform_get_spec($allFields);
  return _civicrm_api3_basic_array_get('Afform', $params, $result, 'name', array_keys($allFields));
}

/**
 * @param array $fields
 */
function _civicrm_api3_afform_get_spec(&$fields) {
  $fields['name'] = [
    'title' => 'Name',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $fields['title'] = [
    'title' => 'Title',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $fields['module_name'] = [
    'title' => 'Module Name',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $fields['directive_name'] = [
    'title' => 'Directive Name',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $fields['description'] = [
    'title' => 'Description',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $fields['server_route'] = [
    'title' => 'Server Route',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $fields['type'] = [
    'title' => 'Type',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $fields['placement'] = [
    'title' => 'Placement',
  ];
  $fields['is_public'] = [
    'title' => 'Public',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  ];
  $fields['redirect'] = [
    'title' => 'Redirect URL',
    'type' => CRM_Utils_Type::T_STRING,
  ];
}

/**
 * Augment parameters for Afform entityRef list.
 *
 * @see _civicrm_api3_generic_getlist_params
 *
 * @param array $request
 *   API request.
 */
function _civicrm_api3_afform_getlist_params(&$request) {
  $fieldsToReturn = [
    'name',
    'title',
    'type',
    'description',
    $request['id_field'],
    $request['label_field'],
  ];
  $request['params']['return'] = array_unique(array_merge($fieldsToReturn, $request['extra']));
}

/**
 * Format output for Afform entityRef list.
 *
 * @see _civicrm_api3_generic_getlist_output
 *
 * @param array $result
 * @param array $request
 *
 * @return array
 */
function _civicrm_api3_afform_getlist_output($result, $request) {
  $output = [];
  if (!empty($result['values'])) {
    $icons = CRM_Core_OptionGroup::values('afform_type', FALSE, FALSE, FALSE, NULL, 'icon', FALSE);
    foreach ($result['values'] as $row) {
      $data = [
        'id' => $row[$request['id_field']],
        'label' => $row[$request['label_field']],
        'description' => [],
        'icon' => $icons[$row['type']],
      ];
      if (!empty($row['description'])) {
        $data['description'][] = $row['description'];
      }
      $output[] = $data;
    }
  }
  return $output;
}
