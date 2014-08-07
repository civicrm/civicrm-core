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
 * File for the CiviCRM APIv3 domain functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_WordReplacement
 *
 * @copyright CiviCRM LLC (c) 2004-2014
 * @version $Id: Domain.php 30171 2010-10-14 09:11:27Z mover $
 *
 */

/**
 * Get CiviCRM Word Replacement details
 * {@getfields word_replacement_create}
 *
 */
function civicrm_api3_word_replacement_get($params) {
  $bao = new CRM_Core_BAO_WordReplacement();
  _civicrm_api3_dao_set_filter($bao, $params, true, 'WordReplacement');
  $wordReplacements = _civicrm_api3_dao_to_array($bao, $params, true,'WordReplacement');

  return civicrm_api3_create_success($wordReplacements, $params, 'word_replacement', 'get', $bao);
}


/**
 * Create a new Word Replacement
 *
 * @param array $params
 *
 * @return array
 *
 * {@getfields word_replacement_create}
 */
function civicrm_api3_word_replacement_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_word_replacement_create_spec(&$params) {
  unset($params['version']);
}

/**
 * delete an existing word_replacement
 *
 *
 * @param array $params array containing id of the word_replacement
 *  to be deleted
 *
 * @return array api result array
 *
 * @access public
 */
function civicrm_api3_word_replacement_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
