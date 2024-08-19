<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contribute_Form_AdditionalInfo {

  /**
   * Build the form object for Premium Information.
   *
   * Called from the CRM_Contribute_Form_Contribute function and seemingly nowhere else.
   *
   * Probably this should be on the form that uses it since it is not used on multiple forms.
   *
   * Putting it on this class doesn't seem to reduce complexity.
   *
   * @param CRM_Core_Form $form
   */
  public static function buildPremium($form) {
    //premium section
    $form->add('hidden', 'hidden_Premium', 1);
    $sel1 = $sel2 = [];

    $dao = new CRM_Contribute_DAO_Product();
    $dao->is_active = 1;
    $dao->find();
    $min_amount = [];
    $sel1[0] = ts('-select product-');
    while ($dao->fetch()) {
      $sel1[$dao->id] = $dao->name . " ( " . $dao->sku . " )";
      $min_amount[$dao->id] = $dao->min_contribution;
      $options = CRM_Contribute_BAO_Premium::parseProductOptions($dao->options);
      if (!empty($options)) {
        $options = ['' => ts('- select -')] + $options;
        $sel2[$dao->id] = $options;
      }
      $form->assign('premiums', TRUE);
    }
    $form->_options = $sel2;
    $form->assign('mincontribution', $min_amount);
    $sel = &$form->addElement('hierselect', "product_name", ts('Premium'), 'onclick="showMinContrib();"');
    $js = "<script type='text/javascript'>\n";
    $formName = 'document.forms.' . $form->getName();

    for ($k = 1; $k < 2; $k++) {
      if (!isset($defaults['product_name'][$k]) || (!$defaults['product_name'][$k])) {
        $js .= "{$formName}['product_name[$k]'].style.display = 'none';\n";
      }
    }

    $sel->setOptions([$sel1, $sel2]);
    $js .= "</script>\n";
    $form->assign('initHideBoxes', $js);

    $form->add('datepicker', 'fulfilled_date', ts('Fulfilled'), [], FALSE, ['time' => FALSE]);
    $form->addElement('text', 'min_amount', ts('Minimum Contribution Amount'));
  }

  /**
   * Build the form object for Additional Details.
   *
   * @param CRM_Core_Form $form
   */
  public static function buildAdditionalDetail(&$form) {
    //Additional information section
    $form->add('hidden', 'hidden_AdditionalDetail', 1);

    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution');

    $form->addField('thankyou_date', ['entity' => 'contribution'], FALSE, FALSE);

    // add various amounts
    $nonDeductAmount = &$form->add('text', 'non_deductible_amount', ts('Non-deductible Amount'),
      $attributes['non_deductible_amount']
    );
    $form->addRule('non_deductible_amount', ts('Please enter a valid monetary value for Non-deductible Amount.'), 'money');

    if ($form->_online) {
      $nonDeductAmount->freeze();
    }
    $feeAmount = &$form->add('text', 'fee_amount', ts('Fee Amount'),
      $attributes['fee_amount']
    );
    $form->addRule('fee_amount', ts('Please enter a valid monetary value for Fee Amount.'), 'money');
    if ($form->_online) {
      $feeAmount->freeze();
    }

    $element = &$form->add('text', 'invoice_id', ts('Invoice ID'),
      $attributes['invoice_id']
    );
    if ($form->_online) {
      $element->freeze();
    }
    else {
      $form->addRule('invoice_id',
        ts('This Invoice ID already exists in the database.'),
        'objectExists',
        ['CRM_Contribute_DAO_Contribution', $form->_id, 'invoice_id']
      );
    }
    $element = $form->add('text', 'creditnote_id', ts('Credit Note ID'),
      $attributes['creditnote_id']
    );
    if ($form->_online) {
      $element->freeze();
    }
    else {
      $form->addRule('creditnote_id',
        ts('This Credit Note ID already exists in the database.'),
        'objectExists',
        ['CRM_Contribute_DAO_Contribution', $form->_id, 'creditnote_id']
      );
    }

    $form->add('select', 'contribution_page_id',
      ts('Contribution Page'),
      ['' => ts('- select -')] + CRM_Contribute_PseudoConstant::contributionPage(),
      FALSE,
      ['class' => 'crm-select2']
    );

    $form->add('textarea', 'note', ts('Notes'), ["rows" => 4, "cols" => 60]);

    $statusName = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    if ($form->_id && $form->_values['contribution_status_id'] == array_search('Cancelled', $statusName)) {
      $feeAmount->freeze();
    }

  }

  /**
   * used by  CRM/Pledge/Form/Pledge.php
   *
   * Build the form object for PaymentReminders Information.
   *
   * @deprecated since 5.68 will be removed around 5.78.
   * @param CRM_Core_Form $form
   */
  public static function buildPaymentReminders(&$form) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative, will be removed around 5.78');
    //PaymentReminders section
    $form->add('hidden', 'hidden_PaymentReminders', 1);
    $form->add('text', 'initial_reminder_day', ts('Send Initial Reminder'), ['size' => 3]);
    $form->addRule('initial_reminder_day', ts('Please enter a valid reminder day.'), 'positiveInteger');
    $form->add('text', 'max_reminders', ts('Send up to'), ['size' => 3]);
    $form->addRule('max_reminders', ts('Please enter a valid No. of reminders.'), 'positiveInteger');
    $form->add('text', 'additional_reminder_day', ts('Send additional reminders'), ['size' => 3]);
    $form->addRule('additional_reminder_day', ts('Please enter a valid additional reminder day.'), 'positiveInteger');
  }

  /**
   * Process the Premium Information.
   *
   * @param array $params
   * @param int $contributionID
   * @param int $premiumID
   * @param array $options
   */
  public static function processPremium($params, $contributionID, $premiumID = NULL, $options = []) {
    $selectedProductID = $params['product_name'][0];
    $selectedProductOptionID = $params['product_name'][1] ?? NULL;

    $dao = new CRM_Contribute_DAO_ContributionProduct();
    $dao->contribution_id = $contributionID;
    $dao->product_id = $selectedProductID;
    $dao->fulfilled_date = $params['fulfilled_date'];
    $isDeleted = FALSE;

    //CRM-11106
    $premiumParams = [
      'id' => $selectedProductID,
    ];

    $productDetails = [];
    CRM_Contribute_BAO_Product::retrieve($premiumParams, $productDetails);
    $dao->financial_type_id = $productDetails['financial_type_id'] ?? NULL;
    if (!empty($options[$selectedProductID])) {
      $dao->product_option = $selectedProductOptionID;
    }

    // This IF condition codeblock does the following:
    // 1. If premium is present then get previous contribution-product mapping record (if any) based on contribtuion ID.
    //   If found and the product chosen doesn't matches with old done, then delete or else set the ID for update
    // 2. If no product is chosen theb delete the previous contribution-product mapping record based on contribtuion ID.
    if ($premiumID || empty($selectedProductID)) {
      $ContributionProduct = new CRM_Contribute_DAO_ContributionProduct();
      $ContributionProduct->contribution_id = $contributionID;
      $ContributionProduct->find(TRUE);
      // here $selectedProductID can be 0 in case one unselect the premium product on backoffice update form
      if ($ContributionProduct->product_id == $selectedProductID) {
        $dao->id = $premiumID;
      }
      else {
        $ContributionProduct->delete();
        $isDeleted = TRUE;
      }
    }

    // only add/update contribution product when a product is selected
    if (!empty($selectedProductID)) {
      $dao->save();
    }

    //CRM-11106
    if ($premiumID == NULL || $isDeleted) {
      $premiumParams = [
        'cost' => $productDetails['cost'] ?? NULL,
        'currency' => $productDetails['currency'] ?? NULL,
        'financial_type_id' => $productDetails['financial_type_id'] ?? NULL,
        'contributionId' => $contributionID,
      ];
      if ($isDeleted) {
        $premiumParams['oldPremium']['product_id'] = $ContributionProduct->product_id;
        $premiumParams['oldPremium']['contribution_id'] = $ContributionProduct->contribution_id;
      }
      CRM_Core_BAO_FinancialTrxn::createPremiumTrxn($premiumParams);
    }
  }

  /**
   * Process the Note.
   *
   *
   * @param array $params
   * @param int $contactID
   * @param int $contributionID
   * @param int $contributionNoteID
   *
   * @throws \CRM_Core_Exception
   */
  public static function processNote($params, $contactID, $contributionID, $contributionNoteID = NULL) {
    if (CRM_Utils_System::isNull($params['note']) && $contributionNoteID) {
      CRM_Core_BAO_Note::deleteRecord(['id' => $contributionNoteID]);
      $status = ts('Selected Note has been deleted successfully.');
      CRM_Core_Session::setStatus($status, ts('Deleted'), 'success');
      return;
    }
    //process note
    $noteParams = [
      'entity_table' => 'civicrm_contribution',
      'note' => $params['note'],
      'entity_id' => $contributionID,
      'contact_id' => $contactID,
      'id' => $contributionNoteID,
    ];
    if ($contributionNoteID) {
      $noteParams['note'] = $noteParams['note'] ?: "null";
    }
    CRM_Core_BAO_Note::add($noteParams);
  }

  /**
   * Process the Common data.
   *
   * @param array $params
   * @param array $formatted
   * @param CRM_Core_Form $form
   */
  public static function postProcessCommon(&$params, &$formatted, &$form) {
    $fields = [
      'non_deductible_amount',
      'total_amount',
      'fee_amount',
      'trxn_id',
      'invoice_id',
      'creditnote_id',
      'campaign_id',
      'contribution_page_id',
    ];
    foreach ($fields as $f) {
      $formatted[$f] = $params[$f] ?? NULL;
    }

    if (!empty($params['thankyou_date'])) {
      $formatted['thankyou_date'] = CRM_Utils_Date::processDate($params['thankyou_date']);
    }
    else {
      $formatted['thankyou_date'] = 'null';
    }

    if (!empty($params['is_email_receipt'])) {
      $params['receipt_date'] = $formatted['receipt_date'] = date('YmdHis');
    }

    $formatted['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
       $params['id'] ?? NULL,
      'Contribution'
    );
  }

  /**
   * Send email receipt.
   *
   * @param \CRM_Core_Form $form
   *   instance of Contribution form.
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param bool $ccContribution
   *   is it credit card contribution.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function emailReceipt(&$form, &$params, $ccContribution = FALSE) {
    $form->assign('receiptType', 'contribution');
    // Retrieve Financial Type Name from financial_type_id
    $params['contributionType_name'] = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType',
      $params['financial_type_id']);
    if (!empty($params['payment_instrument_id'])) {
      $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();
      $params['paidBy'] = $paymentInstrument[$params['payment_instrument_id']];
      if ($params['paidBy'] !== 'Check' && isset($params['check_number'])) {
        unset($params['check_number']);
      }
    }

    // retrieve individual prefix value for honoree
    if (isset($params['soft_credit'])) {
      $softCreditTypes = $softCredits = [];
      foreach ($params['soft_credit'] as $key => $softCredit) {
        $softCredits[$key] = [
          'Name' => $softCredit['contact_name'],
          'Amount' => CRM_Utils_Money::format($softCredit['amount'], $softCredit['currency']),
        ];
        $softCreditTypes[$key] = $softCredit['soft_credit_type_label'];
      }
      $form->assign('softCreditTypes', $softCreditTypes);
      $form->assign('softCredits', $softCredits);
    }

    // retrieve premium product name and assigned fulfilled
    // date to template
    if (!empty($params['hidden_Premium'])) {
      if (isset($params['product_name']) &&
        is_array($params['product_name']) &&
        !empty($params['product_name'])
      ) {
        $productDAO = new CRM_Contribute_DAO_Product();
        $productDAO->id = $params['product_name'][0];
        $productOptionID = $params['product_name'][1];
        $productDAO->find(TRUE);
        $params['product_name'] = $productDAO->name;
        $params['product_sku'] = $productDAO->sku;

        if (empty($params['product_option']) && !empty($form->_options[$productDAO->id])) {
          $params['product_option'] = $form->_options[$productDAO->id][$productOptionID];
        }
      }
      if (!empty($params['fulfilled_date'])) {
        $form->assign('fulfilled_date', $params['fulfilled_date']);
      }
    }

    $form->assign('ccContribution', $ccContribution);
    if ($ccContribution) {
      $form->assignBillingName($params);
      $form->assign('address', CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters($params));

      $valuesForForm = CRM_Contribute_Form_AbstractEditPayment::formatCreditCardDetails($params);
      $form->assignVariables($valuesForForm, ['credit_card_exp_date', 'credit_card_type', 'credit_card_number']);
    }
    else {
      //offline contribution
      // assigned various dates to the templates
      $form->assign('receipt_date', CRM_Utils_Date::processDate($params['receipt_date']));

      if (!empty($params['cancel_date'])) {
        $form->assign('cancel_date', CRM_Utils_Date::processDate($params['cancel_date']));
      }
      if (!empty($params['thankyou_date'])) {
        $form->assign('thankyou_date', CRM_Utils_Date::processDate($params['thankyou_date']));
      }
      if ($form->_action & CRM_Core_Action::UPDATE) {
        $form->assign('lineItem', empty($form->_lineItems) ? FALSE : $form->_lineItems);
      }
    }

    //handle custom data
    if (!empty($params['hidden_custom'])) {
      $contribParams = [['contribution_id', '=', $params['contribution_id'], 0, 0]];
      if ($form->_mode == 'test') {
        $contribParams[] = ['contribution_test', '=', 1, 0, 0];
      }

      //retrieve custom data
      $customGroup = [];

      foreach ($form->_groupTree as $groupID => $group) {
        $customFields = $customValues = [];
        if ($groupID == 'info') {
          continue;
        }

        $is_public = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $groupID, 'is_public');
        if (!$is_public) {
          continue;
        }
        foreach ($group['fields'] as $k => $field) {
          $field['title'] = $field['label'];
          $customFields["custom_{$k}"] = $field;
        }

        //build the array of customgroup contain customfields.
        CRM_Core_BAO_UFGroup::getValues($params['contact_id'], $customFields, $customValues, FALSE, $contribParams);
        $customGroup[$group['title']] = $customValues;
      }
      //assign all custom group and corresponding fields to template.
      $form->assign('customGroup', $customGroup);
    }

    $form->assign('formValues', $params);
    list($contributorDisplayName,
      $contributorEmail
      ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($params['contact_id']);
    $form->assign('contactID', $params['contact_id']);
    $form->assign('contributionID', $params['contribution_id']);

    if (!empty($params['currency'])) {
      $form->assign('currency', $params['currency']);
    }

    if (!empty($params['receive_date'])) {
      $form->assign('receive_date', CRM_Utils_Date::processDate($params['receive_date']));
    }

    [$sendReceipt] = CRM_Core_BAO_MessageTemplate::sendTemplate(
      [
        'workflow' => 'contribution_offline_receipt',
        'contactId' => $params['contact_id'],
        'contributionId' => $params['contribution_id'],
        'tokenContext' => ['contributionId' => (int) $params['contribution_id'], 'contactId' => $params['contact_id']],
        'from' => $params['from_email_address'],
        'toName' => $contributorDisplayName,
        'toEmail' => $contributorEmail,
        'isTest' => $form->_mode === 'test',
        'PDFFilename' => ts('receipt') . '.pdf',
        'isEmailPdf' => Civi::settings()->get('invoice_is_email_pdf'),
      ]
    );

    return $sendReceipt;
  }

}
