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
 * This api exposes CiviCRM configuration settings.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Get fields for setting api calls.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_setting_getfields($params) {
  if (!empty($params['action']) && strtolower($params['action']) == 'getvalue') {
    $result = [
      'name' => [
        'title' => 'name of setting field',
        'api.required' => 1,
        'type' => CRM_Utils_Type::T_STRING,
      ],
      'group' => [
        'api.required' => 0,
        'title' => 'Setting Group',
        'description' => 'Settings Group. This is required if the setting is not stored in config',
        'type' => CRM_Utils_Type::T_STRING,
      ],
    ];
    return civicrm_api3_create_success($result, $params, 'Setting', 'getfields');
  }
  if (!empty($params['name'])) {
    //am of two minds about special handling for 'name' as opposed to other filters - but is does make most common
    //usage really easy
    $params['filters']['name'] = $params['name'];
  }
  $result = CRM_Core_BAO_Setting::getSettingSpecification(
    $params['component_id'] ?? NULL,
    $params['filters'] ?? [],
    $params['domain_id'] ?? NULL,
    $params['profile'] ?? NULL
  );
  // find any supplemental information
  if (!empty($params['action'])) {
    $specFunction = '_civicrm_api3_setting_' . strtolower($params['action']) . '_spec';
    if (function_exists($specFunction)) {
      $specFunction($result);
    }
  }
  return civicrm_api3_create_success($result, $params, 'Setting', 'getfields');
}

/**
 * Alter metadata for getfields functions.
 *
 * @param array $params
 */
function _civicrm_api3_setting_getfields_spec(&$params) {
  $params['filters'] = [
    'title' => 'Filters',
    'description' => 'Fields you wish to filter by e.g. array("group_name" => "CiviCRM Preferences")',
  ];
  $params['component_id'] = [
    'title' => 'Component ID',
    'description' => 'ID of relevant component',
  ];
  $params['profile'] = [
    'title' => 'Profile',
    'description' => 'Profile is passed through to hooks & added to cachestring',
  ];
}

/**
 * Return default values for settings.
 *
 * We will domain key this as it could vary by domain (ie. urls)
 * as we will be creating the option for a function rather than an value to be in the defaults
 * Note that is not in place as yet.
 *
 * @param array $params
 *
 * @return array
 * @throws \CRM_Core_Exception
 * @throws \Exception
 */
function civicrm_api3_setting_getdefaults($params) {
  $settings = civicrm_api3('Setting', 'getfields', $params);
  $domains = _civicrm_api3_setting_getDomainArray($params);
  $defaults = [];
  foreach ($domains as $domainID) {
    $defaults[$domainID] = [];
    foreach ($settings['values'] as $setting => $spec) {
      if (array_key_exists('default', $spec) && !is_null($spec['default'])) {
        $defaults[$domainID][$setting] = $spec['default'];
      }
    }
  }
  return civicrm_api3_create_success($defaults, $params, 'Setting', 'getfields');
}

/**
 * Metadata for Setting create function.
 *
 * @param array $params
 *   Parameters as passed to the API.
 */
function _civicrm_api3_setting_getdefaults_spec(&$params) {
  $params['domain_id'] = [
    'api.default' => 'current_domain',
    'description' => 'Defaults may differ by domain - if you do not pass in a domain id this will default to the current domain
      an array or "all" are acceptable values for multiple domains',
    'title' => 'Setting Domain',
  ];
}

/**
 * Get options for settings.
 *
 * @param array $params
 *
 * @return array
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_setting_getoptions($params) {
  $domainId = $params['domain_id'] ?? NULL;
  $specs = \Civi\Core\SettingsMetadata::getMetadata(['name' => $params['field']], $domainId, TRUE);

  if (!isset($specs[$params['field']]['options']) || !is_array($specs[$params['field']]['options'])) {
    throw new CRM_Core_Exception("The field '" . $params['field'] . "' has no associated option list.");
  }

  return civicrm_api3_create_success($specs[$params['field']]['options'], $params, 'Setting', 'getoptions');
}

/**
 * Revert settings to defaults.
 *
 * @param array $params
 *
 * @return array
 * @throws \Exception
 */
function civicrm_api3_setting_revert($params) {
  $defaults = civicrm_api('Setting', 'getdefaults', $params);
  $allSettings = civicrm_api('Setting', 'getfields', $params)['values'] ?? [];
  // constant settings can't be set through the API, so can't be reverted
  // so we must filter them out here
  $revertable = array_filter($allSettings, function ($settingMeta) {
    return !($settingMeta['is_constant'] ?? FALSE);
  });
  $domains = _civicrm_api3_setting_getDomainArray($params);
  $result = [];
  $isError = FALSE;
  foreach ($domains as $domainID) {
    $valuesToRevert = array_intersect_key($defaults['values'][$domainID], $revertable);
    if (!empty($valuesToRevert)) {
      $valuesToRevert['version'] = $params['version'];
      $valuesToRevert['domain_id'] = $domainID;
      // note that I haven't looked at how the result would appear with multiple domains in play
      $result = array_merge($result, civicrm_api('Setting', 'create', $valuesToRevert));
      if ($result['is_error'] ?? FALSE) {
        $isError = TRUE;
      }
    }
  }

  if ($isError) {
    return civicrm_api3_create_error('Error reverting settings');
  }

  return civicrm_api3_create_success($result, $params, 'Setting', 'revert');
}

/**
 * Alter metadata for getfields functions.
 *
 * @param array $params
 */
function _civicrm_api3_setting_revert_spec(&$params) {
  $params['name'] = [
    'title' => 'Name',
    'description' => 'Setting Name belongs to',
  ];
  $params['component_id'] = [
    'title' => 'Component ID',
    'description' => 'ID of relevant component',
  ];
  $params['domain_id'] = [
    'api.default' => 'current_domain',
    'description' => 'Defaults may differ by domain - if you do not pass in a domain id this will default to the current domain'
    . ' an array or "all" are acceptable values for multiple domains',
    'title' => 'Setting Domain',
  ];
}

/**
 * Revert settings to defaults.
 *
 * @param array $params
 * @deprecated
 * @return array
 * @throws \CRM_Core_Exception
 * @throws \Exception
 */
function civicrm_api3_setting_fill($params) {
  $defaults = civicrm_api3('Setting', 'getdefaults', $params);
  $domains = _civicrm_api3_setting_getDomainArray($params);
  $result = [];
  foreach ($domains as $domainID) {
    $apiArray = [
      'version' => $params['version'],
      'domain_id' => $domainID,
    ];
    $existing = civicrm_api3('Setting', 'get', $apiArray);
    $valuesToFill = array_diff_key($defaults['values'][$domainID], $existing['values'][$domainID]);
    if (!empty($valuesToFill)) {
      $result = array_merge($result, civicrm_api('Setting', 'create', $valuesToFill + $apiArray));
    }
  }
  return civicrm_api3_create_success($result, $params, 'Setting', 'fill');
}

/**
 * Alter metadata for getfields functions.
 *
 * @param array $params
 */
function _civicrm_api3_setting_fill_spec(&$params) {
  $params['name'] = [
    'title' => 'Name',
    'description' => 'Setting Name belongs to',
  ];
  $params['component_id'] = [
    'title' => 'Component ID',
    'description' => 'ID of relevant component',
  ];
  $params['domain_id'] = [
    'api.default' => 'current_domain',
    'title' => 'Setting Domain',
    'description' => 'Defaults may differ by domain - if you do not pass in a domain id this will default to the '
    . 'current domain, an array or "all" are acceptable values for multiple domains',
  ];
}

/**
 * Declare deprecated api functions.
 *
 * @return array
 */
function _civicrm_api3_setting_deprecation() {
  return ['fill' => 'Setting "fill" is no longer necessary.'];
}

/**
 * Create or update a setting.
 *
 * @param array $params
 *   Parameters as per getfields.
 *
 * @return array
 *   api result array
 *
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_setting_create($params) {
  $domains = _civicrm_api3_setting_getDomainArray($params);
  $result = CRM_Core_BAO_Setting::setItems($params, $domains);
  return civicrm_api3_create_success($result, $params, 'Setting', 'create');
}

/**
 * Metadata for setting create function.
 *
 * @param array $params
 *   Parameters as passed to the API.
 */
function _civicrm_api3_setting_create_spec(&$params) {
  $params['domain_id'] = [
    'api.default' => 'current_domain',
    'title' => 'Setting Domain',
    'description' => 'if you do not pass in a domain id this will default to the current domain
      an array or "all" are acceptable values for multiple domains',
  ];
  $params['group'] = [
    'title' => 'Setting Group',
    'description' => 'if you know the group defining it will make the api more efficient',
  ];
}

/**
 * Returns array of settings matching input parameters.
 *
 * @param array $params
 *   Array of one or more valid property_name=>value pairs.
 *
 * @return array
 *   Array of matching settings
 */
function civicrm_api3_setting_get($params) {
  $domains = _civicrm_api3_setting_getDomainArray($params);
  $returnSettings = (array) ($params['return'] ?? []);
  if (in_array('contribution_invoice_settings', $returnSettings)) {
    CRM_Core_Error::deprecatedWarning('contribution_invoice_settings is not a valid setting. Request the actual setting');
  }
  $result = CRM_Core_BAO_Setting::getItems($params, $domains, $returnSettings);
  return civicrm_api3_create_success($result, $params, 'Setting', 'get');
}

/**
 * Metadata for setting create function.
 *
 * @param array $params
 *   Parameters as passed to the API.
 */
function _civicrm_api3_setting_get_spec(&$params) {
  $params['domain_id'] = [
    'api.default' => 'current_domain',
    'title' => 'Setting Domain',
    'description' => 'if you do not pass in a domain id this will default to the current domain',
  ];
  $params['group'] = [
    'title' => 'Setting Group',
    'description' => 'if you know the group defining it will make the api more efficient',
  ];
}

/**
 * Returns value for specific parameter.
 *
 * Function requires more fields than 'get' but is intended for
 * runtime usage & should be quicker
 *
 * @param array $params
 *   Array of one or more valid.
 *                       property_name=>value pairs.
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_setting_getvalue($params) {
  //$config = CRM_Core_Config::singleton();
  //if (isset($config->$params['name'])) {
  //  return $config->$params['name'];
  //}
  return CRM_Core_BAO_Setting::getItem(
    NULL,
    $params['name'] ?? NULL,
    $params['component_id'] ?? NULL,
    $params['default_value'] ?? NULL,
    $params['contact_id'] ?? NULL,
    $params['domain_id'] ?? NULL
  );
}

/**
 * Metadata for setting create function.
 *
 * @param array $params
 *   Parameters as passed to the API.
 */
function _civicrm_api3_setting_getvalue_spec(&$params) {

  $params['group'] = [
    'title' => 'Settings Group',
    'api.required' => TRUE,
  ];
  $params['name'] = [
    'title' => 'Setting Name',
    'api.aliases' => ['return'],
  ];
  $params['default_value'] = [
    'title' => 'Default Value',
  ];
  $params['component_id'] = [
    'title' => 'Component Id',
  ];
  $params['contact_id'] = [
    'title' => 'Contact Id',
  ];
  $params['domain_id'] = [
    'title' => 'Setting Domain',
    'description' => 'if you do not pass in a domain id this will default to the current domain',
  ];
}

/**
 * Converts domain input into an array.
 *
 * If an array is passed in this is used, if 'all' is passed
 * in this is converted to 'all arrays'
 *
 * Really domain_id should always be set but doing an empty check because at the moment
 * using crm-editable will pass an id & default won't be applied
 * we did talk about id being a pseudonym for domain_id in this api so applying it here.
 *
 * @param array $params
 *
 * @return array
 * @throws CRM_Core_Exception
 */
function _civicrm_api3_setting_getDomainArray(&$params) {
  if (empty($params['domain_id']) && isset($params['id'])) {
    $params['domain_id'] = $params['id'];
  }

  if ($params['domain_id'] === 'current_domain') {
    $params['domain_id'] = CRM_Core_Config::domainID();
  }

  if ($params['domain_id'] === 'all') {
    $domainAPIResult = civicrm_api('domain', 'get', ['version' => 3, 'return' => 'id']);
    if (isset($domainAPIResult['values'])) {
      $params['domain_id'] = array_keys($domainAPIResult['values']);
    }
    else {
      throw new CRM_Core_Exception('All domains not retrieved - problem with Domain Get api call ' . $domainAPIResult['error_message']);
    }
  }
  if (is_array($params['domain_id'])) {
    $domains = $params['domain_id'];
  }
  else {
    $domains = [$params['domain_id']];
  }
  return $domains;
}
