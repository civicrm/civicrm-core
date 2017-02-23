<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * @package CiviCRM_APIv3
 */

/**
 * Generic api wrapper used for quicksearch and autocomplete.
 *
 * @param array $apiRequest
 *
 * @return mixed
 */
function civicrm_api3_generic_getList($apiRequest) {
  $entity = _civicrm_api_get_entity_name_from_camel($apiRequest['entity']);
  $request = $apiRequest['params'];
  $meta = civicrm_api3_generic_getfields(array('action' => 'get') + $apiRequest, FALSE);

  // Hey api, would you like to provide default values?
  $fnName = "_civicrm_api3_{$entity}_getlist_defaults";
  $defaults = function_exists($fnName) ? $fnName($request) : array();
  _civicrm_api3_generic_getList_defaults($entity, $request, $defaults, $meta['values']);

  // Hey api, would you like to format the search params?
  $fnName = "_civicrm_api3_{$entity}_getlist_params";
  $fnName = function_exists($fnName) ? $fnName : '_civicrm_api3_generic_getlist_params';
  $fnName($request);

  $request['params']['check_permissions'] = !empty($apiRequest['params']['check_permissions']);
  $result = civicrm_api3($entity, 'get', $request['params']);

  // Hey api, would you like to format the output?
  $fnName = "_civicrm_api3_{$entity}_getlist_output";
  $fnName = function_exists($fnName) ? $fnName : '_civicrm_api3_generic_getlist_output';
  $values = $fnName($result, $request, $entity, $meta['values']);

  _civicrm_api3_generic_getlist_postprocess($result, $request, $values);

  $output = array('page_num' => $request['page_num']);

  // Limit is set for searching but not fetching by id
  if (!empty($request['params']['options']['limit'])) {
    // If we have an extra result then this is not the last page
    $last = $request['params']['options']['limit'] - 1;
    $output['more_results'] = isset($values[$last]);
    unset($values[$last]);
  }

  return civicrm_api3_create_success($values, $request['params'], $entity, 'getlist', CRM_Core_DAO::$_nullObject, $output);
}

/**
 * Set defaults for api.getlist.
 *
 * @param string $entity
 * @param array $request
 * @param array $apiDefaults
 * @param array $fields
 */
function _civicrm_api3_generic_getList_defaults($entity, &$request, $apiDefaults, $fields) {
  $config = CRM_Core_Config::singleton();
  $defaults = array(
    'page_num' => 1,
    'input' => '',
    'image_field' => NULL,
    'id_field' => $entity == 'option_value' ? 'value' : 'id',
    'description_field' => array(),
    'params' => array(),
    'extra' => array(),
  );
  // Find main field from meta
  foreach (array('sort_name', 'title', 'label', 'name', 'subject') as $field) {
    if (isset($fields[$field])) {
      $defaults['label_field'] = $defaults['search_field'] = $field;
      break;
    }
  }
  // Find fields to be used for the description
  foreach (array('description') as $field) {
    if (isset($fields[$field])) {
      $defaults['description_field'][] = $field;
    }
  }
  $resultsPerPage = Civi::settings()->get('search_autocomplete_count');
  if (isset($request['params']) && isset($apiDefaults['params'])) {
    $request['params'] += $apiDefaults['params'];
  }
  $request += $apiDefaults + $defaults;
  // Default api params
  $params = array(
    'options' => array(
      // Adding one extra result allows us to see if there are any more
      'limit' => $resultsPerPage + 1,
      // Because sql is zero-based
      'offset' => ($request['page_num'] - 1) * $resultsPerPage,
      'sort' => $request['label_field'],
    ),
    'sequential' => 1,
  );
  // When searching e.g. autocomplete
  if ($request['input']) {
    $params[$request['search_field']] = array('LIKE' => ($config->includeWildCardInName ? '%' : '') . $request['input'] . '%');
  }
  // When looking up a field e.g. displaying existing record
  if (!empty($request['id'])) {
    if (is_string($request['id']) && strpos($request['id'], ',')) {
      $request['id'] = explode(',', trim($request['id'], ', '));
    }
    // Don't run into search limits when prefilling selection
    $params['options']['limit'] = NULL;
    unset($params['options']['offset'], $request['params']['options']['limit'], $request['params']['options']['offset']);
    $params[$request['id_field']] = is_array($request['id']) ? array('IN' => $request['id']) : $request['id'];
  }
  $request['params'] += $params;
}

/**
 * Fallback implementation of getlist_params. May be overridden by individual apis.
 *
 * @param array $request
 */
function _civicrm_api3_generic_getlist_params(&$request) {
  $fieldsToReturn = array($request['id_field'], $request['label_field']);
  if (!empty($request['image_field'])) {
    $fieldsToReturn[] = $request['image_field'];
  }
  if (!empty($request['description_field'])) {
    $fieldsToReturn = array_merge($fieldsToReturn, (array) $request['description_field']);
  }
  $request['params']['return'] = array_unique(array_merge($fieldsToReturn, $request['extra']));
}

/**
 * Fallback implementation of getlist_output. May be overridden by individual api functions.
 *
 * @param array $result
 * @param array $request
 * @param string $entity
 * @param array $fields
 *
 * @return array
 */
function _civicrm_api3_generic_getlist_output($result, $request, $entity, $fields) {
  $output = array();
  if (!empty($result['values'])) {
    foreach ($result['values'] as $row) {
      $data = array(
        'id' => $row[$request['id_field']],
        'label' => $row[$request['label_field']],
      );
      if (!empty($request['description_field'])) {
        $data['description'] = array();
        foreach ((array) $request['description_field'] as $field) {
          if (!empty($row[$field])) {
            if (!isset($fields[$field]['pseudoconstant'])) {
              $data['description'][] = $row[$field];
            }
            else {
              $data['description'][] = CRM_Core_PseudoConstant::getLabel(
                _civicrm_api3_get_BAO($entity),
                $field,
                $row[$field]
              );
            }
          }
        }
      };
      if (!empty($request['image_field'])) {
        $data['image'] = isset($row[$request['image_field']]) ? $row[$request['image_field']] : '';
      }
      $output[] = $data;
    }
  }
  return $output;
}

/**
 * Common postprocess for getlist output
 *
 * @param $result
 * @param $request
 * @param $values
 */
function _civicrm_api3_generic_getlist_postprocess($result, $request, &$values) {
  $chains = array();
  foreach ($request['params'] as $field => $param) {
    if (substr($field, 0, 4) === 'api.') {
      $chains[] = $field;
    }
  }
  if (!empty($result['values'])) {
    foreach (array_values($result['values']) as $num => $row) {
      foreach ($request['extra'] as $field) {
        $values[$num]['extra'][$field] = isset($row[$field]) ? $row[$field] : NULL;
      }
      foreach ($chains as $chain) {
        $values[$num][$chain] = isset($row[$chain]) ? $row[$chain] : NULL;
      }
    }
  }
}

/**
 * Provide metadata for this api
 *
 * @param array $params
 * @param array $apiRequest
 */
function _civicrm_api3_generic_getlist_spec(&$params, $apiRequest) {
  $params += array(
    'page_num' => array(
      'title' => 'Page Number',
      'description' => "Current page of a multi-page lookup",
      'type' => CRM_Utils_Type::T_INT,
    ),
    'input' => array(
      'title' => 'Search Input',
      'description' => "String to search on",
      'type' => CRM_Utils_Type::T_TEXT,
    ),
    'params' => array(
      'title' => 'API Params',
      'description' => "Additional filters to send to the {$apiRequest['entity']} API.",
    ),
    'extra' => array(
      'title' => 'Extra',
      'description' => 'Array of additional fields to return.',
    ),
    'image_field' => array(
      'title' => 'Image Field',
      'description' => "Field that this entity uses to store icons (usually automatic)",
      'type' => CRM_Utils_Type::T_TEXT,
    ),
    'id_field' => array(
      'title' => 'ID Field',
      'description' => "Field that uniquely identifies this entity (usually automatic)",
      'type' => CRM_Utils_Type::T_TEXT,
    ),
    'description_field' => array(
      'title' => 'Description Field',
      'description' => "Field that this entity uses to store summary text (usually automatic)",
      'type' => CRM_Utils_Type::T_TEXT,
    ),
    'label_field' => array(
      'title' => 'Label Field',
      'description' => "Field to display as title of results (usually automatic)",
      'type' => CRM_Utils_Type::T_TEXT,
    ),
    'search_field' => array(
      'title' => 'Search Field',
      'description' => "Field to search on (assumed to be the same as label field unless otherwise specified)",
      'type' => CRM_Utils_Type::T_TEXT,
    ),
  );
}
