<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * File for CiviCRM APIv3 settings
 *
 * @package CiviCRM_APIv3_Core
 * @subpackage API_Settings
 * @copyright CiviCRM LLC (c) 2004-2014
 * @version $Id: Settings.php
 *
 */

function civicrm_api3_setting_getfields($params) {
  if(!empty($params['action']) && strtolower($params['action']) == 'getvalue'){
    $result = array(
      'name' => array(
        'title' => 'name of setting field',
        'api.required' => 1,
        'type' => CRM_Utils_Type::T_STRING),
      'group' => array(
        'api.required' => 0,
        'title' => 'Setting Group',
        'description' => 'Settings Group. This is required if the setting is not stored in config',
        'type' => CRM_Utils_Type::T_STRING)
      );
    return civicrm_api3_create_success($result, $params, 'setting', 'getfields');
  }
  if(!empty($params['name'])){
    //am of two minds about special handling for 'name' as opposed to other filters - but is does make most common
    //usage really easy
    $params['filters']['name'] = $params['name'];
  }
  $result = CRM_Core_BAO_Setting::getSettingSpecification(
    CRM_Utils_Array::value('component_id',$params),
    CRM_Utils_Array::value('filters', $params, array()),
    CRM_Utils_Array::value('domain_id', $params, null),
    CRM_Utils_Array::value('profile', $params, null)
  );
  // find any supplemental information
  if (!empty($params['action'])){
    $specFunction = '_civicrm_api3_setting_' . strtolower($params['action']) . '_spec';
    if (function_exists($specFunction)) {
      $specFunction($result);
    }
  }
  return civicrm_api3_create_success($result, $params, 'setting', 'getfields');
}

/**
 * Alter metadata for getfields functions
 */
function _civicrm_api3_setting_getfields_spec(&$params) {
  $params['filters'] = array('title' => 'Fields you wish to filter by e.g. array("group_name" => "CiviCRM Preferences")');
  $params['component_id'] = array('title' => 'id of relevant component');
  $params['profile'] = array('title' => 'profile is passed through to hooks & added to cachestring');
}

/**
 * Return default values for settings. We will domain key this as it could vary by domain (ie. urls)
 * as we will be creating the option for a function rather than an value to be in the defaults
 * Note that is not in place as yet
 */
function civicrm_api3_setting_getdefaults(&$params){
  $settings = civicrm_api3('setting','getfields', $params);
  $domains = _civicrm_api3_setting_getDomainArray($params);
  $defaults = array();
  foreach ($domains as $domainID){
    $defaults[$domainID] = array();
    $noDefaults = array();
    foreach ($settings['values'] as $setting => $spec){
      if(array_key_exists('default', $spec) && !is_null($spec['default'])){
        $defaults[$domainID][$setting] = $spec['default'];
      }
      else{
        $noDefaults[$setting] = 1;
      }
    }
    if(!empty($params['debug'])){
      // we are only tracking 'noDefaults' to help us check the xml
      print_r($noDefaults);
    }
  }
  return civicrm_api3_create_success($defaults,$params,'setting','getfields');
}
/**
 * Metadata for setting create function
 *
 * @param array $params parameters as passed to the API
 */
function _civicrm_api3_setting_getdefaults_spec(&$params) {
  $params['domain_id'] = array(
    'api.default' => 'current_domain',
    'description' => 'Defaults may differ by domain - if you do not pass in a domain id this will default to the current domain
      an array or "all" are acceptable values for multiple domains',
    'title' => 'Setting Domain',
  );
}

/**
 * Revert settings to defaults
 */
function civicrm_api3_setting_revert(&$params){
  $defaults = civicrm_api('setting','getdefaults', $params);
  $fields = civicrm_api('setting','getfields', $params);
  $fields = $fields['values'];
  $domains = _civicrm_api3_setting_getDomainArray($params);
  $result = array();
  foreach ($domains as $domainID){
    $valuesToRevert = array_intersect_key($defaults['values'][$domainID], $fields);
    if(!empty($valuesToRevert)){
      $valuesToRevert['version'] = $params['version'];
      $valuesToRevert['domain_id'] = $domainID;
      // note that I haven't looked at how the result would appear with multiple domains in play
      $result = array_merge($result, civicrm_api('setting', 'create', $valuesToRevert));
    }
  }

  return civicrm_api3_create_success($result, $params, 'setting', 'revert');
}

/**
 * Alter metadata for getfields functions
 */
function _civicrm_api3_setting_revert_spec(&$params) {
  $params['name'] = array('title' => 'Setting Name belongs to');
  $params['component_id'] = array('title' => 'id of relevant component');
  $params['domain_id'] = array(
    'api.default' => 'current_domain',
    'description' => 'Defaults may differ by domain - if you do not pass in a domain id this will default to the current domain
      an array or "all" are acceptable values for multiple domains',
    'title' => 'Setting Domain',
  );
}

/**
 * Revert settings to defaults
 */
function civicrm_api3_setting_fill(&$params){
  $defaults = civicrm_api3('setting','getdefaults', $params);
  $domains = _civicrm_api3_setting_getDomainArray($params);
  $result = array();
  foreach ($domains as $domainID){
    $apiArray = array(
      'version' => $params['version'],
      'domain_id' => $domainID
    );
    $existing = civicrm_api3('setting','get', $apiArray);
    $valuesToFill = array_diff_key($defaults['values'][$domainID], $existing['values'][$domainID]);
    if(!empty($valuesToFill)){
      $result = array_merge($result, civicrm_api('setting', 'create', $valuesToFill + $apiArray));
    }
  }
  return civicrm_api3_create_success($result, $params, 'setting', 'fill');
}

/**
 * Alter metadata for getfields functions
 */
function _civicrm_api3_setting_fill_spec(&$params) {
  $params['name'] = array('title' => 'Setting Name belongs to');
  $params['component_id'] = array('title' => 'id of relevant component');
  $params['domain_id'] = array(
    'api.default' => 'current_domain',
    'title' => 'Setting Domain',
    'description' => 'Defaults may differ by domain - if you do not pass in a domain id this will default to the current domain
      an array or "all" are acceptable values for multiple domains'
  );
}

/**
 * Create or update a setting
 *
 * @param array $params  Associative array of setting
 *                       name/value pairs + other vars as applicable - see getfields for more
 * @example SettingCreate.php Std Create example
 *
 * @return array api result array
 * {@getfields setting_create}
 * @access public
 */
function civicrm_api3_setting_create($params) {
  $domains = _civicrm_api3_setting_getDomainArray($params);
  $result = CRM_Core_BAO_Setting::setItems($params, $domains);
  return civicrm_api3_create_success($result,$params,'setting','create');
}

/**
 * Metadata for setting create function
 *
 * @param array $params parameters as passed to the API
 */
function _civicrm_api3_setting_create_spec(&$params) {
  $params['domain_id'] = array(
    'api.default' => 'current_domain',
    'title' => 'Setting Domain',
    'description' => 'if you do not pass in a domain id this will default to the current domain
      an array or "all" are acceptable values for multiple domains'
   );
   $params['group'] = array(
     'title' => 'Setting Group',
     'description' => 'if you know the group defining it will make the api more efficient'
   )
  ;
}

/**
 * Returns array of settings matching input parameters
 *
 * @param array $params Array of one or more valid
 *                       property_name=>value pairs.
 *
 * @return array Array of matching settings
 * {@getfields setting_get}
 * @access public
 */
function civicrm_api3_setting_get($params) {
  $domains = _civicrm_api3_setting_getDomainArray($params);
  $result =   $result = CRM_Core_BAO_Setting::getItems($params, $domains, CRM_Utils_Array::value('return', $params, array()));
  return civicrm_api3_create_success($result, $params,'setting','get');
}
/**
 * Metadata for setting create function
 *
 * @param array $params parameters as passed to the API
 */
function _civicrm_api3_setting_get_spec(&$params) {
  $params['domain_id'] = array(
    'api.default' => 'current_domain',
    'title' => 'Setting Domain',
    'description' => 'if you do not pass in a domain id this will default to the current domain'
  );
  $params['group'] = array(
    'title' => 'Setting Group',
    'description' => 'if you know the group defining it will make the api more efficient'
  )
  ;
}
/**
 * Returns value for specific parameter. Function requires more fields than 'get' but is intended for
 * runtime usage & should be quicker
 *
 * @param array $params  (reference) Array of one or more valid
 *                       property_name=>value pairs.
 *
 * @return array Array of matching settings
 * {@getfields setting_get}
 * @access public
 */
function civicrm_api3_setting_getvalue($params) {
  $config = CRM_Core_Config::singleton();
  if(isset($config->$params['name'])){
    return $config->$params['name'];
  }
  return CRM_Core_BAO_Setting::getItem(
    $params['group'],
    CRM_Utils_Array::value('name', $params),
    CRM_Utils_Array::value('component_id', $params),
    CRM_Utils_Array::value('default_value', $params),
    CRM_Utils_Array::value('contact_id', $params),
    CRM_Utils_Array::value('domain_id', $params)
  );
}

/**
 * Metadata for setting create function
 *
 * @param array $params parameters as passed to the API
 */
function _civicrm_api3_setting_getvalue_spec(&$params) {

  $params['group'] = array(
      'title' => 'Settings Group',
      'api.required' => TRUE,
  );
  $params['name'] = array(
      'title' => 'Setting Name',
      'api.aliases' => array('return'),
  );
  $params['default_value'] = array(
      'title' => 'Default Value',
  );
  $params['component_id'] = array(
      'title' => 'Component Id',
  );
  $params['contact_id'] = array(
      'title' => 'Contact Id',
  );
  $params['domain_id'] = array(
    'title' => 'Setting Domain',
    'description' => 'if you do not pass in a domain id this will default to the current domain'
  );
}

/**
 * Converts domain input into an array. If an array is passed in this is used, if 'all' is passed
 * in this is converted to 'all arrays'
 *
 * Really domain_id should always be set but doing an empty check because at the moment
 * using crm-editable will pass an id & default won't be applied
 * we did talk about id being a pseudonym for domain_id in this api so applying it here
 */
function _civicrm_api3_setting_getDomainArray(&$params){
  if(empty($params['domain_id']) && isset($params['id'])){
    $params['domain_id'] = $params['id'];
  }

  if($params['domain_id'] == 'current_domain'){
    $params['domain_id']    = CRM_Core_Config::domainID();
  }

  if($params['domain_id'] == 'all'){
    $domainAPIResult = civicrm_api('domain','get',array('version' => 3, 'return' => 'id'));
    if (isset($domainAPIResult['values'])) {
      $params['domain_id'] = array_keys($domainAPIResult['values']);
    }
    else{
      throw new Exception('All domains not retrieved - problem with Domain Get api call ' . $domainAPIResult['error_message']);
    }
  }
  if(is_array($params['domain_id'])){
    $domains = $params['domain_id'];
  }
  else{
    $domains = array($params['domain_id']);
  }
  return $domains;
}

