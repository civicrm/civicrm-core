<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 * $Id$
 *
 */

/**
 * Grab the button type from a passed button element 'name' by checking for reserved QF button type strings
 *
 * @param string $btnName
 *
 * @return string
 *   button type, one of: 'upload', 'next', 'back', 'cancel', 'refresh'
 *                                      'submit', 'done', 'display', 'jump' 'process'
 */
function smarty_modifier_crmBtnType($btnName) {
  // split the string into 5 or more
  // button name are typically: '_qf_Contact_refresh' OR '_qf_Contact_refresh_dedupe'
  // button type is always the 3rd element
  // note the first _
  $substr = CRM_Utils_System::explode('_', $btnName, 5);

  return $substr[3];
}
