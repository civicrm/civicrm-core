<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
use CRM_Payflowpro_ExtensionUtil as E;

return [
  0 => [
    'name' => 'PayflowPro',
    'entity' => 'PaymentProcessorType',
    'params' => [
      'version' => 3,
      'name' => 'PayflowPro',
      'title' => E::ts('PayflowPro'),
      'description' => '',
      'user_name_label' => 'Vendor ID',
      'password_label' => 'Password',
      'signature_label' => 'Partner (merchant)',
      'subject_label' => 'User',
      'class_name' => 'Payment_PayflowPro',
      'billing_mode' => 1,
      'url_site_default' => 'https://Payflowpro.paypal.com',
      'payment_type' => 1,
      'is_recur' => 1,
      'url_site_test_default' => 'https://pilot-Payflowpro.paypal.com',
    ],
  ],
];
