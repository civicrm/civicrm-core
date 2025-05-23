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
 * This api exposes CiviCRM Domain configuration settings.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Get CiviCRM Domain details.
 *
 * @param array $params
 *
 * @return array
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_domain_get($params) {

  $params['version'] = $params['domain_version'] ?? NULL;
  unset($params['version']);

  if (!empty($params['current_domain'])) {
    $params['id'] = CRM_Core_Config::domainID();
  }
  if (!empty($params['options']['is_count'])) {
    return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
  }

  // If requesting current domain, read from cache
  if (!empty($params['id']) && $params['id'] == CRM_Core_Config::domainID()) {
    $bao = CRM_Core_BAO_Domain::getDomain();
    $domains = [$params['id'] => $bao->toArray()];
  }
  else {
    $bao = new CRM_Core_BAO_Domain();
    _civicrm_api3_dao_set_filter($bao, $params, TRUE);
    $domains = _civicrm_api3_dao_to_array($bao, $params, TRUE, 'Domain');
  }

  foreach ($domains as $domain) {
    if (!empty($domain['contact_id'])) {
      $values = [];
      $locparams = [
        'contact_id' => $domain['contact_id'],
      ];
      $values['location'] = CRM_Core_BAO_Location::getValues($locparams, TRUE);
      $address_array = [
        'street_address', 'supplemental_address_1', 'supplemental_address_2', 'supplemental_address_3',
        'city', 'state_province_id', 'postal_code', 'country_id',
        'geo_code_1', 'geo_code_2',
      ];

      if (!empty($values['location']['email'])) {
        $domain['domain_email'] = $values['location']['email'][1]['email'] ?? NULL;
      }

      if (!empty($values['location']['phone'])) {
        $domain['domain_phone'] = [
          'phone_type' => CRM_Core_PseudoConstant::getLabel('CRM_Core_BAO_Phone', 'phone_type_id',
            $values['location']['phone'][1]['phone_type_id'] ?? NULL),
          'phone' => $values['location']['phone'][1]['phone'] ?? NULL,
        ];
      }

      if (!empty($values['location']['address'])) {
        foreach ($address_array as $value) {
          $domain['domain_address'][$value] = $values['location']['address'][1][$value] ?? NULL;
        }
      }

      list($domain['from_name'],
        $domain['from_email']
      ) = CRM_Core_BAO_Domain::getNameAndEmail(TRUE);

      // Rename version to domain_version, see CRM-17430.
      $domain['domain_version'] = $domain['version'];
      unset($domain['version']);
      $domains[$domain['id']] = array_merge($domains[$domain['id']], $domain);
    }
  }

  return civicrm_api3_create_success($domains, $params, 'Domain', 'get', $bao);
}

/**
 * Adjust Metadata for Get action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_domain_get_spec(&$params) {
  $params['current_domain'] = [
    'title' => "Current Domain",
    'description' => "get loaded domain",
  ];
}

/**
 * Create a new Domain.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_domain_create($params) {
  if (isset($params['domain_version'])) {
    $params['version'] = $params['domain_version'];
  }
  else {
    unset($params['version']);
  }
  $result = _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Domain');

  $result_value = CRM_Utils_Array::first($result['values']);
  if (isset($result_value['version'])) {
    // Rename version to domain_version, see CRM-17430.
    $result_value['domain_version'] = $result_value['version'];
    unset($result_value['version']);
    $result['values'][$result['id']] = $result_value;
  }
  return $result;
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_domain_create_spec(&$params) {
  $params['domain_version'] = [
    'title' => "CiviCRM Version",
    'description' => "The civicrm version this instance is running",
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['domain_version']['api.required'] = 1;
  unset($params['version']);
  $params['name']['api.required'] = 1;
}
