<?php

/*
 +--------------------------------------------------------------------+
| CiviCRM version 4.4                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * Generic api wrapper used for quicksearch and autocomplete
 *
 * @param $apiRequest array
 * @return mixed
 */
function civicrm_api3_generic_getList($apiRequest) {
  $entity = _civicrm_api_get_entity_name_from_camel($apiRequest['entity']);
  $request = $apiRequest['params'];
  
  _civicrm_api3_generic_getList_defaults($entity, $request);
  
  // Hey api, would you like to format the search params?
  $fnName = "_civicrm_api3_{$entity}_getlist_params";
  $fnName = function_exists($fnName) ? $fnName : '_civicrm_api3_generic_getlist_params';
  $fnName($request);
  
  $result = civicrm_api3($entity, 'get', $request['params']);

  // Hey api, would you like to format the output?
  $fnName = "_civicrm_api3_{$entity}_getlist_output";
  $fnName = function_exists($fnName) ? $fnName : '_civicrm_api3_generic_getlist_output';
  $values = $fnName($result, $request);

  $output = array(
    // If we have an extra result then this is not the last page
    'more_results' => isset($values[10]),
    'page_num' => $request['page_num'],
  );
  unset($values[10]);

  return civicrm_api3_create_success($values, $request['params'], $entity, 'getlist', CRM_Core_DAO::$_nullObject, $output);
}

/**
 * Set defaults for api.getlist
 *
 * @param $entity string
 * @param $request array
 */
function _civicrm_api3_generic_getList_defaults($entity, &$request) {
  $config = CRM_Core_Config::singleton();
  $fields = _civicrm_api_get_fields($entity);
  $defaults = array(
    'page_num' => 1,
    'input' => '',
    'image_field' => NULL,
    'id_field' => 'id',
    'params' => array(),
  );
  // Find main field from meta
  foreach (array('sort_name', 'title', 'label', 'name') as $field) {
    if (isset($fields[$field])) {
      $defaults['label_field'] = $defaults['search_field'] = $field;
      break;
    }
  }
  foreach (array('description') as $field) {
    if (isset($fields[$field])) {
      $defaults['description_field'] = $field;
      break;
    }
  }
  $resultsPerPage = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'search_autocomplete_count', NULL, 10);
  $request += $defaults;
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
    if (is_string($request['id']) && strpos(',', $request['id'])) {
      $request['id'] = explode(',', $request['id']);
    }
    $params[$request['id_field']] = is_array($request['id']) ? array('IN' => $request['id']) : $request['id'];
  }
  $request['params'] += $params;
}

/**
 * Fallback implementation of getlist_params. May be overridden by individual apis
 *
 * @param $request array
 */
function _civicrm_api3_generic_getlist_params(&$request) {
  $fieldsToReturn = array($request['id_field'], $request['label_field']);
  !empty($request['image_field']) && $fieldsToReturn[] = $request['image_field'];
  !empty($request['description_field']) && $fieldsToReturn[] = $request['description_field'];
  $request['params']['options']['return'] = $fieldsToReturn;
}

/**
 * Fallback implementation of getlist_output. May be overridden by individual apis
 *
 * @param $result array
 * @param $request array
 *
 * @return array
 */
function _civicrm_api3_generic_getlist_output($result, $request) {
  $output = array();
  if (!empty($result['values'])) {
    foreach ($result['values'] as $row) {
      $output[] = array(
        'id' => $row[$request['id_field']],
        'label' => $row[$request['label_field']],
        'description' => isset($request['description_field']) && isset($row[$request['description_field']]) ? $row[$request['description_field']] : NULL,
        'image' => isset($request['image_field']) && isset($row[$request['image_field']]) ? $row[$request['image_field']] : NULL,
      );
    }
  }
  return $output;
}
