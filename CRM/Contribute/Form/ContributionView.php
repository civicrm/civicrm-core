<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class generates form components for Payment-Instrument.
 */
class CRM_Contribute_Form_ContributionView extends CRM_Core_Form {

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    $id = $this->get('id');
    $params = array('id' => $id);
    $context = CRM_Utils_Request::retrieve('context', 'String', $this);
    $this->assign('context', $context);

    $values = CRM_Contribute_BAO_Contribution::getValuesWithMappings($params);

    if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus() && $this->_action & CRM_Core_Action::VIEW) {
      $financialTypeID = CRM_Contribute_PseudoConstant::financialType($values['financial_type_id']);
      CRM_Financial_BAO_FinancialType::checkPermissionedLineItems($id, 'view');
      if (CRM_Financial_BAO_FinancialType::checkPermissionedLineItems($id, 'edit', FALSE)) {
        $this->assign('canEdit', TRUE);
      }
      if (CRM_Financial_BAO_FinancialType::checkPermissionedLineItems($id, 'delete', FALSE)) {
        $this->assign('canDelete', TRUE);
      }
      if (!CRM_Core_Permission::check('view contributions of type ' . $financialTypeID)) {
        CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
      }
    }
    elseif ($this->_action & CRM_Core_Action::VIEW) {
      $this->assign('noACL', TRUE);
    }
    CRM_Contribute_BAO_Contribution::resolveDefaults($values);
    // @todo - I believe this cancelledStatus is unused - if someone reaches the same conclusion
    // by grepping then the next few lines can go.
    $cancelledStatus = TRUE;
    $status = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    if (CRM_Utils_Array::value('contribution_status_id', $values) == array_search('Cancelled', $status)) {
      $cancelledStatus = FALSE;
    }
    $this->assign('cancelledStatus', $cancelledStatus);

    if (!empty($values['contribution_page_id'])) {
      $contribPages = CRM_Contribute_PseudoConstant::contributionPage(NULL, TRUE);
      $values['contribution_page_title'] = CRM_Utils_Array::value(CRM_Utils_Array::value('contribution_page_id', $values), $contribPages);
    }

    // get received into i.e to_financial_account_id from last trxn
    $financialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($values['contribution_id'], 'DESC');
    $values['to_financial_account'] = '';
    if (!empty($financialTrxnId['financialTrxnId'])) {
      $values['to_financial_account_id'] = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialTrxn', $financialTrxnId['financialTrxnId'], 'to_financial_account_id');
      if ($values['to_financial_account_id']) {
        $values['to_financial_account'] = CRM_Contribute_PseudoConstant::financialAccount($values['to_financial_account_id']);
      }
      $values['payment_processor_id'] = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialTrxn', $financialTrxnId['financialTrxnId'], 'payment_processor_id');
      if ($values['payment_processor_id']) {
        $values['payment_processor_name'] = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_PaymentProcessor', $values['payment_processor_id'], 'name');
      }
    }

    if (!empty($values['contribution_recur_id'])) {
      $sql = "SELECT  installments, frequency_interval, frequency_unit FROM civicrm_contribution_recur WHERE id = %1";
      $params = array(1 => array($values['contribution_recur_id'], 'Integer'));
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      if ($dao->fetch()) {
        $values['recur_installments'] = $dao->installments;
        $values['recur_frequency_unit'] = $dao->frequency_unit;
        $values['recur_frequency_interval'] = $dao->frequency_interval;
      }
    }

    $groupTree = CRM_Core_BAO_CustomGroup::getTree('Contribution', $this, $id, 0, CRM_Utils_Array::value('financial_type_id', $values));
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, FALSE, NULL, NULL, NULL, $id);

    $premiumId = NULL;
    if ($id) {
      $dao = new CRM_Contribute_DAO_ContributionProduct();
      $dao->contribution_id = $id;
      if ($dao->find(TRUE)) {
        $premiumId = $dao->id;
        $productID = $dao->product_id;
      }
    }

    if ($premiumId) {
      $productDAO = new CRM_Contribute_DAO_Product();
      $productDAO->id = $productID;
      $productDAO->find(TRUE);

      $this->assign('premium', $productDAO->name);
      $this->assign('option', $dao->product_option);
      $this->assign('fulfilled', $dao->fulfilled_date);
    }

    // Get Note
    $noteValue = CRM_Core_BAO_Note::getNote(CRM_Utils_Array::value('id', $values), 'civicrm_contribution');
    $values['note'] = array_values($noteValue);

    // show billing address location details, if exists
    if (!empty($values['address_id'])) {
      $addressParams = array('id' => CRM_Utils_Array::value('address_id', $values));
      $addressDetails = CRM_Core_BAO_Address::getValues($addressParams, FALSE, 'id');
      $addressDetails = array_values($addressDetails);
      $values['billing_address'] = $addressDetails[0]['display'];
    }

    //assign soft credit record if exists.
    $SCRecords = CRM_Contribute_BAO_ContributionSoft::getSoftContribution($values['contribution_id'], TRUE);
    if (!empty($SCRecords['soft_credit'])) {
      $this->assign('softContributions', $SCRecords['soft_credit']);
      unset($SCRecords['soft_credit']);
    }

    //assign pcp record if exists
    foreach ($SCRecords as $name => $value) {
      $this->assign($name, $value);
    }

    $lineItems = array();
    if ($id) {
      $lineItem = CRM_Price_BAO_LineItem::getLineItems($id, 'contribution', 1, TRUE, TRUE);
      if (!empty($lineItem)) {
        $lineItems[] = $lineItem;
      }

    }
    $this->assign('lineItem', empty($lineItems) ? FALSE : $lineItems);
    $values['totalAmount'] = $values['total_amount'];

    //do check for campaigns
    if ($campaignId = CRM_Utils_Array::value('campaign_id', $values)) {
      $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns($campaignId);
      $values['campaign'] = $campaigns[$campaignId];
    }
    if ($values['contribution_status'] == 'Refunded') {
      $this->assign('refund_trxn_id', CRM_Core_BAO_FinancialTrxn::getRefundTransactionTrxnID($id));
    }

    // assign values to the template
    $this->assign($values);
    $invoiceSettings = Civi::settings()->get('contribution_invoice_settings');
    $invoicing = CRM_Utils_Array::value('invoicing', $invoiceSettings);
    $this->assign('invoicing', $invoicing);
    $this->assign('isDeferred', CRM_Utils_Array::value('deferred_revenue_enabled', $invoiceSettings));
    if ($invoicing && isset($values['tax_amount'])) {
      $this->assign('totalTaxAmount', $values['tax_amount']);
    }

    $displayName = CRM_Contact_BAO_Contact::displayName($values['contact_id']);
    $this->assign('displayName', $displayName);

    // Check if this is default domain contact CRM-10482
    if (CRM_Contact_BAO_Contact::checkDomainContact($values['contact_id'])) {
      $displayName .= ' (' . ts('default organization') . ')';
    }

    // omitting contactImage from title for now since the summary overlay css doesn't work outside of our crm-container
    CRM_Utils_System::setTitle(ts('View Contribution from') . ' ' . $displayName);

    // add viewed contribution to recent items list
    $url = CRM_Utils_System::url('civicrm/contact/view/contribution',
      "action=view&reset=1&id={$values['id']}&cid={$values['contact_id']}&context=home"
    );

    $title = $displayName . ' - (' . CRM_Utils_Money::format($values['total_amount']) . ' ' . ' - ' . $values['financial_type'] . ')';

    $recentOther = array();
    if (CRM_Core_Permission::checkActionPermission('CiviContribute', CRM_Core_Action::UPDATE)) {
      $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/contact/view/contribution',
        "action=update&reset=1&id={$values['id']}&cid={$values['contact_id']}&context=home"
      );
    }
    if (CRM_Core_Permission::checkActionPermission('CiviContribute', CRM_Core_Action::DELETE)) {
      $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/contact/view/contribution',
        "action=delete&reset=1&id={$values['id']}&cid={$values['contact_id']}&context=home"
      );
    }
    CRM_Utils_Recent::add($title,
      $url,
      $values['id'],
      'Contribution',
      $values['contact_id'],
      NULL,
      $recentOther
    );
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    $this->addButtons(array(
        array(
          'type' => 'cancel',
          'name' => ts('Done'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
      )
    );
  }

}
