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
 * @copyright TTTP
 * $Id$
 *
 */

/**
 * Display the CiviCRM version
 *
 * @code
 * The version is {crmVersion}.
 *
 * {crmVersion redact=auto assign=ver}The version is {$ver}.
 * @endcode
 *
 * @param $params
 * @param $smarty
 *
 * @return string
 */
function smarty_function_crmVersion($params, &$smarty) {
  $version = CRM_Utils_System::version();

  if (!CRM_Core_Permission::check('access CiviCRM')) {
    $version = CRM_Utils_System::majorVersion();
  }

  if (isset($params['assign'])) {
    $smarty->assign($params['assign'], $version);
  }
  else {
    return $version;
  }
}
