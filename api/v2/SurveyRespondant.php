<?php
// $Id$

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright Tech To The People (c) 2010                              |
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
 * File for the CiviCRM APIv2 Petition Signatures functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Contribute
 */

/**
 * Include utility functions
 */
require_once 'api/v2/utils.php';
require_once 'CRM/Utils/Rule.php';
require_once 'CRM/Campaign/BAO/Petition.php';

/**
 * Get the list of signatories
 *
 * @param  array   $params           (reference ) input parameters
 *
 * @return array (reference )        contribution_id of created or updated record
 * @static void
 * @access public
 */
function &civicrm_survey_respondant_get(&$params) {
  _civicrm_initialize();

  if (empty($params)) {
    return civicrm_create_error(ts('No input parameters present'));
  }

  if (!is_array($params)) {
    return civicrm_create_error(ts('Input parameters is not an array'));
  }

  if (!array_key_exists('survey_id', $params)) {
    return (civicrm_create_error('survey_id mandatory'));
  }

  if (array_key_exists('status_id', $params)) {
    $status_id = $params['status_id'];
  }
  else {
    $status_id = NULL;
  }

  $petition = new CRM_Campaign_BAO_Petition();
  $signatures = $petition->getPetitionSignature($params['survey_id'], $status_id);
  return ($signatures);
}

function &civicrm_survey_respondant_count(&$params) {
  _civicrm_initialize();
  $petition = new CRM_Campaign_BAO_Petition();
  if (array_key_exists('groupby', $params) && $params['groupby'] == 'country') {
    $signaturesCount = $petition->getPetitionSignatureTotalbyCountry($params['survey_id']);
  }
  else {
    $signaturesCount = $petition->getPetitionSignatureTotal($params['survey_id']);
  }
  return ($signaturesCount);
}

