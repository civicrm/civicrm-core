<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * This api exposes CiviCRM Campaign records.
 *
 * @note Campaign component must be enabled.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create/update Campaign.
 *
 * This API is used to create new campaign or update any of the existing
 * In case of updating existing campaign, id of that particular campaign must
 * be in $params array.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_campaign_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Campaign');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_campaign_create_spec(&$params) {
  $params['title']['api.required'] = 1;
  $params['is_active']['api.default'] = 1;
}

/**
 * Returns array of campaigns matching a set of one or more properties.
 *
 * @param array $params
 *   Array per getfields
 *
 * @return array
 *   Array of matching campaigns
 */
function civicrm_api3_campaign_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'Campaign');
}

/**
 * Delete an existing campaign.
 *
 * This method is used to delete any existing campaign.
 * Id of the campaign to be deleted is required field in $params array
 *
 * @param array $params
 *   array containing id of the group to be deleted
 *
 * @return array
 */
function civicrm_api3_campaign_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Get campaign list parameters.
 *
 * @see _civicrm_api3_generic_getlist_params
 *
 * @param array $request
 */
function _civicrm_api3_campaign_getlist_params(&$request) {
  $fieldsToReturn = ['title', 'campaign_type_id', 'start_date', 'end_date'];
  $request['params']['return'] = array_unique(array_merge($fieldsToReturn, $request['extra']));
  if (empty($request['params']['id'])) {
    $request['params'] += [
      'is_active' => 1,
    ];
  }
}

/**
 * Get campaign list output.
 *
 * @see _civicrm_api3_generic_getlist_output
 *
 * @param array $result
 * @param array $request
 *
 * @return array
 */
function _civicrm_api3_campaign_getlist_output($result, $request) {
  $output = [];
  if (!empty($result['values'])) {
    foreach ($result['values'] as $row) {
      $data = [
        'id' => $row[$request['id_field']],
        'label' => $row[$request['label_field']],
        'description' => [
          CRM_Core_Pseudoconstant::getLabel(
            'CRM_Campaign_BAO_Campaign',
            'campaign_type_id',
            $row['campaign_type_id']
          ),
        ],
      ];
      $config = CRM_Core_Config::singleton();
      $data['description'][0] .= ': ' . CRM_Utils_Date::customFormat($row['start_date'], $config->dateformatFull) . ' - ';
      if (!empty($row['end_date'])) {
        $data['description'][0] .= CRM_Utils_Date::customFormat($row['end_date'], $config->dateformatFull);
      }
      $output[] = $data;
    }
  }
  return $output;
}