<?php
use CRM_Afform_ExtensionUtil as E;

/**
 * Afform.Get API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_afform_get_spec(&$spec) {
  $spec['name'] = array(
    'name' => 'name',
    'type' => CRM_Utils_Type::T_STRING,
    'title' => ts('Form name'),
    'description' => 'Form name',
    'maxlength' => 128,
    'size' => CRM_Utils_Type::HUGE,
  );
  $spec['description'] = array(
    'name' => 'description',
    'type' => CRM_Utils_Type::T_TEXT,
    'title' => ts('Description'),
    'description' => 'Description',
  );
  $spec['is_public'] = array(
    'name' => 'is_public',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'title' => ts('Is public'),
    'description' => 'Display with public theming?',
  );

  // FIXME: title, requires, layout, server_route, client_route
}

/**
 * Afform.Get API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_afform_get($params) {
  $scanner = new CRM_Afform_AfformScanner();
  $converter = new CRM_Afform_ArrayHtml();
  $records = [];

  if (isset($params['name']) && is_string($params['name'])) {
    $names = [$params['name']];
  }
  else {
    $names = array_keys($scanner->findFilePaths());
  }

  foreach ($names as $name) {
    $record = $scanner->getMeta($name);
    $layout = $scanner->findFilePath($name, 'layout.html');
    if ($layout) {
      // FIXME check for file existence+substance+validity
      $record['layout'] = $converter->convertHtmlToArray(file_get_contents($layout));
    }
    $records[$name] = $record;
  }

  return _civicrm_api3_basic_array_get('Afform', $params, $records, 'name', _afform_fields());
}
