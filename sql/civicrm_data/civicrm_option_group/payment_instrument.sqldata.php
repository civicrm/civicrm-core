<?php
return CRM_Core_CodeGen_OptionGroup::create('payment_instrument', 'a/0010')
  ->addMetadata([
    'title' => ts('Payment Methods'),
    'description' => ts('You may choose to record the payment method used for each contribution and fee. Reserved payment methods are required - you may modify their labels but they can not be deleted (e.g. Check, Credit Card, Debit Card). If your site requires additional payment methods, you can add them here. You can associate each payment method with a Financial Account which specifies where the payment is going (e.g. a bank account for checks and cash).'),
    'data_type' => 'Integer',
  ])
  ->addValues([
    [
      'label' => ts('Credit Card'),
      'value' => 1,
      'name' => 'Credit Card',
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Debit Card'),
      'value' => 2,
      'name' => 'Debit Card',
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Cash'),
      'value' => 3,
      'name' => 'Cash',
    ],
    [
      'label' => ts('Check'),
      'value' => 4,
      'name' => 'Check',
      'is_default' => 1,
      'is_reserved' => 1,
    ],
    [
      'label' => ts('EFT'),
      'value' => 5,
      'name' => 'EFT',
    ],
  ]);
