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
 * @throws \API_Exception
 */
function civicrm_api3_domain_get($params) {

  $params['version'] = CRM_Utils_Array::value('domain_version', $params);
  unset($params['version']);

  $bao = new CRM_Core_BAO_Domain();
  if (!empty($params['current_domain'])) {
    $domainBAO = CRM_Core_Config::domainID();
    $params['id'] = $domainBAO;
  }
  if (!empty($params['options']) && !empty($params['options']['is_count'])) {
    return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
  }

  _civicrm_api3_dao_set_filter($bao, $params, TRUE);
  $domains = _civicrm_api3_dao_to_array($bao, $params, TRUE, 'Domain');

  foreach ($domains as $domain) {
    if (!empty($domain['contact_id'])) {
      $values = array();
      $locparams = array(
        'contact_id' => $domain['contact_id'],
      );
      $values['location'] = CRM_Core_BAO_Location::getValues($locparams, TRUE);
      $address_array = array(
        'street_address', 'supplemental_address_1', 'supplemental_address_2',
        'city', 'state_province_id', 'postal_code', 'country_id',
        'geo_code_1', 'geo_code_2',
      );

      if (!empty($values['location']['email'])) {
        $domain['domain_email'] = CRM_Utils_Array::value('email', $values['location']['email'][1]);
      }

      if (!empty($values['location']['phone'])) {
        $domain['domain_phone'] = array(
          'phone_type' => CRM_Core_PseudoConstant::getLabel('CRM_Core_BAO_Phone', 'phone_type_id',
            CRM_Utils_Array::value(
              'phone_type_id',
              $values['location']['phone'][1]
            )
          ),
          'phone' => CRM_Utils_Array::value(
            'phone',
            $values['location']['phone'][1]
          ),
        );
      }

      if (!empty($values['location']['address'])) {
        foreach ($address_array as $value) {
          $domain['domain_address'][$value] = CRM_Utils_Array::value($value,
          $values['location']['address'][1]
          );
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
  $params['current_domain'] = array(
    'title' => "Current Domain",
    'description' => "get loaded domain",
  );
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
  $result = _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);

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
  $params['domain_version'] = array(
    'title' => "CiviCRM Version",
    'description' => "The civicrm version this instance is running",
    'type' => CRM_Utils_Type::T_STRING,
  );
  $params['domain_version']['api.required'] = 1;
  unset($params['version']);
  $params['name']['api.required'] = 1;
}
