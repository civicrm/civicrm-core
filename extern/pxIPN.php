<?php

/*
 * PxPay Functionality Copyright (C) 2008 Lucas Baker,
 *   Logistic Information Systems Limited (Logis)
 * PxAccess Functionality Copyright (C) 2008 Eileen McNaughton
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 *
 * Grateful acknowledgements go to Donald Lobo for invaluable assistance
 * in creating this payment processor module
 */


session_start();

require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';

$config = CRM_Core_Config::singleton();
$log = new CRM_Utils_SystemLogger();
$log->alert('payment_notification processor_name=Payment_Express', $_REQUEST);
/*
 * Get the password from the Payment Processor's table based on the DPS user id
 * being passed back from the server
 */

$query = "
SELECT    url_site, password, user_name, signature
FROM      civicrm_payment_processor
LEFT JOIN civicrm_payment_processor_type ON civicrm_payment_processor_type.id = civicrm_payment_processor.payment_processor_type_id
WHERE     civicrm_payment_processor_type.name = 'Payment_Express'
AND       user_name = %1
";
$params = array(1 => array($_GET['userid'], 'String'));

$dpsSettings = CRM_Core_DAO::executeQuery($query, $params);
while ($dpsSettings->fetch()) {
  $dpsUrl    = $dpsSettings->url_site;
  $dpsUser   = $dpsSettings->user_name;
  $dpsKey    = $dpsSettings->password;
  $dpsMacKey = $dpsSettings->signature;
}

if ($dpsMacKey) {
  $method = "pxaccess";
}
else {
  $method = "pxpay";
}

require_once 'CRM/Core/Payment/PaymentExpressIPN.php';
$rawPostData = $_GET['result'];
CRM_Core_Payment_PaymentExpressIPN::main($method, $rawPostData, $dpsUrl, $dpsUser, $dpsKey, $dpsMacKey);
