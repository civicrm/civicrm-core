<?php
return [
    // No DAO change?
  'civicrm_contribution' => [
    'is_test' => "DEFAULT 0",
    'is_pay_later' => "DEFAULT 0",
    'is_template' => "DEFAULT 0 COMMENT 'Shows this is a template for recurring contributions.'",
  ],
  'civicrm_contribution_page' => [
    'is_credit_card_only' => "DEFAULT 0 COMMENT 'if true - processing logic must reject transaction at confirmation stage if pay method != credit card'",
    'is_monetary' => "DEFAULT 1 COMMENT 'if true - allows real-time monetary transactions otherwise non-monetary transactions'",
    'is_recur' => "DEFAULT 0 COMMENT 'if true - allows recurring contributions, valid only for PayPal_Standard'",
    'is_confirm_enabled' => "DEFAULT 1 COMMENT 'if false, the confirm page in contribution pages gets skipped'",
    'is_recur_interval' => "DEFAULT 0 COMMENT 'if true - supports recurring intervals'",
    'is_recur_installments' => "DEFAULT 0 COMMENT 'if true - asks user for recurring installments'",
    'adjust_recur_start_date' => "DEFAULT 0 COMMENT 'if true - user is able to adjust payment start date'",
    'is_pay_later' => "DEFAULT 0 COMMENT 'if true - allows the user to send payment directly to the org later'",
    'is_allow_other_amount' => "DEFAULT 0 COMMENT 'if true, page will include an input text field where user can enter their own amount'",
    'is_email_receipt' => "DEFAULT 0 COMMENT 'if true, receipt is automatically emailed to contact on success'",
    'is_active' => "DEFAULT 1 COMMENT 'Is this property active?'",
    'amount_block_is_active' => "DEFAULT 1 COMMENT 'Is this property active?'",
    'is_share' => "DEFAULT 1 COMMENT 'Can people share the contribution page through social media?'",
    'is_billing_required' => "DEFAULT 0 COMMENT 'if true - billing block is required for online contribution page'",
  ],
  'civicrm_contribution_widget' => [
    'is_active' => "DEFAULT 1 COMMENT 'Is this property active?'",
  ],
  'civicrm_contribution_recur' => [
    'is_test' => "DEFAULT 0",
    'is_email_receipt' => "DEFAULT 1 COMMENT 'if true, receipt is automatically emailed to contact on each successful payment'",
  ],
  'civicrm_contribution_soft' => [
    'pcp_display_in_roll' => "DEFAULT 0",
  ],
  'civicrm_premiums' => [
    'premiums_display_min_contribution' => 'DEFAULT 0',
  ],
];
