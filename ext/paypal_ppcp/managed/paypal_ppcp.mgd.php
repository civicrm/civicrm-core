<?php
return [
  [
    'name' => 'PPCP',
    'entity' => 'PaymentProcessorType',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'PPCP',
        'title' => 'PayPal - Complete Payments',
        'description' => 'PayPal - Complete Payments',
        'is_active' => TRUE,
        'is_default' => FALSE,
        'user_name_label' => 'Username',
        'password_label' => 'Bearer Token',
        'signature_label' => NULL,
        'subject_label' => NULL,
        'class_name' => 'Payment_PPCP',
        'url_site_default' => NULL,
        'url_api_default' => NULL,
        'url_recur_default' => NULL,
        'url_button_default' => NULL,
        'url_site_test_default' => NULL,
        'url_api_test_default' => NULL,
        'url_recur_test_default' => NULL,
        'url_button_test_default' => NULL,
        'billing_mode' => 1,
        'is_recur' => TRUE,
        'payment_type' => 1,
        'payment_instrument_id:name' => 'Credit Card',
      ],
      'match' => [
        'name',
      ],
    ],
  ],
];
