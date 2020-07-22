<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
