<?php
return CRM_Core_CodeGen_OptionGroup::create('accept_creditcard', 'a/0009')
  ->addMetadata([
    'title' => ts('Accepted Credit Cards'),
    'description' => implode(
      ' ',
      [
        ts('The following credit card options will be offered to contributors using Online Contribution pages. You will need to verify which cards are accepted by your chosen Payment Processor and update these entries accordingly.'),
        ts('IMPORTANT: These options do not control credit card/payment method choices for sites and/or contributors using the PayPal Express service (e.g. where billing information is collected on the Payment Processor\\\'s website).'),
      ]
    ),
  ])
  ->addValueTable(['name', 'value'], [
    ['Visa', 1],
    ['MasterCard', 2],
    ['Amex', 3],
    ['Discover', 4],
  ])
  ->syncColumns('fill', ['name' => 'label']);
