<?php
return [
  'civicrm_financial_account' => [
    'is_header_account' => "DEFAULT 0 COMMENT 'Is this a header account which does not allow transactions to be posted against it directly, but only to its sub-accounts?'",
    'is_deductible' => "DEFAULT 0 COMMENT 'Is this account tax-deductible?'",
    'is_tax' => "DEFAULT 0 COMMENT 'Is this account for taxes?'",
    'is_reserved' => "DEFAULT 0 COMMENT 'Is this a predefined system object?'",
    'is_active' => "DEFAULT 1 COMMENT 'Is this property active?'",
    'is_default' => "DEFAULT 0 COMMENT 'Is this account the default one (or default tax one) for its financial_account_type?'",
  ],
  'civicrm_financial_trxn' => [
    'is_payment' => "DEFAULT 0 COMMENT 'Is this entry either a payment or a reversal of a payment?'",
  ],
  'civicrm_payment_processor' => [
    'is_active' => "DEFAULT 1 COMMENT 'Is this processor active?'",
    'is_default' => "DEFAULT 0 COMMENT 'Is this processor the default?'",
    'is_test' => "DEFAULT 0 COMMENT 'Is this processor for a test site?'",
    'is_recur' => "DEFAULT 0 COMMENT 'Can process recurring contributions'",
  ],
];
