<?php
session_start();

require_once '../civicrm.config.php';
$config = CRM_Core_Config::singleton();

$params = $_REQUEST;
if(!empty($params['id'])) {
  $query = 'SELECT * FROM civicrm_notification_log WHERE id = %1';
  $ipn = CRM_Core_DAO::executeQuery($query, array(
    1 => array($params['id'], 'Integer'),
  ));
  while ($ipn->fetch()) {
    switch($ipn->message_type) {
      case 'paypal':
      case 'paypalpro':
        $payPal = new CRM_Core_Payment_PayPalProIPN(json_decode($ipn->message_raw, TRUE));
        $payPal->main();
        break;
    case 'authorize.net':
        $_REQUEST = $_POST = json_decode($ipn->message_raw, TRUE);
        $authorizeNetIPN = new CRM_Core_Payment_AuthorizeNetIPN();
        $authorizeNetIPN->main();
      break;
    }
  }
}