<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 5                                                  |
  +--------------------------------------------------------------------+
  | This file is a part of CiviCRM.                                    |
  |                                                                    |
  | CiviCRM is free software; you can copy, modify, and distribute it  |
  | under the terms of the GNU Affero General Public License           |
  | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
  |                                                                    |
  | CiviCRM is distributed in the hope that it will be useful, but     |
  | WITHOUT ANY WARRANTY; without even the implied warranty of         |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
  | See the GNU Affero General Public License for more details.        |
  |                                                                    |
  | You should have received a copy of the GNU Affero General Public   |
  | License and the CiviCRM Licensing Exception along                  |
  | with this program; if not, contact CiviCRM LLC                     |
  | at info[AT]civicrm[DOT]org. If you have questions about the        |
  | GNU Affero General Public License or the licensing of CiviCRM,     |
  | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
  +--------------------------------------------------------------------+
 */

/**
 * Build the $data array for CiviContribute related tables
 * Includes the following tables
 * civicrm_contribute
 * civicrm_contribution_soft
 * civicrm_contribution_recur
 * civicrm_pcp
 */
function _civicrm_contribute_data(&$data, $enabled) {
  //----------------------------------------------------------------
  // CIVICRM Contributions are here with all the bling, base tabling it up.
  //----------------------------------------------------------------

  $data['civicrm_contribution']['table']['group'] = t('CiviCRM Contributions');

  $data['civicrm_contribution']['table']['base'] = array(
    // Governs the whole mozilla
    'field' => 'id',
    'title' => t('CiviCRM Contributions'),
    'help' => t("View displays CiviCRM Contributions, with relation to contacts."),
  );

  // CiviCRM recurring contributions - make this available as a base table too
  $data['civicrm_contribution_recur']['table']['base'] = array(
    'field' => 'id',
    'title' => t('CiviCRM Recurring Contribution profile'),
    'help' => t("Recurring Contribution Profiles in CiviCRM."),
  );

  // this join makes Contribution data available in view based on recurring .
  // A separate relationship is defined elsewhere to make recurring data available to view based on contributi
  $data['civicrm_contribution']['table']['join']['civicrm_contribution_recur'] = array(
    'left_field' => 'id',
    'field' => 'contribution_recur_id',
  );

  //TABLE JOINS FOR CIVICRM CONTRIBUTIONS GO HERE!

  $data['civicrm_contribution']['table']['join']['civicrm_contact'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  $data['civicrm_contribution']['table']['join']['civicrm_participant'] = array(
    'left_table' => 'civicrm_participant_payment',
    'left_field' => 'contribution_id',
    'field' => 'id',
  );

  $data['civicrm_contribution']['table']['join']['civicrm_pledge'] = array(
    'left_table' => 'civicrm_pledge_payment',
    'left_field' => 'contribution_id',
    'field' => 'id',
  );
  //CiviCRM Contributions - FIELDS

  //Numeric Contribution ID
  $data['civicrm_contribution']['id'] = array(
    'title' => t('Contribution ID'),
    'help' => t('The numeric ID of the Contribution'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //Contact ID for relationship to contact record (brings in Contact custom fields)
  $data['civicrm_contribution']['contact_id'] = array(
    'title' => t('Contact Record ID'),
    'help' => 'Bring Contact Record the complete contact record to the party',
    'relationship' => array(
      'base' => 'civicrm_contact',
      'left_field' => 'id',
      'field' => 'contact_id',
      'handler' => 'views_handler_relationship',
      'label' => t('CiviCRM Contact, with custom fields'),
    ),
  );
  //Financial Type
  $data['civicrm_contribution']['financial_type'] = array(
    'title' => t('Financial Type'),
    'real field' => 'financial_type_id',
    'help' => t('The Type of Contribution made, ie Member Dues, Event Fees etc'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Contribute_PseudoConstant',
      'pseudo method' => 'financialType',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Contribute_PseudoConstant',
      'pseudo method' => 'financialType',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Contribution Page
  $data['civicrm_contribution']['contribution_page'] = array(
    'title' => t('Contribution Page'),
    'real field' => 'contribution_page_id',
    'help' => t('The Page this Contribution came from'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Contribute_PseudoConstant',
      'pseudo method' => 'contributionPage',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Contribute_PseudoConstant',
      'pseudo method' => 'contributionPage',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Payment Instrument Used
  $data['civicrm_contribution']['payment_instrument'] = array(
    'title' => t('Payment Method'),
    'real field' => 'payment_instrument_id',
    'help' => t('The Payment Instrument Used for the Contribution'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Contribute_PseudoConstant',
      'pseudo method' => 'paymentInstrument',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Contribute_PseudoConstant',
      'pseudo method' => 'paymentInstrument',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Contribution Receive Date
  $data['civicrm_contribution']['receive_date'] = array(
    'title' => t('Date Received'),
    'help' => t('The Date the Contribution was received / paid'),
    'field' => array(
      'handler' => 'civicrm_handler_field_datetime',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_datetime',
      'is date' => TRUE,
    ),
    'sort' => array(
      'handler' => 'civicrm_handler_sort_date',
    ),
  );

  civicrm_views_add_date_arguments($data['civicrm_contribution'], array(
    'title' => t('Date Received'),
      'name' => 'receive_date',
    ));
  //Non Deductible Amount of Contribution
  $data['civicrm_contribution']['non_deductible_amount'] = array(
    'title' => t('Non Deductible Amount'),
    'help' => t('The Non Deductible Part of the Contribution'),
    'field' => array(
      'handler' => 'civicrm_handler_field_money',
      'click sortable' => TRUE,
      'currency field' => 'currency',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['civicrm_contribution']['total_amount'] = array(
    'title' => t('Total Amount'),
    'help' => t('The Total Contribution made by the Contact'),
    'field' => array(
      'handler' => 'civicrm_handler_field_money',
      'click sortable' => TRUE,
      'currency field' => 'currency',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['civicrm_contribution']['fee_amount'] = array(
    'title' => t('Fee Amount'),
    'help' => t('The fee charged by the Payment Processor for the transaction.'),
    'field' => array(
      'handler' => 'civicrm_handler_field_money',
      'click sortable' => TRUE,
      'currency field' => 'currency',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['civicrm_contribution']['net_amount'] = array(
    'title' => t('Net Amount'),
    'help' => t('The Full Contribution minus Payment Processor Fees.'),
    'field' => array(
      'handler' => 'civicrm_handler_field_money',
      'click sortable' => TRUE,
      'currency field' => 'currency',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Transaction ID
  $data['civicrm_contribution']['trxn_id'] = array(
    'title' => t('Transaction ID'),
    'help' => t('Unique Transaction ID from Payment Processor / Bank etc'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Invoice ID
  $data['civicrm_contribution']['invoice_id'] = array(
    'title' => t('Invoice ID'),
    'help' => t('Unique Invoice ID from system'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Currency
  $data['civicrm_contribution']['currency'] = array(
    'title' => t('Currency'),
    'help' => t('Currency used for the Contribution'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Contribution Cancellation Date
  $data['civicrm_contribution']['cancel_date'] = array(
    'title' => t('Cancellation Date'),
    'help' => t('The Date the Contribution was cancelled (if it was)'),
    'field' => array(
      'handler' => 'civicrm_handler_field_datetime',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_datetime',
      'is date' => TRUE,
    ),
    'sort' => array(
      'handler' => 'civicrm_handler_sort_date',
    ),
  );
  civicrm_views_add_date_arguments($data['civicrm_contribution'], array(
    'title' => 'Cancel Date',
      'name' => 'cancel_date',
    ));

  //Cancellation Reason
  $data['civicrm_contribution']['cancel_reason'] = array(
    'title' => t('Cancellation Reason'),
    'help' => t('The Reason for the Contribution Cancellation'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Contribution Receipt Date
  $data['civicrm_contribution']['receipt_date'] = array(
    'title' => t('Receipt Date'),
    'help' => t('The Date a receipt for the contribution was sent.'),
    'field' => array(
      'handler' => 'civicrm_handler_field_datetime',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_datetime',
      'is date' => TRUE,
    ),
    'sort' => array(
      'handler' => 'civicrm_handler_sort_date',
    ),
  );
  civicrm_views_add_date_arguments($data['civicrm_contribution'], array(
    'title' => 'Receipt Date',
      'name' => 'receipt_date',
    ));

  //Contribution Thank You Date
  $data['civicrm_contribution']['thankyou_date'] = array(
    'title' => t('Thank You Date'),
    'help' => t('The Date a thank you to the donor for the contribution was sent.'),
    'field' => array(
      'handler' => 'civicrm_handler_field_datetime',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_datetime',
      'is date' => TRUE,
    ),
    'sort' => array(
      'handler' => 'civicrm_handler_sort_date',
    ),
  );
  civicrm_views_add_date_arguments($data['civicrm_contribution'], array(
    'title' => 'Thank You Date',
      'name' => 'thankyou_date',
    ));

  //Contribution Source
  $data['civicrm_contribution']['source'] = array(
    'title' => t('Source'),
    'help' => t('Where the Contribution came through.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Contribution Status
  $data['civicrm_contribution']['contribution_status'] = array(
    'title' => t('Contribution Status'),
    'real field' => 'contribution_status_id',
    'help' => t('The Status of this Contribution'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Contribute_PseudoConstant',
      'pseudo method' => 'contributionStatus',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Contribute_PseudoConstant',
      'pseudo method' => 'contributionStatus',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //TODO: CONTRIBUTION RECURRING STUFF + HONOR_CONTACT_ID + HONOR_TYPE_ID

  //BOOLEAN : IS A TEST CONTRIBUTION
  $data['civicrm_contribution']['is_test'] = array(
    'title' => t('Is Test'),
    'help' => t('Is the contribution a test entry?'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //BOOLEAN : IS SET TO PAY LATER
  $data['civicrm_contribution']['is_pay_later'] = array(
    'title' => t('Is Pay Later'),
    'help' => t('Opted to Pay Contribution Later, and is still pending payment.'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  $data['civicrm_contribution']['check_number'] = array(
    'title' => t('Check Number'),
    'help' => t("Contribution's Check Number"),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  if (isset($enabled['CiviCampaign'])) {
    $data['civicrm_contribution']['campaign_id'] = array(
      'title' => t('Campaign'),
      'help' => t('Contribution -> Campaign'),
      'relationship' => array(
        'base' => 'civicrm_campaign',
        'base field' => 'id',
        'handler' => 'views_handler_relationship',
        'label' => t('Contribution -> Campaign'),
      ),
    );
  }
  // Link to Membership table
  if (isset($enabled['CiviMember'])) {
    $data['civicrm_contribution']['membership_id'] = array(
      'title' => t('Memberships Records'),
      'help' => 'Bring memberships records',
      'relationship' => array(
        'base' => 'civicrm_membership',
        'base field' => 'id',
        'handler' => 'civicrm_handler_relationship_memberships_contributions',
        'label' => t('Contribution -> Membership'),
      ),
    );
  }
  //link to line-items
  $data['civicrm_contribution']['line_item_id'] = array(
    'title' => t('Line-items Records'),
    'help' => 'Bring line-items records',
    'relationship' => array(
      'base' => 'civicrm_line_item',
      'base field' => 'contribution_id',
      'field' => 'id',
      'handler' => 'views_handler_relationship',
      'label' => t('Contribution -> line-item'),
    ),
  );

  // Soft Credits
  $data['civicrm_contribution_soft']['table']['group'] = t('CiviCRM  Soft Credits');

  //TABLE JOINS FOR CIVICRM CONTRIBUTIONS GO HERE!
  $data['civicrm_contribution_soft']['table']['join']['civicrm_contribution'] = array(
    'left_table' => 'civicrm_contribution',
    'left_field' => 'id',
    'field' => 'contribution_id',
  );
  //CiviCRM Soft Credits - FIELDS

  //Numeric Soft Credit ID
  $data['civicrm_contribution_soft']['id'] = array(
    'title' => t('Contribution Soft Credit ID'),
    'help' => t('The numeric ID of the Soft Credit'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Numeric Contact ID of the Soft Credit recipient
  $data['civicrm_contribution_soft']['contact_id'] = array(
    'title' => t('Contact ID'),
    'help' => t('Contact ID of the Soft Credit recipient'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'base' => 'civicrm_contact',
      'left_field' => 'id',
      'field' => 'contact_id',
      'handler' => 'views_handler_relationship',
      'label' => t('Soft Contribution -> Contact Record'),
    ),
  );
  //Soft Credit Amount
  $data['civicrm_contribution_soft']['amount'] = array(
    'title' => t('Amount'),
    'help' => t('The amount of the soft credit'),
    'field' => array(
      'handler' => 'civicrm_handler_field_money',
      'click sortable' => TRUE,
      'currency field' => 'currency',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Personal Campaign Page
  $data['civicrm_contribution_soft']['pcp_roll_nickname'] = array(
    'title' => t('Personal Campaign Page Nickname'),
    'help' => t('The Page this Contribution came from'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //Personal Campaign Page Personla Note
  $data['civicrm_contribution_soft']['pcp_personal_note'] = array(
    'title' => t('Personal Campaign Page Personal Note'),
    'help' => t('The Personal note form PCP'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // Soft Credits
  $data['civicrm_contribution_recur']['table']['group'] = t('CiviCRM  Recurring Contributions');

  //TABLE JOINS FOR CIVICRM CONTRIBUTIONS GO HERE!
  $data['civicrm_contribution_recur']['table']['join']['civicrm_contribution'] = array(
    'left_table' => 'civicrm_contribution',
    'left_field' => 'contribution_recur_id',
    'field' => 'id',
  );

  //CiviCRM Soft Credits - FIELDS

  //Numeric Recurring Contribution ID
  $data['civicrm_contribution_recur']['id'] = array(
    'title' => t('Recurring Contribution ID'),
    'help' => t('The numeric ID of the  Recurring Contribution'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['civicrm_contribution_recur']['contact_id'] = array(
    'title' => t('Contact ID'),
    'help' => t('The contact who is paying the recurring pledge'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'relationship' => array(
      'base' => 'civicrm_contact',
      'left_field' => 'id',
      'field' => 'contact_id',
      'handler' => 'views_handler_relationship',
      'label' => t('CiviCRM Recurring payment Contact'),
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Recurring Contribution Amount
  $data['civicrm_contribution_recur']['amount'] = array(
    'title' => t('Amount'),
    'help' => t('The amount of the recurring contribution'),
    'field' => array(
      'handler' => 'civicrm_handler_field_money',
      'click sortable' => TRUE,
      'currency field' => 'currency',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //Frequency of recurring contribution
  $data['civicrm_contribution_recur']['frequency_unit'] = array(
    'title' => t('Frequency Unit'),
    'help' => t('Day, Week, Month, Year'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //Frequency Interval
  $data['civicrm_contribution_recur']['frequency_interval'] = array(
    'title' => t('Frequency Interval'),
    'help' => t('The Interval of the Frequency Unit'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //# of Installments
  $data['civicrm_contribution_recur']['installments'] = array(
    'title' => t('Installments'),
    'help' => t('Number of Installments'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //Recurring Contribution Start Date
  $data['civicrm_contribution_recur']['start_date'] = array(
    'title' => t('Start Date'),
    'help' => t('The Date the recurring contribution started.'),
    'field' => array(
      'handler' => 'civicrm_handler_field_datetime',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_datetime',
      'is date' => TRUE,
    ),
    'sort' => array(
      'handler' => 'civicrm_handler_sort_date',
    ),
  );
  civicrm_views_add_date_arguments($data['civicrm_contribution_recur'], array(
    'title' => 'Recurring Contribution Start Date',
      'name' => 'start_date',
    ));
  //Recurring Contribution Create Date
  $data['civicrm_contribution_recur']['create_date'] = array(
    'title' => t('Create Date'),
    'help' => t('The Date the recurring contribution was created.'),
    'field' => array(
      'handler' => 'civicrm_handler_field_datetime',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_datetime',
      'is date' => TRUE,
    ),
    'sort' => array(
      'handler' => 'civicrm_handler_sort_date',
    ),
  );
  civicrm_views_add_date_arguments($data['civicrm_contribution_recur'], array(
    'title' => 'Recurring Contribution Create Date',
      'name' => 'create_date',
    ));
  //Recurring Contribution Modified Date
  $data['civicrm_contribution_recur']['modified_date'] = array(
    'title' => t('Modified Date'),
    'help' => t('The Date the recurring contribution was modified.'),
    'field' => array(
      'handler' => 'civicrm_handler_field_datetime',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_datetime',
      'is date' => TRUE,
    ),
    'sort' => array(
      'handler' => 'civicrm_handler_sort_date',
    ),
  );
  civicrm_views_add_date_arguments($data['civicrm_contribution_recur'], array(
    'title' => 'Recurring Contribution Modified Date',
      'name' => 'modified_date',
    ));
  //Recurring Contribution Cancel Date
  $data['civicrm_contribution_recur']['cancel_date'] = array(
    'title' => t('Cancel Date'),
    'help' => t('The Date the recurring contribution was cancelled.'),
    'field' => array(
      'handler' => 'civicrm_handler_field_datetime',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_datetime',
      'is date' => TRUE,
    ),
    'sort' => array(
      'handler' => 'civicrm_handler_sort_date',
    ),
  );
  civicrm_views_add_date_arguments($data['civicrm_contribution_recur'], array(
    'title' => 'Recurring Contribution Cancel Date',
      'name' => 'cancel_date',
    ));
  //Recurring Contribution End Date
  $data['civicrm_contribution_recur']['end_date'] = array(
    'title' => t('End Date'),
    'help' => t('The Date the recurring contribution ended.'),
    'field' => array(
      'handler' => 'civicrm_handler_field_datetime',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_datetime',
      'is date' => TRUE,
    ),
    'sort' => array(
      'handler' => 'civicrm_handler_sort_date',
    ),
  );
  civicrm_views_add_date_arguments($data['civicrm_contribution_recur'], array(
    'title' => 'Recurring Contribution End Date',
      'name' => 'end_date',
    ));
  //Prcocessor ID
  $data['civicrm_contribution_recur']['processor_id'] = array(
    'title' => t('Processor ID'),
    'help' => t('The Processor ID of the Recurring Contribution'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //Unique Transaction ID
  $data['civicrm_contribution_recur']['trxn_id'] = array(
    'title' => t('Unique Transaction ID'),
    'help' => t('The Transaction ID of the  Recurring Contribution'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //Invoice ID
  $data['civicrm_contribution_recur']['invoice_id'] = array(
    'title' => t('Invoice ID'),
    'help' => t('The Unique CiviCRM Generated Invoice ID'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //recurring contributions Status
  $data['civicrm_contribution_recur']['contribution_status'] = array(
    'title' => t('Contribution Status'),
    'real field' => 'contribution_status_id',
    'help' => t("The Contribution's Status"),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Contribute_PseudoConstant',
      'pseudo method' => 'contributionStatus',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Contribute_PseudoConstant',
      'pseudo method' => 'contributionStatus',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //BOOLEAN : IS A TEST CONTRIBUTION
  $data['civicrm_contribution_recur']['is_test'] = array(
    'title' => t('Is Test'),
    'help' => t('Is the recurring contribution a test entry?'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //Cycle contibution Day
  $data['civicrm_contribution_recur']['cycle_day'] = array(
    'title' => t('Cycle Day'),
    'help' => t('What day in the contribution will the debit occur'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //Recurring Contribution End Date
  $data['civicrm_contribution_recur']['next_sched_contribution'] = array(
    'title' => t('Next Contribution Date'),
    'help' => t('The Next Contribution Date.'),
    'field' => array(
      'handler' => 'civicrm_handler_field_datetime',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_datetime',
      'is date' => TRUE,
    ),
    'sort' => array(
      'handler' => 'civicrm_handler_sort_date',
    ),
  );
  civicrm_views_add_date_arguments($data['civicrm_contribution_recur'], array(
    'title' => 'Recurring Contribution Next Date',
      'name' => 'next_sched_contribution',
    ));
  //How many times has the contribution failed
  $data['civicrm_contribution_recur']['failure_count'] = array(
    'title' => t('Failure Count'),
    'help' => t('How many times has theis recurring contribution failed?'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //when will the filed contribution be retried?
  $data['civicrm_contribution_recur']['failure_retry_date'] = array(
    'title' => t('Failed retry date'),
    'help' => t('When will the failed contribution be retried?'),
    'field' => array(
      'handler' => 'civicrm_handler_field_datetime',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_datetime',
      'is date' => TRUE,
    ),
    'sort' => array(
      'handler' => 'civicrm_handler_sort_date',
    ),
  );
  civicrm_views_add_date_arguments($data['civicrm_contribution_recur'], array(
    'title' => 'Recurring Contribution Failure Retry Date',
      'name' => 'failure_retry_date',
    ));
  //BOOLEAN : Does thie recurring contribution auto-renew?
  $data['civicrm_contribution_recur']['auto_renew'] = array(
    'title' => t('Auto-renew'),
    'help' => t('Will this recurring contribution automayically renew?'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  $data['civicrm_pledge']['table']['base'] = array(
    'field' => 'id',
    'title' => t('CiviCRM Pledges'),
    'help' => t("CiviCRM Pledges."),
  );
  $data['civicrm_pledge']['table']['group'] = t('CiviCRM Pledges');
  $data['civicrm_pledge_payment']['table']['group'] = t('Pledge Payments');
  $data['civicrm_pledge']['id'] = array(
    'title' => t('CiviCRM Pledge ID'),
    'help' => t('The numeric ID of the Pledge'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  $data['civicrm_pledge']['amount'] = array(
    'title' => t('Amount'),
    'help' => t('The total amount of the pledge'),
    'field' => array(
      'handler' => 'civicrm_handler_field_money',
      'click sortable' => TRUE,
      'currency field' => 'currency',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //------------------------------------------------------
  // CiviCRM Personal Campaign Pages tables
  //------------------------------------------------------

  $data['civicrm_pcp']['table']['group'] = t('CiviCRM Personal Campaign Pages');

  $data['civicrm_pcp']['table']['base'] = array(
    'field' => 'id',
    'title' => t('CiviCRM Personal Campaign Pages'),
    'help' => t("View displays CiviCRM Personal Campaign Pages, with connection to contacts."),
  );

  $data['civicrm_pcp']['table']['join'] = array(
    // Makes PCP fields available to Contact views.
    'civicrm_contact' => array(
      'left_field' => 'id',
      'field' => 'contact_id',
    ),
  );

  // PCP FIELDS

  // Numeric PCP ID
  $data['civicrm_pcp']['id'] = array(
    'title' => t('PCP ID'),
    'help' => t('The numeric ID of the Personal Campaign Page.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // PCP Text Link
  $data['civicrm_pcp']['url'] = array(
    'title' => t('URL'),
    'help' => t('Generates URLs for various PCP actions.'),
    'field' => array(
      'handler' => 'civicrm_handler_field_link_pcp',
    ),
  );

  // PCP status
  $data['civicrm_pcp']['status'] = array(
    'title' => t('Page Status'),
    'real field' => 'status_id',
    'help' => t('The Status of this Personal Contribution Page'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Contribute_PseudoConstant',
      'pseudo method' => 'pcpStatus',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Contribute_PseudoConstant',
      'pseudo method' => 'pcpStatus',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // PCP title
  $data['civicrm_pcp']['title'] = array(
    'title' => t('Title'),
    'help' => t('The title of this Personal Contribution Page'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // introductory text
  $data['civicrm_pcp']['intro_text'] = array(
    'title' => t('Intro text'),
    'help' => t('Introductory welcome text'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // page text
  $data['civicrm_pcp']['page_text'] = array(
    'title' => t('Page text'),
    'help' => t('Page text (your message)'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // text for donate link
  $data['civicrm_pcp']['donate_link_text'] = array(
    'title' => t('Text for donate link'),
    'help' => t('Text for donate link'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // contribution page
  $data['civicrm_pcp']['contribution_page'] = array(
    'title' => t('Associated Contribution Page'),
    'real field' => 'page_id',
    'help' => t('The Contribution or Event Page with which this Personal Campaign Page is associated.'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Contribute_PseudoConstant',
      'pseudo method' => 'contributionPage',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Contribute_PseudoConstant',
      'pseudo method' => 'contributionPage',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // event page
  $data['civicrm_pcp']['event_page'] = array(
    'title' => t('Associated Event Page'),
    'real field' => 'page_id',
    'help' => t('The Event or Event Page with which this Personal Campaign Page is associated.'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Event_PseudoConstant',
      'pseudo method' => 'event',
      'pseudo args' => array(NULL, FALSE, 'is_template = 0'),
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Event_PseudoConstant',
      'pseudo method' => 'event',
      'pseudo args' => array(NULL, FALSE, 'is_template = 0'),
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //BOOLEAN : IS Thermometer
  $data['civicrm_pcp']['is_thermometer'] = array(
    'title' => t('Is Thermometer'),
    'help' => t('Does the page use a progress indicator?'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //BOOLEAN : Is Honor Roll
  $data['civicrm_pcp']['is_honor_roll'] = array(
    'title' => t('Is Honor Roll'),
    'help' => t('Does the page use an Honor Roll?'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // fundraising goal
  $data['civicrm_pcp']['goal_amount'] = array(
    'title' => t('Goal Amount'),
    'help' => t('Goal Amount.'),
    'field' => array(
      'handler' => 'civicrm_handler_field_money',
      'click sortable' => TRUE,
      'currency field' => 'currency',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // amount raised
  $data['civicrm_pcp']['raised_amount'] = array(
    'title' => t('Amount Raised'),
    'help' => t('Amount raised through this Personal Campaign Page'),
    'real field' => 'id',
    'field' => array(
      'handler' => 'civicrm_handler_field_pcp_raised_amount',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'civicrm_handler_sort_pcp_raised_amount',
    ),
  );

  // referer (not really sure what this is, but...)
  $data['civicrm_pcp']['referer'] = array(
    'title' => t('Referer'),
    'help' => t('Referer'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //BOOLEAN : Is Active
  $data['civicrm_pcp']['is_active'] = array(
    'title' => t('Is Active'),
    'help' => t('Is this page active?'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // currency
  $data['civicrm_pcp']['currency'] = array(
    'title' => t('Currency'),
    'help' => t('Currency'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['civicrm_pcp']['image_url'] = array(
    'title' => t('Image URL'),
    'real field' => 'id',
    'help' => t('URL for the image the user uploaded when creating this page'),
    'field' => array(
      'handler' => 'civicrm_handler_field_file',
    ),
  );

  //Contribution Text Link
  $data['civicrm_contribution']['text_link'] = array(
    'field' => array(
      'title' => t('Text Link'),
      'help' => t('Creates a Text Link for various contribution actions.'),
      'handler' => 'civicrm_handler_field_link_contribution',
    ),
  );
}
