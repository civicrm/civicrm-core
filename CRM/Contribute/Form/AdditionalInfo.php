<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.5                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Contribute_Form_AdditionalInfo {

  /**
   * Function to build the form for Premium Information.
   *
   * @access public
   *
   * @param $form
   *
   * @return void
   */
  static function buildPremium(&$form) {
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
    $sel = & $form->addElement('hierselect', "product_name", ts('Premium'), 'onclick="showMinContrib();"');
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
   * Function to build the form for Additional Details.
   *
   * @access public
   *
   * @param $form
   *
   * @return void
   */
  static function buildAdditionalDetail(&$form) {
    //Additional information section
    $form->add('hidden', 'hidden_AdditionalDetail', 1);

    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution');

    $form->addDateTime('thankyou_date', ts('Thank-you Sent'), FALSE, array('formatType' => 'activityDateTime'));

    // add various amounts
    $nonDeductAmount = & $form->add('text', 'non_deductible_amount', ts('Non-deductible Amount'),
               $attributes['non_deductible_amount']
    );
    $form->addRule('non_deductible_amount', ts('Please enter a valid monetary value for Non-deductible Amount.'), 'money');

    if ($form->_online) {
      $nonDeductAmount->freeze();
    }
    $feeAmount = & $form->add('text', 'fee_amount', ts('Fee Amount'),
               $attributes['fee_amount']
    );
    $form->addRule('fee_amount', ts('Please enter a valid monetary value for Fee Amount.'), 'money');
    if ($form->_online) {
      $feeAmount->freeze();
    }

    $netAmount = & $form->add('text', 'net_amount', ts('Net Amount'),
               $attributes['net_amount']
    );
    $form->addRule('net_amount', ts('Please enter a valid monetary value for Net Amount.'), 'money');
    if ($form->_online) {
      $netAmount->freeze();
    }
    $element = & $form->add('text', 'invoice_id', ts('Invoice ID'),
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

    $form->add('select', 'contribution_page_id',
      ts('Online Contribution Page'),
      array(
        '' => ts('- select -')
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
   * This function is used by  CRM/Pledge/Form/Pledge.php
   *
   * Function to build the form for PaymentReminders Information.
   *
   * @access public
   *
   * @param $form
   *
   * @return void
   */
  static function buildPaymentReminders(&$form) {
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
   * Function to process the Premium Information
   *
   * @access public
   *
   * @param $params
   * @param $contributionID
   * @param null $premiumID
   * @param null $options
   * @return void
   */
  static function processPremium(&$params, $contributionID, $premiumID = NULL, &$options = NULL) {
    $dao = new CRM_Contribute_DAO_ContributionProduct();
    $dao->contribution_id = $contributionID;
    $dao->product_id = $params['product_name'][0];
    $dao->fulfilled_date = CRM_Utils_Date::processDate($params['fulfilled_date'], NULL, TRUE);
    $isDeleted = False;
    //CRM-11106
    $premiumParams = array(
      'id' => $params['product_name'][0],
    );
    $productDetails = array();
    CRM_Contribute_BAO_ManagePremiums::retrieve($premiumParams, $productDetails);
    $dao->financial_type_id = CRM_Utils_Array::value('financial_type_id', $productDetails);
    if (!empty($options[$params['product_name'][0]])) {
      $dao->product_option = $options[$params['product_name'][0]][$params['product_name'][1]];
    }
    if ($premiumID) {
      $premoumDAO = new CRM_Contribute_DAO_ContributionProduct();
      $premoumDAO->id = $premiumID;
      $premoumDAO->find(TRUE);
      if ($premoumDAO->product_id == $params['product_name'][0]) {
        $dao->id = $premiumID;
        $premium = $dao->save();
      }
      else {
        $premoumDAO->delete();
        $isDeleted = TRUE;
        $premium = $dao->save();
      }
    }
    else {
      $premium = $dao->save();
    }
    //CRM-11106
    if ($premiumID == NULL || $isDeleted) {
      $params = array(
        'cost' => CRM_Utils_Array::value('cost', $productDetails),
        'currency' => CRM_Utils_Array::value('currency', $productDetails),
        'financial_type_id' => CRM_Utils_Array::value('financial_type_id', $productDetails),
        'contributionId' => $contributionID
      );
      if ($isDeleted) {
        $params['oldPremium']['product_id'] = $premoumDAO->product_id;
        $params['oldPremium']['contribution_id'] = $premoumDAO->contribution_id;
      }
      CRM_Core_BAO_FinancialTrxn::createPremiumTrxn($params);
    }
  }

  /**
   * Function to process the Note
   *
   * @access public
   *
   * @param $params
   * @param $contactID
   * @param $contributionID
   * @param null $contributionNoteID
   *
   * @return void
   */
  static function processNote(&$params, $contactID, $contributionID, $contributionNoteID = NULL) {
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
   * Function to process the Common data
   *
   * @access public
   *
   * @param $params
   * @param $formatted
   * @param $form
   * @return void
   */
  static function postProcessCommon(&$params, &$formatted, &$form) {
    $fields = array(
      'non_deductible_amount',
      'total_amount',
      'fee_amount',
      'net_amount',
      'trxn_id',
      'invoice_id',
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
      $customFields,
      CRM_Utils_Array::value('id', $params, NULL),
      'Contribution'
    );
  }

  /**
   * Function to send email receipt.
   *
   * @form object  of Contribution form.
   *
   * @param $form
   * @param array $params (reference ) an assoc array of name/value pairs.
   * @$ccContribution boolen,  is it credit card contribution.
   * @param bool $ccContribution
   * @access public.
   *
   * @return array
   */
  static function emailReceipt(&$form, &$params, $ccContribution = FALSE) {
    $form->assign('receiptType', 'contribution');
    // Retrieve Financial Type Name from financial_type_id
    $params['contributionType_name'] = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType',
      $params['financial_type_id']);
    if (!empty($params['payment_instrument_id'])) {
      $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();
      $params['paidBy'] = $paymentInstrument[$params['payment_instrument_id']];
    }

    // retrieve individual prefix value for honoree
    if (isset($params['soft_credit'])) {
      $softCreditTypes = $softCredits = array();
      foreach ($params['soft_credit'] as $key => $softCredit) {
        $softCredits[$key] = array(
          'Name' => $softCredit['contact_name'],
          'Amount' => CRM_Utils_Money::format($softCredit['amount'], $softCredit['currency'])
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
        $productDAO->find(TRUE);
        $params['product_name'] = $productDAO->name;
        $params['product_sku'] = $productDAO->sku;

        if (empty($params['product_option']) && !empty($form->_options
[$params['product_name'][0]])) {
          $params['product_option'] = $form->_options[$params['product_name'][0]][$params['product_name'][1]];
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
        'PDFFilename' => ts('receipt').'.pdf',
      )
    );

    return $sendReceipt;
  }

}


