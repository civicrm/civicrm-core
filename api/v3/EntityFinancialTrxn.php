<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * This api exposes CiviCRM FinancialType.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Save a Entity Financial Trxn.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_entity_financial_trxn_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Get a Entity Financial Trxn.
 *
 * @param array $params
 *
 * @return array
 *   Array of retrieved Entity Financial Trxn property values.
 */
function civicrm_api3_entity_financial_trxn_get($params) {
  $sql = CRM_Utils_SQL_Select::fragment();
  _civicrm_api3_entity_financial_trxn_get_extraFilters($params, $sql);
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'EntityFinancialTrxn', $sql);
}

function _civicrm_api3_entity_financial_trxn_get_extraFilters(&$params, &$sql) {
  $rels = array(
    'batch_id' => array(
      'join' => '!joinType civicrm_entity_batch ON (civicrm_entity_batch.entity_table = "civicrm_financial_trxn" AND civicrm_entity_batch.entity_id = a.financial_trxn_id)',
      'column' => 'batch_id',
    ),
    'tag_id' => array(
      'join' => '
        LEFT JOIN civicrm_contribution cc ON (a.entity_table = "civicrm_contribution" AND a.entity_id = cc.id)
        LEFT JOIN civicrm_entity_tag ON (civicrm_entity_tag.contact_id = cc.contact_id)',
      'column' => 'tag_id',
    ),
  );
  foreach ($rels as $filter => $relSpec) {
    if (!empty($params[$filter])) {
      if (!is_array($params[$filter])) {
        $params[$filter] = array('=' => $params[$filter]);
      }
      // $mode is one of ('LEFT JOIN', 'INNER JOIN', 'SUBQUERY')
      $mode = isset($params[$filter]['IS NULL']) ? 'LEFT JOIN' : 'INNER JOIN';
      $clause = \CRM_Core_DAO::createSQLFilter($relSpec['column'], $params[$filter]);
      if ($clause) {
        $sql->join('', $relSpec['join'], array('joinType' => $mode));
        $sql->where($clause);
      }
    }
  }
}

/**
 * Delete a Entity Financial Trxn.
 *
 * @param array $params
 *
 * @return array
 *   Array of deleted values.
 */
function civicrm_api3_entity_financial_trxn_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_entity_financial_trxn_create_spec(&$params) {
  $params['entity_table']['api.required'] = 1;
  $params['entity_id']['api.required'] = 1;
  $params['financial_trxn_id']['api.required'] = 1;
  $params['amount']['api.required'] = 1;
}
