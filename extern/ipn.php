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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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

CRM_Core_Config::singleton();
try {
  switch ($config->userFramework) {
    case 'Joomla':
      // CRM-18245
      CRM_Utils_System::loadBootStrap();
      break;

    default:
      // Gitlab issues: #973, #1017
      CRM_Utils_System::loadBootStrap([], FALSE);
      break;

  }
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
  $paypalIPN->main();
}
catch (CRM_Core_Exception $e) {
  CRM_Core_Error::debug_log_message($e->getMessage());
  CRM_Core_Error::debug_var('error data', $e->getErrorData(), TRUE, TRUE);
  CRM_Core_Error::debug_var('REQUEST', $_REQUEST, TRUE, TRUE);
  //@todo give better info to logged in user - ie dev
  echo "The transaction has failed. Please review the log for more detail";
}
