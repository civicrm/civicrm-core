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
 */
require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';

$config = CRM_Core_Config::singleton();
$template = CRM_Core_Smarty::singleton();

require_once 'CRM/Utils/Request.php';
$cpageId  = CRM_Utils_Request::retrieve('cpageId', 'Positive', CRM_Core_DAO::$_nullObject);
$widgetId = CRM_Utils_Request::retrieve('widgetId', 'Positive', CRM_Core_DAO::$_nullObject);
$format   = CRM_Utils_Request::retrieve('format', 'Positive', CRM_Core_DAO::$_nullObject);

require_once 'CRM/Contribute/BAO/Widget.php';

$jsonvar = 'jsondata';
if (isset($format)) {
  $jsonvar .= $cpageId;
}

$data = CRM_Contribute_BAO_Widget::getContributionPageData($cpageId, $widgetId);

$output = '
    var ' . $jsonvar . ' = ' . json_encode($data) . ';
';

echo $output;
CRM_Utils_System::civiExit();
