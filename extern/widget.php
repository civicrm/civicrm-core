<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2017
 * $Id$
 */
require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';

$config = CRM_Core_Config::singleton();
$template = CRM_Core_Smarty::singleton();

require_once 'CRM/Utils/Request.php';
$cpageId = CRM_Utils_Request::retrieve('cpageId', 'Positive');
$widgetId = CRM_Utils_Request::retrieve('widgetId', 'Positive');
$format = CRM_Utils_Request::retrieve('format', 'Positive');
$includePending = CRM_Utils_Request::retrieve('includePending', 'Boolean');

require_once 'CRM/Contribute/BAO/Widget.php';

$jsonvar = 'jsondata';
if (isset($format)) {
  $jsonvar .= $cpageId;
}

$data = CRM_Contribute_BAO_Widget::getContributionPageData($cpageId, $widgetId, $includePending);

$output = '
    var ' . $jsonvar . ' = ' . json_encode($data) . ';
';

// FIXME: Not using CRM_Core_Page_AJAX::setJsHeaders because CMS is not bootstrapped
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 60));
header('Content-Type: application/javascript');
header("Cache-Control: max-age=60, public");

echo $output;
CRM_Utils_System::civiExit();
