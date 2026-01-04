<?php

return [
  'name' => 'Contribution',
  'table' => 'civicrm_contribution',
  'class' => 'CRM_Contribute_DAO_Contribution',
  'getInfo' => fn() => [
    'title' => ts('Contribution'),
    'title_plural' => ts('Contributions'),
    'description' => ts('Financial records consisting of transactions, line-items, etc.'),
    'log' => TRUE,
    'add' => '1.3',
    'icon' => 'fa-credit-card',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/contribute/add?reset=1&action=add&context=standalone',
    'view' => 'civicrm/contact/view/contribution?reset=1&action=view&id=[id]',
    'update' => 'civicrm/contact/view/contribution?reset=1&action=update&id=[id]',
    'delete' => 'civicrm/contact/view/contribution?reset=1&action=delete&id=[id]',
  ],
  'getIndices' => fn() => [
    'UI_contrib_payment_instrument_id' => [
      'fields' => [
        'payment_instrument_id' => TRUE,
      ],
      'add' => '1.6',
    ],
    'index_total_amount_receive_date' => [
      'fields' => [
        'total_amount' => TRUE,
        'receive_date' => TRUE,
      ],
      'add' => '4.7',
    ],
    'index_source' => [
      'fields' => [
        'source' => TRUE,
      ],
      'add' => '4.7',
    ],
    'UI_contrib_trxn_id' => [
      'fields' => [
        'trxn_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '2.1',
    ],
    'UI_contrib_invoice_id' => [
      'fields' => [
        'invoice_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '2.1',
    ],
    'index_contribution_status' => [
      'fields' => [
        'contribution_status_id' => TRUE,
      ],
      'add' => '1.6',
    ],
    'received_date' => [
      'fields' => [
        'receive_date' => TRUE,
      ],
      'add' => '1.6',
    ],
    'check_number' => [
      'fields' => [
        'check_number' => TRUE,
      ],
      'add' => '2.2',
    ],
    'index_creditnote_id' => [
      'fields' => [
        'creditnote_id' => TRUE,
      ],
      'add' => '4.7',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Contribution ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Contribution ID'),
      'add' => '1.3',
      'unique_name' => 'contribution_id',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'contact_id' => [
      'title' => ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to Contact ID'),
      'add' => '1.3',
      'unique_name' => 'contribution_contact_id',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Contact'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'financial_type_id' => [
      'title' => ts('Financial Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('FK to Financial Type for (total_amount - non_deductible_amount).'),
      'add' => '4.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Financial Type'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_financial_type',
        'key_column' => 'id',
        'label_column' => 'label',
      ],
      'entity_reference' => [
        'entity' => 'FinancialType',
        'key' => 'id',
      ],
    ],
    'contribution_page_id' => [
      'title' => ts('Contribution Page ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('The Contribution Page which triggered this contribution'),
      'add' => '1.5',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Contribution Page'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_contribution_page',
        'key_column' => 'id',
        'name_column' => 'name',
        'label_column' => 'title',
      ],
      'entity_reference' => [
        'entity' => 'ContributionPage',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'payment_instrument_id' => [
      'title' => ts('Payment Method ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('FK to Payment Instrument'),
      'add' => '1.3',
      'unique_name' => 'payment_instrument_id',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Payment Method'),
      ],
      'pseudoconstant' => [
        'option_group_name' => 'payment_instrument',
        'condition_provider' => ['CRM_Contribute_BAO_Contribution', 'alterPaymentInstrument'],
      ],
    ],
    'receive_date' => [
      'title' => ts('Contribution Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'add' => '1.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'format_type' => 'activityDateTime',
      ],
    ],
    'non_deductible_amount' => [
      'title' => ts('Non-deductible Amount'),
      'sql_type' => 'decimal(20,2)',
      'input_type' => 'Text',
      'description' => ts('Portion of total amount which is NOT tax deductible. Equal to total_amount for non-deductible financial types.'),
      'add' => '1.3',
      'default' => '0',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'total_amount' => [
      'title' => ts('Total Amount'),
      'sql_type' => 'decimal(20,2)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Total amount of this contribution. Use market value for non-monetary gifts.'),
      'add' => '1.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Total Amount'),
      ],
    ],
    'fee_amount' => [
      'title' => ts('Fee Amount'),
      'sql_type' => 'decimal(20,2)',
      'input_type' => 'Text',
      'description' => ts('actual processor fee if known - may be 0.'),
      'add' => '1.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Fee Amount'),
      ],
    ],
    'net_amount' => [
      'title' => ts('Net Amount'),
      'sql_type' => 'decimal(20,2)',
      'input_type' => 'Text',
      'description' => ts('actual funds transfer amount. total less fees. if processor does not report actual fee during transaction, this is set to total_amount.'),
      'add' => '1.3',
      'readonly' => TRUE,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Net Amount'),
        'formula' => '[total_amount] - [fee_amount]',
      ],
    ],
    'trxn_id' => [
      'title' => ts('Transaction ID'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'readonly' => TRUE,
      'description' => ts('unique transaction id. may be processor id, bank id + trans id, or account number + check number... depending on payment_method'),
      'add' => '1.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'invoice_id' => [
      'title' => ts('Invoice Reference'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'readonly' => TRUE,
      'description' => ts('unique invoice id, system generated or passed in'),
      'add' => '1.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'invoice_number' => [
      'title' => ts('Invoice Number'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Human readable invoice number'),
      'add' => '4.7',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'currency' => [
      'title' => ts('Currency'),
      'sql_type' => 'varchar(3)',
      'input_type' => 'Select',
      'description' => ts('3 character string, value from config setting or input via user.'),
      'add' => '1.3',
      'default' => NULL,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Currency'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_currency',
        'key_column' => 'name',
        'label_column' => 'full_name',
        'name_column' => 'name',
        'abbr_column' => 'symbol',
        'description_column' => 'IFNULL(CONCAT(name, " (", symbol, ")"), name)',
      ],
    ],
    'cancel_date' => [
      'title' => ts('Cancelled / Refunded Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => ts('when was gift cancelled'),
      'add' => '1.3',
      'unique_name' => 'contribution_cancel_date',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'format_type' => 'activityDateTime',
      ],
    ],
    'cancel_reason' => [
      'title' => ts('Cancellation / Refund Reason'),
      'sql_type' => 'text',
      'input_type' => 'Text',
      'add' => '1.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'size' => '40',
      ],
    ],
    'receipt_date' => [
      'title' => ts('Receipt Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => ts('when (if) receipt was sent. populated automatically for online donations w/ automatic receipting'),
      'add' => '1.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'format_type' => 'activityDateTime',
        'label' => ts('Receipt Date'),
      ],
    ],
    'thankyou_date' => [
      'title' => ts('Thank-you Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => ts('when (if) was donor thanked'),
      'add' => '1.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'format_type' => 'activityDateTime',
      ],
    ],
    'source' => [
      'title' => ts('Contribution Source'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Origin of this Contribution.'),
      'add' => '1.3',
      'unique_name' => 'contribution_source',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'amount_level' => [
      'title' => ts('Amount Label'),
      'sql_type' => 'text',
      'input_type' => 'Text',
      'add' => '1.7',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'contribution_recur_id' => [
      'title' => ts('Recurring Contribution ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'readonly' => TRUE,
      'description' => ts('Conditional foreign key to civicrm_contribution_recur id. Each contribution made in connection with a recurring contribution carries a foreign key to the recurring contribution record. This assumes we can track these processor initiated events.'),
      'add' => '1.4',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'label' => ts('Recurring Contribution'),
      ],
      'entity_reference' => [
        'entity' => 'ContributionRecur',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'is_test' => [
      'title' => ts('Test Mode'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'default' => FALSE,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'is_pay_later' => [
      'title' => ts('Is Pay Later'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'add' => '2.1',
      'default' => FALSE,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'contribution_status_id' => [
      'title' => ts('Contribution Status ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'add' => '1.6',
      'default' => 1,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Contribution Status'),
      ],
      'pseudoconstant' => [
        'option_group_name' => 'contribution_status',
        'condition_provider' => ['CRM_Contribute_BAO_Contribution', 'alterStatus'],
      ],
    ],
    'address_id' => [
      'title' => ts('Address ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('Conditional foreign key to civicrm_address.id. We insert an address record for each contribution when we have associated billing name and address data.'),
      'add' => '2.2',
      'unique_name' => 'contribution_address_id',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'label' => ts('Address'),
      ],
      'entity_reference' => [
        'entity' => 'Address',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'check_number' => [
      'title' => ts('Check Number'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'add' => '2.2',
      'unique_name' => 'contribution_check_number',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'size' => '6',
      ],
    ],
    'campaign_id' => [
      'title' => ts('Campaign ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('The campaign for which this contribution has been triggered.'),
      'add' => '3.4',
      'unique_name' => 'contribution_campaign_id',
      'component' => 'CiviCampaign',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Campaign'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_campaign',
        'key_column' => 'id',
        'label_column' => 'title',
        'prefetch' => 'disabled',
      ],
      'entity_reference' => [
        'entity' => 'Campaign',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'creditnote_id' => [
      'title' => ts('Credit Note ID'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('unique credit note id, system generated or passed in'),
      'add' => '4.6',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'tax_amount' => [
      'title' => ts('Tax Amount'),
      'sql_type' => 'decimal(20,2)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Total tax amount of this contribution.'),
      'add' => '4.6',
      'default' => '0',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'revenue_recognition_date' => [
      'title' => ts('Revenue Recognition Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => ts('Stores the date when revenue should be recognized.'),
      'add' => '4.7',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'format_type' => 'activityDateTime',
        'label' => ts('Revenue Recognition Date'),
      ],
    ],
    'is_template' => [
      'title' => ts('Is a Template Contribution'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'readonly' => TRUE,
      'description' => ts('Shows this is a template for recurring contributions.'),
      'add' => '5.20',
      'default' => FALSE,
      'usage' => [
        'export',
        'duplicate_matching',
      ],
    ],
    'created_date' => [
      'title' => ts('Created Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'description' => ts('When was the contribution created.'),
      'add' => '6.9',
      'unique_name' => 'contribution_created_date',
      'default' => 'CURRENT_TIMESTAMP',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'label' => ts('Created Date'),
      ],
    ],
    'modified_date' => [
      'title' => ts('Modified Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'description' => ts('When was the contribution created or modified or deleted.'),
      'add' => '6.9',
      'unique_name' => 'contribution_modified_date',
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'label' => ts('Modified Date'),
      ],
    ],
  ],
];
