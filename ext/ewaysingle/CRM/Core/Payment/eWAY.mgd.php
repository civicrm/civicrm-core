<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return [
  0 => [
    'name' => 'eWAY',
    'entity' => 'PaymentProcessorType',
    'params' => [
      'version' => 3,
      'name' => 'eWAY',
      'title' => 'eWAY (Single Currency)',
      'description' => '',
      'user_name_label' => 'Customer ID',
      'class_name' => 'Payment_eWAY',
      'billing_mode' => 1,
      'url_site_default' => 'https://www.eway.com.au/gateway_cvn/xmlpayment.asp',
      'payment_type' => 1,
      'is_recur' => 0,
      'url_site_test_default' => 'https://www.eway.com.au/gateway_cvn/xmltest/testpage.asp',
    ],
  ],
];
