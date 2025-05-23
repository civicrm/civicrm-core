<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * @package CiviCRM_APIv3
 */

/**
 * Get information about fields for a given api request.
 *
 * Getfields information is used for documentation, validation, default setting
 * We first query the scheme using the $dao->fields function & then augment
 * that information by calling the _spec functions that apply to the relevant function
 * Note that we use 'unique' field names as described in the xml/schema files
 * for get requests & just field name for create. This is because some get functions
 * access multiple objects e.g. contact api accesses is_deleted from the activity
 * table & from the contact table
 *
 * @param array $apiRequest
 *   Api request as an array. Keys are.
 *   - entity: string
 *   - action: string
 *   - version: string
 *   - function: callback (mixed)
 *   - params: array, varies
 *
 * @param bool $unique
 *   Determines whether to key by unique field names (only affects get-type) actions
 *
 * @return array
 *   API success object
 */
function civicrm_api3_generic_getfields($apiRequest, $unique = TRUE) {
  static $results = [];
  if (!empty($apiRequest['params']['cache_clear'])) {
    $results = [];
    // we will also clear pseudoconstants here - should potentially be moved to relevant BAO classes
    CRM_Core_PseudoConstant::flush();
    Civi::cache('metadata')->clear();
    if (!empty($apiRequest['params']['fieldname'])) {
      CRM_Utils_PseudoConstant::flushConstant($apiRequest['params']['fieldname']);
    }
    if (!empty($apiRequest['params']['option_group_id'])) {
      $optionGroupName = civicrm_api('option_group', 'getvalue', [
        'version' => 3,
        'id' => $apiRequest['params']['option_group_id'],
        'return' => 'name',
      ]);
      if (is_string($optionGroupName)) {
        CRM_Utils_PseudoConstant::flushConstant(_civicrm_api_get_camel_name($optionGroupName));
      }
    }
  }
  $entity = $apiRequest['entity'];
  $lowercase_entity = _civicrm_api_get_entity_name_from_camel($entity);
  $subentity = $apiRequest['params']['contact_type'] ?? NULL;
  $action = $apiRequest['params']['action'] ?? NULL;
  $sequential = empty($apiRequest['params']['sequential']) ? 0 : 1;
  $apiRequest['params']['options'] ??= [];
  $optionsToResolve = (array) ($apiRequest['params']['options']['get_options'] ?? []);

  if (!$action || $action == 'getvalue' || $action == 'getcount') {
    $action = 'get';
  }
  // If no options, return results from cache
  if (!$apiRequest['params']['options'] && isset($results[$entity . $subentity]) && isset($action, $results[$entity . $subentity])
    && isset($action, $results[$entity . $subentity][$sequential])) {
    return $results[$entity . $subentity][$action][$sequential];
  }
  // defaults based on data model and API policy
  switch ($action) {
    case 'getfields':
      $values = _civicrm_api_get_fields($entity, FALSE, $apiRequest['params']);
      return civicrm_api3_create_success($values, $apiRequest['params'], $entity, 'getfields');

    case 'create':
    case 'update':
    case 'replace':
      $unique = FALSE;
    case 'get':
    case 'getsingle':
    case 'getcount':
    case 'getstat':
      $metadata = _civicrm_api_get_fields($apiRequest['entity'], $unique, $apiRequest['params']);
      if (empty($metadata['id'])) {
        // if id is not set we will set it eg. 'id' from 'case_id', case_id will be an alias
        if (!empty($metadata[strtolower($apiRequest['entity']) . '_id'])) {
          $metadata['id'] = $metadata[$lowercase_entity . '_id'];
          unset($metadata[$lowercase_entity . '_id']);
          $metadata['id']['api.aliases'] = [$lowercase_entity . '_id'];
        }
      }
      else {
        // really the preference would be to set the unique name in the xml
        // question is which is a less risky fix this close to a release - setting in xml for the known failure
        // (note) or setting for all api where fields is returning 'id' & we want to accept 'note_id' @ the api layer
        // nb we don't officially accept note_id anyway - rationale here is more about centralising a now-tested
        // inconsistency
        $metadata['id']['api.aliases'] = [$lowercase_entity . '_id'];
      }
      break;

    case 'delete':
      $metadata = [
        'id' => [
          'title' => $entity . ' ID',
          'api.required' => 1,
          'api.aliases' => [$lowercase_entity . '_id'],
          'type' => CRM_Utils_Type::T_INT,
        ],
      ];
      break;

    // Note: adding setvalue case here instead of in a generic spec function because
    // some APIs override the generic setvalue fn which causes the generic spec to be overlooked.
    case 'setvalue':
      $metadata = [
        'field' => [
          'title' => 'Field name',
          'api.required' => 1,
          'type' => CRM_Utils_Type::T_STRING,
        ],
        'id' => [
          'title' => $entity . ' ID',
          'api.required' => 1,
          'type' => CRM_Utils_Type::T_INT,
        ],
        'value' => [
          'title' => 'Value',
          'description' => "Field value to set",
          'api.required' => 1,
        ],
      ];
      if (array_intersect(['all', 'field'], $optionsToResolve)) {
        $options = civicrm_api3_generic_getfields(['entity' => $entity, ['params' => ['action' => 'create']]]);
        $metadata['field']['options'] = CRM_Utils_Array::collect('title', $options['values']);
      }
      break;

    default:
      // oddballs are on their own
      $metadata = [];
  }

  // Hack for product api to pass tests.
  if (!is_string($apiRequest['params']['options'])) {
    // Normalize this for the sake of spec funcions
    $apiRequest['params']['options']['get_options'] = $optionsToResolve;
  }

  // find any supplemental information
  $hypApiRequest = ['entity' => $apiRequest['entity'], 'action' => $action, 'version' => $apiRequest['version']];
  if ($action == 'getsingle') {
    $hypApiRequest['action'] = 'get';
  }
  try {
    list ($apiProvider, $hypApiRequest) = \Civi::service('civi_api_kernel')->resolve($hypApiRequest);
    if (isset($hypApiRequest['function'])) {
      $helper = '_' . $hypApiRequest['function'] . '_spec';
    }
    else {
      // not implemented MagicFunctionProvider
      $helper = '';
    }
  }
  catch (\Civi\API\Exception\NotImplementedException $e) {
    $helper = '';
  }
  if (function_exists($helper)) {
    // alter
    $helper($metadata, $apiRequest);
  }

  foreach ($metadata as $fieldname => $fieldSpec) {
    // Ensure 'name' is set
    if (!isset($fieldSpec['name'])) {
      $metadata[$fieldname]['name'] = $fieldname;
    }
    _civicrm_api3_generic_get_metadata_options($metadata, $apiRequest, $fieldname, $fieldSpec);

    // Convert options to "sequential" format
    if ($sequential && !empty($metadata[$fieldname]['options'])) {
      $metadata[$fieldname]['options'] = CRM_Utils_Array::makeNonAssociative($metadata[$fieldname]['options']);
    }
  }

  $results[$entity][$action][$sequential] = civicrm_api3_create_success($metadata, $apiRequest['params'], $entity, 'getfields');
  return $results[$entity][$action][$sequential];
}

/**
 * Get metadata for a field
 *
 * @param array $apiRequest
 *
 * @return array
 *   API success object
 */
function civicrm_api3_generic_getfield($apiRequest) {
  $params = $apiRequest['params'];
  $sequential = !empty($params['sequential']);
  $fieldName = _civicrm_api3_api_resolve_alias($apiRequest['entity'], $params['name'], $params['action']);
  if (!$fieldName) {
    return civicrm_api3_create_error("The field '{$params['name']}' doesn't exist.");
  }
  // Turn off sequential to make the field easier to find
  $apiRequest['params']['sequential'] = 0;
  if (isset($params['get_options'])) {
    $apiRequest['params']['options']['get_options_context'] = $params['get_options'];
    $apiRequest['params']['options']['get_options'] = $fieldName;
  }
  $result = civicrm_api3_generic_getfields($apiRequest, FALSE);
  $result = $result['values'][$fieldName];
  // Fix sequential options since we forced it off
  if ($sequential && !empty($result['options'])) {
    $result['options'] = CRM_Utils_Array::makeNonAssociative($result['options']);
  }
  return civicrm_api3_create_success($result, $apiRequest['params'], $apiRequest['entity'], 'getfield');
}

/**
 * Get metadata for getfield action.
 *
 * @param array $params
 * @param array $apiRequest
 *
 * @throws \CRM_Core_Exception
 * @throws \Exception
 */
function _civicrm_api3_generic_getfield_spec(&$params, $apiRequest) {
  $params = [
    'name' => [
      'title' => 'Field name',
      'description' => 'Name or alias of field to lookup',
      'api.required' => 1,
      'type' => CRM_Utils_Type::T_STRING,
    ],
    'action' => [
      'title' => 'API Action',
      'api.required' => 1,
      'type' => CRM_Utils_Type::T_STRING,
      'api.aliases' => ['api_action'],
    ],
    'get_options' => [
      'title' => 'Get Options',
      'description' => 'Context for which to get field options, or null to skip fetching options.',
      'type' => CRM_Utils_Type::T_STRING,
      'options' => CRM_Core_DAO::buildOptionsContext(),
      'api.aliases' => ['context'],
    ],
  ];
  // Add available options to these params if requested
  if (array_intersect(['all', 'action'], $apiRequest['params']['options']['get_options'])) {
    $actions = civicrm_api3($apiRequest['entity'], 'getactions');
    $actions = array_combine($actions['values'], $actions['values']);
    // Let's not go meta-crazy
    CRM_Utils_Array::remove($actions, 'getactions', 'getoptions', 'getfields', 'getfield', 'getcount', 'getrefcount', 'getsingle', 'getlist', 'getvalue', 'setvalue', 'update');
    $params['action']['options'] = $actions;
  }
}

/**
 * API return function to reformat results as count.
 *
 * @param array $apiRequest
 *   Api request as an array. Keys are.
 *
 * @throws CRM_Core_Exception
 * @return int
 *   count of results
 */
function civicrm_api3_generic_getcount($apiRequest) {
  $apiRequest['params']['options']['is_count'] = TRUE;
  $result = civicrm_api($apiRequest['entity'], 'get', $apiRequest['params']);
  if (is_numeric($result['values'] ?? '')) {
    return (int) $result['values'];
  }
  if (!isset($result['count'])) {
    throw new CRM_Core_Exception(ts('Unexpected result from getcount') . print_r($result, TRUE));
  }
  return $result['count'];
}

/**
 * API return function to reformat results as single result.
 *
 * @param array $apiRequest
 *   Api request as an array. Keys are.
 *
 * @return int
 *   count of results
 */
function civicrm_api3_generic_getsingle($apiRequest) {
  // So the first entity is always result['values'][0].
  $apiRequest['params']['sequential'] = 1;
  $result = civicrm_api($apiRequest['entity'], 'get', $apiRequest['params']);
  if ($result['is_error'] !== 0) {
    return $result;
  }
  if ($result['count'] === 1) {
    return $result['values'][0];
  }
  if ($result['count'] !== 1) {
    return civicrm_api3_create_error("Expected one " . $apiRequest['entity'] . " but found " . $result['count'], ['count' => $result['count']]);
  }
  return civicrm_api3_create_error("Undefined behavior");
}

/**
 * API return function to reformat results as single value.
 *
 * @param array $apiRequest
 *   Api request as an array. Keys are.
 *
 * @return int
 *   count of results
 */
function civicrm_api3_generic_getvalue($apiRequest) {
  $apiRequest['params']['sequential'] = 1;
  $result = civicrm_api($apiRequest['entity'], 'get', $apiRequest['params']);
  if ($result['is_error'] !== 0) {
    return $result;
  }
  if ($result['count'] !== 1) {
    $result = civicrm_api3_create_error("Expected one " . $apiRequest['entity'] . " but found " . $result['count'], ['count' => $result['count']]);
    return $result;
  }

  // we only take "return=" as valid options
  if (!empty($apiRequest['params']['return'])) {
    if (!isset($result['values'][0][$apiRequest['params']['return']])) {
      return civicrm_api3_create_error("field " . $apiRequest['params']['return'] . " unset or not existing", ['invalid_field' => $apiRequest['params']['return']]);
    }

    return $result['values'][0][$apiRequest['params']['return']];
  }

  return civicrm_api3_create_error("missing param return=field you want to read the value of", ['error_type' => 'mandatory_missing', 'missing_param' => 'return']);
}

/**
 * Get count of contact references.
 *
 * @param array $params
 * @param array $apiRequest
 */
function _civicrm_api3_generic_getrefcount_spec(&$params, $apiRequest) {
  $params['id']['api.required'] = 1;
  $params['id']['title'] = $apiRequest['entity'] . ' ID';
  $params['id']['type'] = CRM_Utils_Type::T_INT;
}

/**
 * API to determine if a record is in-use.
 *
 * @param array $apiRequest
 *   Api request as an array.
 *
 * @throws CRM_Core_Exception
 * @return array
 *   API result (int 0 or 1)
 */
function civicrm_api3_generic_getrefcount($apiRequest) {
  $entityToClassMap = CRM_Core_DAO_AllCoreTables::daoToClass();
  if (!isset($entityToClassMap[$apiRequest['entity']])) {
    throw new CRM_Core_Exception("The entity '{$apiRequest['entity']}' is unknown or unsupported by 'getrefcount'. Consider implementing this API.", 'getrefcount_unsupported');
  }
  $daoClass = $entityToClassMap[$apiRequest['entity']];

  /** @var CRM_Core_DAO $dao */
  $dao = new $daoClass();
  $dao->id = $apiRequest['params']['id'];
  if ($dao->find(TRUE)) {
    return civicrm_api3_create_success($dao->getReferenceCounts());
  }
  else {
    return civicrm_api3_create_success([]);
  }
}

/**
 * API wrapper for replace function.
 *
 * @param array $apiRequest
 *   Api request as an array. Keys are.
 *
 * @return int
 *   count of results
 */
function civicrm_api3_generic_replace($apiRequest) {
  return _civicrm_api3_generic_replace($apiRequest['entity'], $apiRequest['params']);
}

/**
 * API wrapper for getoptions function.
 *
 * @param array $apiRequest
 *   Api request as an array.
 *
 * @return array
 *   Array of results
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_generic_getoptions($apiRequest) {
  // Resolve aliases.
  $fieldName = _civicrm_api3_api_resolve_alias($apiRequest['entity'], $apiRequest['params']['field']);
  if (!$fieldName) {
    return civicrm_api3_create_error("The field '{$apiRequest['params']['field']}' doesn't exist.");
  }
  // Validate 'context' from params
  $context = $apiRequest['params']['context'] ?? NULL;
  CRM_Core_DAO::buildOptionsContext($context);
  unset($apiRequest['params']['context'], $apiRequest['params']['field'], $apiRequest['params']['condition']);

  // Legacy support for campaign_id fields which used to have a pseudoconstant
  if ($fieldName === 'campaign_id') {
    $campaignParams = [
      'select' => ['id', 'name', 'title'],
      'options' => ['limit' => 0],
    ];
    if ($context === 'match' || $context === 'create') {
      $campaignParams['is_active'] = 1;
    }
    $labelField = $context === 'validate' ? 'name' : 'title';
    $keyField = $context === 'match' ? 'name' : 'id';
    $options = array_column(civicrm_api3('Campaign', 'get', $campaignParams)['values'], $labelField, $keyField);
  }
  else {
    $baoName = _civicrm_api3_get_BAO($apiRequest['entity']);
    if (!isset($apiRequest['params']['check_permissions'])) {
      // Ensure this is set so buildOptions for ContributionPage.buildOptions
      // can distinguish between 'who knows' and 'NO'.
      $apiRequest['params']['check_permissions'] = FALSE;
    }
    $options = $baoName::buildOptions($fieldName, $context, $apiRequest['params']);
  }
  if ($options === FALSE) {
    return civicrm_api3_create_error("The field '{$fieldName}' has no associated option list.");
  }
  // Support 'sequential' output as a non-associative array
  if (!empty($apiRequest['params']['sequential'])) {
    $options = CRM_Utils_Array::makeNonAssociative($options);
  }
  return civicrm_api3_create_success($options, $apiRequest['params'], $apiRequest['entity'], 'getoptions');
}

/**
 * Provide metadata for this generic action
 *
 * @param array $params
 * @param array $apiRequest
 */
function _civicrm_api3_generic_getoptions_spec(&$params, $apiRequest) {
  $params += [
    'field' => [
      'title' => 'Field name',
      'api.required' => 1,
      'type' => CRM_Utils_Type::T_STRING,
    ],
    'context' => [
      'title' => 'Context',
      'type' => CRM_Utils_Type::T_STRING,
      'options' => CRM_Core_DAO::buildOptionsContext(),
    ],
  ];

  // Add available fields if requested
  if (array_intersect(['all', 'field'], $apiRequest['params']['options']['get_options'])) {
    $fields = civicrm_api3_generic_getfields(['entity' => $apiRequest['entity'], ['params' => ['action' => 'create']]]);
    $params['field']['options'] = [];
    foreach ($fields['values'] as $name => $field) {
      if (isset($field['pseudoconstant']) || ($field['type'] ?? NULL) == CRM_Utils_Type::T_BOOLEAN) {
        $params['field']['options'][$name] = $field['title'] ?? $name;
      }
    }
  }

  $entityName = _civicrm_api_get_entity_name_from_camel($apiRequest['entity']);
  $getOptionsSpecFunction = '_civicrm_api3_' . $entityName . '_getoptions_spec';

  if (function_exists($getOptionsSpecFunction)) {
    $getOptionsSpecFunction($params);
  }
}

/**
 * Get metadata.
 *
 * Function fills the 'options' array on the metadata returned by getfields if
 * 1) the param option 'get_options' is defined - e.g. $params['options']['get_options'] => array('custom_1)
 * (this is passed in as the $fieldsToResolve array)
 * 2) the field is a pseudoconstant and is NOT an FK
 * - the reason for this is that checking / transformation is done on pseudoconstants but
 * - if the field is an FK then mysql will enforce the data quality (& we have handling on failure)
 * @todo - if may be we should define a 'resolve' key on the pseudoconstant for when these rules are not fine enough
 *
 * This function is only split out for the purpose of code clarity / comment block documentation
 *
 * @param array $metadata
 *   The array of metadata that will form the result of the getfields function.
 * @param array $apiRequest
 * @param string $fieldname
 *   Field currently being processed.
 * @param array $fieldSpec
 *   Metadata for that field.
 */
function _civicrm_api3_generic_get_metadata_options(&$metadata, $apiRequest, $fieldname, $fieldSpec) {
  if (empty($fieldSpec['pseudoconstant']) && empty($fieldSpec['option_group_id'])) {
    return;
  }

  if (!is_array($apiRequest['params']['options'])) {
    $fieldsToResolve = [];
  }
  else {
    $fieldsToResolve = $apiRequest['params']['options']['get_options'];
  }

  if (!empty($metadata[$fieldname]['options']) || (!in_array($fieldname, $fieldsToResolve) && !in_array('all', $fieldsToResolve))) {
    return;
  }

  // Allow caller to specify context
  $context = $apiRequest['params']['options']['get_options_context'] ?? NULL;
  // Default to api action if it is a supported context.
  if (!$context) {
    $action = $apiRequest['params']['action'] ?? NULL;
    $contexts = CRM_Core_DAO::buildOptionsContext();
    if (isset($contexts[$action])) {
      $context = $action;
    }
  }

  $options = civicrm_api($apiRequest['entity'], 'getoptions', ['version' => 3, 'field' => $fieldname, 'context' => $context]);
  if (isset($options['values']) && is_array($options['values'])) {
    $metadata[$fieldname]['options'] = $options['values'];
  }
}
