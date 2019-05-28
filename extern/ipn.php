<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 * This script processes "Instant Payment Notifications" (IPNs).  Modern
 * Payment Processors use the /civicrm/payment/ipn/123 endpoint instead (where
 * 123 is the payment processor ID), however a quirk in the way PayPal works
 * means that we need to maintain this script.
 *
 * Note on PayPal.
 *
 * Using PayPal Website Standard (which uses the old PayPal button API) the IPN
 * endpoint is passed to PayPal with every transaction, and it is then stored
 * by PayPal who unhelpfully do not give you any way to retrieve or change
 * this.
 *
 * This means that if you provide URL1 when setting up a recurring
 * contribution, then you will always need to maintain URL1 because all
 * recurring payments against that will be sent to URL1.
 *
 * Note that this also affects you if you were to move your CiviCRM instance to
 * another domain (if you do, get the webserver at the original domain to emit
 * a 307 redirect to the new one, PayPal will re-send).
 *
 * Therefore, for the sake of these old recurring contributions, CiviCRM should
 * maintain this script as part of core.
 */

if (defined('PANTHEON_ENVIRONMENT')) {
  ini_set('session.save_handler', 'files');
}
session_start();

require_once '../civicrm.config.php';

/* Cache the real UF, override it with the SOAP environment */

$config = CRM_Core_Config::singleton();
$log = new CRM_Utils_SystemLogger();
if (empty($_GET)) {
  $log->alert('payment_notification processor_name=PayPal', $_REQUEST);
  $paypalIPN = new CRM_Core_Payment_PayPalProIPN($_REQUEST);
}
else {
  $log->alert('payment_notification PayPal_Standard', $_REQUEST);
  $paypalIPN = new CRM_Core_Payment_PayPalIPN($_REQUEST);
  // @todo upgrade standard per Pro
}
try {
  switch ($config->userFramework) {
    case 'Joomla':
      // CRM-18245
      CRM_Utils_System::loadBootStrap();
      break;

    case 'Drupal':
    case 'Backdrop':
      // Gitlab issue: #973
      CRM_Utils_System::loadBootStrap([], FALSE);
      break;

  }
  $paypalIPN->main();
}
catch (CRM_Core_Exception $e) {
  CRM_Core_Error::debug_log_message($e->getMessage());
  CRM_Core_Error::debug_var('error data', $e->getErrorData(), TRUE, TRUE);
  CRM_Core_Error::debug_var('REQUEST', $_REQUEST, TRUE, TRUE);
  //@todo give better info to logged in user - ie dev
  echo "The transaction has failed. Please review the log for more detail";
}
