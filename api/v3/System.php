<?php
// $Id$

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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
 * File for the CiviCRM APIv3 domain functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Domain
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: Domain.php 30171 2010-10-14 09:11:27Z mover $
 *
 */

/**
 * Flush all system caches
 *
 * @param  array       $params input parameters
 *                          - triggers: bool, whether to drop/create SQL triggers; default: FALSE
 *                          - session:  bool, whether to reset the CiviCRM session data; defaul: FALSE
 *
 * @return boolean        true if success, else false
 * @static void
 * @access public
 * @example SystemFlush.php
 *
 */
function civicrm_api3_system_flush($params) {
  CRM_Core_Invoke::rebuildMenuAndCaches(
    CRM_Utils_Array::value('triggers', $params, FALSE),
    CRM_Utils_Array::value('session', $params, FALSE)
  );
  return civicrm_api3_create_success();
}

/**
 * Adjust Metadata for Flush action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_system_flush_spec(&$params){
  $params['triggers'] = array('title' => 'rebuild triggers (boolean)');
  $params['session'] = array('title' => 'refresh sessions (boolean)');

}