<?php
use CRM_Elavon_ExtensionUtil as E;

// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return [
  0 => [
    'name' => 'PaymentProcessorType_Elavon',
    'entity' => 'PaymentProcessorType',
    'params' => [
      'version' => 3,
      'name' => 'Elavon',
      'title' => E::ts('Elavon Payment Processor'),
      'description' => E::ts('Elavon / Nova Virtual Merchant'),
      'user_name_label' => E::ts('SSL Merchant ID'),
      'password_label' => E::ts('SSL User ID'),
      'signature_label' => E::ts('SSL PIN'),
      'class_name' => 'Payment_Elavon',
      'billing_mode' => 1,
      'url_site_default' => 'https://www.myvirtualmerchant.com/VirtualMerchant/processxml.do',
      'payment_type' => 1,
      'is_recur' => 0,
      'url_site_test_default' => 'https://www.myvirtualmerchant.com/VirtualMerchant/processxml.do',
    ],
  ],
];
