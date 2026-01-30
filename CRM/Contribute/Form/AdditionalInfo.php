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
   * Process the Premium Information.
   *
   * @param array $params
   * @param int $contributionID
   * @param int $premiumID
   * @param array $options
   *
   * @deprecated since 6.11 will be removed around 6.20.
   */
  public static function processPremium($params, $contributionID, $premiumID = NULL, $options = []) {
    CRM_Core_Error::deprecatedFunctionWarning('take a copy');
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
   *
   * @deprecated since 6.11 will be removed around 6.20.
   */
  public static function processNote($params, $contactID, $contributionID, $contributionNoteID = NULL) {
    CRM_Core_Error::deprecatedFunctionWarning('take a copy');
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
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function emailReceipt($form, &$params) {
    $form->assign('receiptType', 'contribution');

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

    $valuesForForm = CRM_Contribute_Form_AbstractEditPayment::formatCreditCardDetails($params);
    $form->assignVariables($valuesForForm, ['credit_card_exp_date', 'credit_card_type', 'credit_card_number']);

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

    [$sendReceipt] = CRM_Core_BAO_MessageTemplate::sendTemplate(
      [
        'workflow' => 'contribution_offline_receipt',
        'from' => $params['from_email_address'],
        'toName' => $contributorDisplayName,
        'toEmail' => $contributorEmail,
        'isTest' => $form->_mode === 'test',
        'PDFFilename' => ts('receipt') . '.pdf',
        'modelProps' => [
          'contributionID' => $params['contribution_id'],
          'contactID' => $params['contact_id'],
        ],
        'isEmailPdf' => Civi::settings()->get('invoice_is_email_pdf'),
      ]
    );

    return $sendReceipt;
  }

}
