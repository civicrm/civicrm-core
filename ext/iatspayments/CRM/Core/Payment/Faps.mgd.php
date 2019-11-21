<?php
/**
 * This record will be automatically inserted, updated, or deleted from the
 * database as appropriate. For more details, see "hook_civicrm_managed" at:
 * http://wiki.civicrm.org/confluence/display/CRMDOC/Hook+Reference
 */

return array(
   0 =>
    array(
      'name' => 'iATS Payments 1stPay Processor',
      'entity' => 'payment_processor_type',
      'params' =>
        array(
          'version' => 3,
          'title' => 'iATS Payments 1stPay Credit Card',
          'name' => 'iATS Payments 1stPay Credit Card',
          'description' => 'iATS Payments Credit Card Processor using 1stPay',
          'user_name_label' => 'Processor ID',
          'password_label' => 'Transaction Center ID',
          'signature_label' => 'Merchant Key',
          'class_name' => 'Payment_Faps',
          'url_site_default' => 'https://secure.1stpaygateway.net/secure/RestGW/Gateway/Transaction/',
          'url_site_test_default' => 'https://secure-v.goemerchant.com/secure/RestGW/Gateway/Transaction/',
//          'url_recur_default' => 'https://secure.1stpaygateway.net/secure/RestGW/Gateway/Transaction/Sale'
//          'url_recur_test_default' => 'https://secure-v.goemerchant.com/secure/RestGW/Gateway/Transaction/',
          'billing_mode' => 1,
          'payment_type' => 1,
          'is_recur' => 1,
          'payment_instrument_id' => 1,
          'is_active' => 1,
        ),
    ),
   1 =>
    array(
      'name' => 'iATS Payments 1stPay ACH Processor',
      'entity' => 'payment_processor_type',
      'params' =>
        array(
          'version' => 3,
          'title' => 'iATS Payments 1stPay ACH',
          'name' => 'iATS Payments 1stPay ACH',
          'description' => 'iATS Payments ACH Processor using 1stPay',
          'user_name_label' => 'Processor ID',
          'password_label' => 'Transaction Center ID',
          'signature_label' => 'Merchant Key',
          'class_name' => 'Payment_FapsACH',
          'url_site_default' => 'https://secure.1stpaygateway.net/secure/RestGW/Gateway/Transaction/',
          'url_site_test_default' => 'https://secure-v.goemerchant.com/secure/RestGW/Gateway/Transaction/',
//          'url_recur_default' => 'https://secure.1stpaygateway.net/secure/RestGW/Gateway/Transaction/Sale'
//          'url_recur_test_default' => 'https://secure-v.goemerchant.com/secure/RestGW/Gateway/Transaction/',
          'billing_mode' => 1,
          'payment_type' => 2,
          'is_recur' => 1,
          'payment_instrument_id' => 2,
          'is_active' => 1,
        ),
    )
);
 
