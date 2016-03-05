<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.7                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
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
  public static function buildPremium(&$form) {
    //premium section
    $form->add('hidden', 'hidden_Premium', 1);
    $sel1 = $sel2 = array();

    $dao = new CRM_Contribute_DAO_Product();
    $dao->is_active = 1;
    $dao->find();
    $min_amount = array();
    $sel1[0] = '-select product-';
    while ($dao->fetch()) {
      $sel1[$dao->id] = $dao->name . " ( " . $dao->sku . " )";
      $min_amount[$dao->id] = $dao->min_contribution;
      $options = explode(',', $dao->options);
      foreach ($options as $k => $v) {
        $options[$k] = trim($v);
      }
      if ($options[0] != '') {
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

    $sel->setOptions(array($sel1, $sel2));
    $js .= "</script>\n";
    $form->assign('initHideBoxes', $js);

    $form->addDate('fulfilled_date', ts('Fulfilled'), FALSE, array('formatType' => 'activityDate'));
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

    $form->addDateTime('thankyou_date', ts('Thank-you Sent'), FALSE, array('formatType' => 'activityDateTime'));

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

    $netAmount = &$form->add('text', 'net_amount', ts('Net Amount'),
      $attributes['net_amount']
    );
    $form->addRule('net_amount', ts('Please enter a valid monetary value for Net Amount.'), 'money');
    if ($form->_online) {
      $netAmount->freeze();
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
        array('CRM_Contribute_DAO_Contribution', $form->_id, 'invoice_id')
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
        array('CRM_Contribute_DAO_Contribution', $form->_id, 'creditnote_id')
      );
    }

    $form->add('select', 'contribution_page_id',
      ts('Online Contribution Page'),
      array(
        '' => ts('- select -'),
      ) +
      CRM_Contribute_PseudoConstant::contributionPage()
    );

    $form->add('textarea', 'note', ts('Notes'), array("rows" => 4, "cols" => 60));

    $statusName = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    if ($form->_id && $form->_values['contribution_status_id'] == array_search('Cancelled', $statusName)) {
      $netAmount->freeze();
      $feeAmount->freeze();
    }

  }

  /**
   * used by  CRM/Pledge/Form/Pledge.php
   *
   * Build the form object for PaymentReminders Information.
   *
   * @param CRM_Core_Form $form
   */
  public static function buildPaymentReminders(&$form) {
    //PaymentReminders section
    $form->add('hidden', 'hidden_PaymentReminders', 1);
    $form->add('text', 'initial_reminder_day', ts('Send Initial Reminder'), array('size' => 3));
    $form->addRule('initial_reminder_day', ts('Please enter a valid reminder day.'), 'positiveInteger');
    $form->add('text', 'max_reminders', ts('Send up to'), array('size' => 3));
    $form->addRule('max_reminders', ts('Please enter a valid No. of reminders.'), 'positiveInteger');
    $form->add('text', 'additional_reminder_day', ts('Send additional reminders'), array('size' => 3));
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
  public static function processPremium($params, $contributionID, $premiumID = NULL, $options = array()) {
    $selectedProductID = $params['product_name'][0];
    $selectedProductOptionID = CRM_Utils_Array::value(1, $params['product_name']);

    $dao = new CRM_Contribute_DAO_ContributionProduct();
    $dao->contribution_id = $contributionID;
    $dao->product_id = $selectedProductID;
    $dao->fulfilled_date = CRM_Utils_Date::processDate($params['fulfilled_date'], NULL, TRUE);
    $isDeleted = FALSE;

    //CRM-11106
    $premiumParams = array(
      'id' => $selectedProductID,
    );

    $productDetails = array();
    CRM_Contribute_BAO_ManagePremiums::retrieve($premiumParams, $productDetails);
    $dao->financial_type_id = CRM_Utils_Array::value('financial_type_id', $productDetails);
    if (!empty($options[$selectedProductID])) {
      $dao->product_option = $options[$selectedProductID][$selectedProductOptionID];
    }
    if ($premiumID) {
      $ContributionProduct = new CRM_Contribute_DAO_ContributionProduct();
      $ContributionProduct->id = $premiumID;
      $ContributionProduct->find(TRUE);
      if ($ContributionProduct->product_id == $selectedProductID) {
        $dao->id = $premiumID;
      }
      else {
        $ContributionProduct->delete();
        $isDeleted = TRUE;
      }
    }

    $dao->save();
    //CRM-11106
    if ($premiumID == NULL || $isDeleted) {
      $premiumParams = array(
        'cost' => CRM_Utils_Array::value('cost', $productDetails),
        'currency' => CRM_Utils_Array::value('currency', $productDetails),
        'financial_type_id' => CRM_Utils_Array::value('financial_type_id', $productDetails),
        'contributionId' => $contributionID,
      );
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
   */
  public static function processNote($params, $contactID, $contributionID, $contributionNoteID = NULL) {
    //process note
    $noteParams = array(
      'entity_table' => 'civicrm_contribution',
      'note' => $params['note'],
      'entity_id' => $contributionID,
      'contact_id' => $contactID,
    );
    $noteID = array();
    if ($contributionNoteID) {
      $noteID = array("id" => $contributionNoteID);
      $noteParams['note'] = $noteParams['note'] ? $noteParams['note'] : "null";
    }
    CRM_Core_BAO_Note::add($noteParams, $noteID);
  }

  /**
   * Process the Common data.
   *
   * @param array $params
   * @param array $formatted
   * @param CRM_Core_Form $form
   */
  public static function postProcessCommon(&$params, &$formatted, &$form) {
    $fields = array(
      'non_deductible_amount',
      'total_amount',
      'fee_amount',
      'net_amount',
      'trxn_id',
      'invoice_id',
      'creditnote_id',
      'campaign_id',
      'contribution_page_id',
    );
    foreach ($fields as $f) {
      $formatted[$f] = CRM_Utils_Array::value($f, $params);
    }

    if (!empty($params['thankyou_date']) && !CRM_Utils_System::isNull($params['thankyou_date'])) {
      $formatted['thankyou_date'] = CRM_Utils_Date::processDate($params['thankyou_date'], $params['thankyou_date_time']);
    }
    else {
      $formatted['thankyou_date'] = 'null';
    }

    if (!empty($params['is_email_receipt'])) {
      $params['receipt_date'] = $formatted['receipt_date'] = date('YmdHis');
    }

    //special case to handle if all checkboxes are unchecked
    $customFields = CRM_Core_BAO_CustomField::getFields('Contribution',
      FALSE,
      FALSE,
      CRM_Utils_Array::value('financial_type_id',
        $params
      )
    );
    $formatted['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
      CRM_Utils_Array::value('id', $params, NULL),
      'Contribution'
    );
  }

  /**
   * Send email receipt.
   *
   * @param CRM_Core_Form $form
   *   instance of Contribution form.
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param bool $ccContribution
   *   is it credit card contribution.
   *
   * @return array
   */
  public static function emailReceipt(&$form, &$params, $ccContribution = FALSE) {
    $form->assign('receiptType', 'contribution');
    // Retrieve Financial Type Name from financial_type_id
    $params['contributionType_name'] = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType',
      $params['financial_type_id']);
    if (!empty($params['payment_instrument_id'])) {
      $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();
      $params['paidBy'] = $paymentInstrument[$params['payment_instrument_id']];
      if ($params['paidBy'] != 'Check' && isset($params['check_number'])) {
        unset($params['check_number']);
      }
    }

    // retrieve individual prefix value for honoree
    if (isset($params['soft_credit'])) {
      $softCreditTypes = $softCredits = array();
      foreach ($params['soft_credit'] as $key => $softCredit) {
        $softCredits[$key] = array(
          'Name' => $softCredit['contact_name'],
          'Amount' => CRM_Utils_Money::format($softCredit['amount'], $softCredit['currency']),
        );
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
        $form->assign('fulfilled_date', CRM_Utils_Date::processDate($params['fulfilled_date']));
      }
    }

    $form->assign('ccContribution', $ccContribution);
    if ($ccContribution) {
      //build the name.
      $name = CRM_Utils_Array::value('billing_first_name', $params);
      if (!empty($params['billing_middle_name'])) {
        $name .= " {$params['billing_middle_name']}";
      }
      $name .= ' ' . CRM_Utils_Array::value('billing_last_name', $params);
      $name = trim($name);
      $form->assign('billingName', $name);

      //assign the address formatted up for display
      $addressParts = array(
        "street_address" => "billing_street_address-{$form->_bltID}",
        "city" => "billing_city-{$form->_bltID}",
        "postal_code" => "billing_postal_code-{$form->_bltID}",
        "state_province" => "state_province-{$form->_bltID}",
        "country" => "country-{$form->_bltID}",
      );

      $addressFields = array();
      foreach ($addressParts as $name => $field) {
        $addressFields[$name] = CRM_Utils_Array::value($field, $params);
      }
      $form->assign('address', CRM_Utils_Address::format($addressFields));

      $date = CRM_Utils_Date::format($params['credit_card_exp_date']);
      $date = CRM_Utils_Date::mysqlToIso($date);
      $form->assign('credit_card_type', CRM_Utils_Array::value('credit_card_type', $params));
      $form->assign('credit_card_exp_date', $date);
      $form->assign('credit_card_number',
        CRM_Utils_System::mungeCreditCard($params['credit_card_number'])
      );
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
      $contribParams = array(array('contribution_id', '=', $params['contribution_id'], 0, 0));
      if ($form->_mode == 'test') {
        $contribParams[] = array('contribution_test', '=', 1, 0, 0);
      }

      //retrieve custom data
      $customGroup = array();

      foreach ($form->_groupTree as $groupID => $group) {
        $customFields = $customValues = array();
        if ($groupID == 'info') {
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

    $form->assign_by_ref('formValues', $params);
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

    $template = CRM_Core_Smarty::singleton();
    $taxAmt = $template->get_template_vars('dataArray');
    $eventTaxAmt = $template->get_template_vars('totalTaxAmount');
    $prefixValue = Civi::settings()->get('contribution_invoice_settings');
    $invoicing = CRM_Utils_Array::value('invoicing', $prefixValue);
    if ((!empty($taxAmt) || isset($eventTaxAmt)) && (isset($invoicing) && isset($prefixValue['is_email_pdf']))) {
      $isEmailPdf = TRUE;
    }
    else {
      $isEmailPdf = FALSE;
    }

    list($sendReceipt, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate(
      array(
        'groupName' => 'msg_tpl_workflow_contribution',
        'valueName' => 'contribution_offline_receipt',
        'contactId' => $params['contact_id'],
        'contributionId' => $params['contribution_id'],
        'from' => $params['from_email_address'],
        'toName' => $contributorDisplayName,
        'toEmail' => $contributorEmail,
        'isTest' => $form->_mode == 'test',
        'PDFFilename' => ts('receipt') . '.pdf',
        'isEmailPdf' => $isEmailPdf,
      )
    );

    return $sendReceipt;
  }

}
