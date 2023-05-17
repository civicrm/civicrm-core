<?php
return CRM_Core_CodeGen_OptionGroup::create('payment_instrument', 'a/0010')
  ->addMetadata([
    'title' => ts('Payment Methods'),
    'description' => ts('You may choose to record the payment method used for each contribution and fee. Reserved payment methods are required - you may modify their labels but they can not be deleted (e.g. Check, Credit Card, Debit Card). If your site requires additional payment methods, you can add them here. You can associate each payment method with a Financial Account which specifies where the payment is going (e.g. a bank account for checks and cash).'),
    'data_type' => 'Integer',
  ])
  ->addValues(['label', 'name', 'value'], [
    [ts('Credit Card'), 'Credit Card', 1, 'is_reserved' => 1],
    [ts('Debit Card'), 'Debit Card', 2, 'is_reserved' => 1],
    [ts('Cash'), 'Cash', 3],
    [ts('Check'), 'Check', 4, 'is_default' => 1, 'is_reserved' => 1],
    [ts('EFT'), 'EFT', 5],
  ]);
