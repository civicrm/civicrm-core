<?php
/**
 * This record will be automatically inserted, updated, or deleted from the
 * database as appropriate. For more details, see "hook_civicrm_managed" at:
 * http://wiki.civicrm.org/confluence/display/CRMDOC/Hook+Reference
 */

return array(
  0 =>
    array(
    'module' => 'com.iatspayments.civicrm',
    'name' => 'iATS Payments',
    'entity' => 'PaymentProcessorType',
    'params' => array(
      'version' => 3,
      'name' => 'iATS Payments Credit Card',
      'title' => 'iATS Payments Credit Card',
      'description' => 'iATS credit card payment processor using the web services interface.',
      'class_name' => 'Payment_iATSService',
      'billing_mode' => 'form',
      'user_name_label' => 'Agent Code',
      'password_label' => 'Password',
      'url_site_default' => 'https://www.iatspayments.com/NetGate/ProcessLinkv2.asmx?WSDL',
      'url_recur_default' => 'https://www.iatspayments.com/NetGate/ProcessLinkv2.asmx?WSDL',
      'url_site_test_default' => 'https://www.iatspayments.com/NetGate/ProcessLinkv2.asmx?WSDL',
      'url_recur_test_default' => 'https://www.iatspayments.com/NetGate/ProcessLinkv2.asmx?WSDL',
      'is_recur' => 1,
      'payment_type' => 1,
    ),
  ),
  1 =>
    array(
    'module' => 'com.iatspayments.civicrm',
    'name' => 'iATS Payments ACH/EFT',
    'entity' => 'PaymentProcessorType',
    'params' => array(
      'version' => 3,
      'name' => 'iATS Payments ACH/EFT',
      'title' => 'iATS Payments ACH/EFT',
      'description' => 'iATS ACH/EFT payment processor using the web services interface.',
      'class_name' => 'Payment_iATSServiceACHEFT',
      'billing_mode' => 'form',
      'user_name_label' => 'Agent Code',
      'password_label' => 'Password',
      'url_site_default' => 'https://www.iatspayments.com/NetGate/ProcessLinkv2.asmx?WSDL',
      'url_recur_default' => 'https://www.iatspayments.com/NetGate/ProcessLinkv2.asmx?WSDL',
      'url_site_test_default' => 'https://www.iatspayments.com/NetGate/ProcessLinkv2.asmx?WSDL',
      'url_recur_test_default' => 'https://www.iatspayments.com/NetGate/ProcessLinkv2.asmx?WSDL',
      'is_recur' => 1,
      'payment_type' => 2,
      'payment_instrument_id' => '2', /* "Debit Card"  */
    ),
  ),
  2 =>
    array(
    'module' => 'com.iatspayments.civicrm',
    'name' => 'iATS Payments SWIPE',
    'entity' => 'PaymentProcessorType',
    'params' => array(
      'version' => 3,
      'name' => 'iATS Payments SWIPE',
      'title' => 'iATS Payments SWIPE',
      'description' => 'iATS credit card payment processor using the encrypted USB IDTECH card reader.',
      'class_name' => 'Payment_iATSServiceSWIPE',
      'billing_mode' => 'form',
      'user_name_label' => 'Agent Code',
      'password_label' => 'Password',
      'url_site_default' => 'https://www.iatspayments.com/NetGate/ProcessLinkv2.asmx?WSDL',
      'url_recur_default' => 'https://www.iatspayments.com/NetGate/ProcessLinkv2.asmx?WSDL',
      'url_site_test_default' => 'https://www.iatspayments.com/NetGate/ProcessLinkv2.asmx?WSDL',
      'url_recur_test_default' => 'https://www.iatspayments.com/NetGate/ProcessLinkv2.asmx?WSDL',
      'is_recur' => 1,
      'payment_type' => 1,
    ),
  ),
);
