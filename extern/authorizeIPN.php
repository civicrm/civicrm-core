<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 */

if (defined('PANTHEON_ENVIRONMENT')) {
  ini_set('session.save_handler', 'files');
}
session_start();

require_once '../civicrm.config.php';
$config = CRM_Core_Config::singleton();
$log = new CRM_Utils_SystemLogger();
$log->alert('payment_notification processor_name=AuthNet', $_REQUEST);

$authorizeNetIPN = new CRM_Core_Payment_AuthorizeNetIPN($_REQUEST);
try {
  $authorizeNetIPN->main();
}
catch (CRM_Core_Exception $e) {
  CRM_Core_Error::debug_log_message($e->getMessage());
  CRM_Core_Error::debug_var('error data', $e->getErrorData(), TRUE, TRUE);
  CRM_Core_Error::debug_var('REQUEST', $_REQUEST, TRUE, TRUE);
  echo "The transaction has failed. Please review the log for more detail";
}
