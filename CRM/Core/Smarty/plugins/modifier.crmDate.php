<?php
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Convert the date string "YYYY-MM-DD" to "MM<long> DD, YYYY".
 *
 * @param string $dateString date which needs to converted to human readable format
 *
 * @return string human readable date format | invalid date message
 * @access public
 */
function smarty_modifier_crmDate($dateString, $dateFormat = NULL, $onlyTime = FALSE) {
  if ($dateString) {
    // this check needs to be type sensitive
    // CRM-3689, CRM-2441
    if ($dateFormat === 0) {
      $dateFormat = NULL;
    }
    if ($onlyTime) {
      $config = CRM_Core_Config::singleton();
      $dateFormat = $config->dateformatTime;
    }

    return CRM_Utils_Date::customFormat($dateString, $dateFormat);
  }
  return '';
}

