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

use Civi\Api4\Contribution;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class generates form components for Payment-Instrument.
 */
class CRM_Contribute_Form_ContributionView extends CRM_Core_Form {
  use CRM_Contribute_Form_ContributeFormTrait;

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $id = $this->getContributionID();
    $this->assign('taxTerm', Civi::settings()->get('tax_term'));
    $this->assign('getTaxDetails', \Civi::settings()->get('invoicing'));

    // Check permission for action.
    $actionMapping = [
      CRM_Core_Action::VIEW => 'get',
      CRM_Core_Action::ADD => 'create',
      CRM_Core_Action::UPDATE => 'update',
      CRM_Core_Action::DELETE => 'delete',
    ];
    if (!$this->isHasAccess($actionMapping[$this->_action])) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }
    $params = ['id' => $id];
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);
    $this->assign('context', $context);

    // Note than this get could be restricted by ACLs in an extension
    $contribution = Contribution::get(TRUE)->addWhere('id', '=', $id)->addSelect('*')->execute()->first();
    if (empty($contribution)) {
      CRM_Core_Error::statusBounce(ts('Access to contribution not permitted'));
    }
    // We just cast here because it was traditionally an array called values - would be better
    // just to use 'contribution'.
    $values = (array) $contribution;
    $contributionStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $values['contribution_status_id']);

    $this->addExpectedSmartyVariables([
      'hookDiscount',
      'pricesetFieldsCount',
      'pcp_id',
      'getTaxDetails',
      // currencySymbol maybe doesn't make sense but is probably old?
      'currencySymbol',
    ]);

    // @todo - it might have been better to create a new form that extends this
    // for template contributions rather than overloading this form.
    $force_create_template = CRM_Utils_Request::retrieve('force_create_template', 'Boolean', $this, FALSE, FALSE);
    if ($force_create_template && !empty($values['contribution_recur_id']) && empty($values['is_template'])) {
      // Create a template contribution.
      $templateContributionId = CRM_Contribute_BAO_ContributionRecur::ensureTemplateContributionExists($values['contribution_recur_id']);
      if (!empty($templateContributionId)) {
        $id = $templateContributionId;
        $params = ['id' => $id];
        $values = CRM_Contribute_BAO_Contribution::getValuesWithMappings($params);
      }
    }
    $this->assign('is_template', $values['is_template']);

    CRM_Contribute_BAO_Contribution::resolveDefaults($values);

    $values['contribution_page_title'] = '';
    if (!empty($values['contribution_page_id'])) {
      $contribPages = CRM_Contribute_PseudoConstant::contributionPage(NULL, TRUE);
      $values['contribution_page_title'] = $contribPages[$values['contribution_page_id']] ?? '';
    }

    // get received into i.e to_financial_account_id from last trxn
    $financialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($this->getContributionID(), 'DESC');
    $values['to_financial_account'] = '';
    $values['payment_processor_name'] = '';
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
      $params = [1 => [$values['contribution_recur_id'], 'Integer']];
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      if ($dao->fetch()) {
        $values['recur_installments'] = $dao->installments;
        $values['recur_frequency_unit'] = $dao->frequency_unit;
        $values['recur_frequency_interval'] = $dao->frequency_interval;
      }
    }

    try {
      $participantLineItems = \Civi\Api4\LineItem::get()
        ->addSelect('entity_id', 'participant.role_id:label', 'participant.fee_level', 'participant.contact_id', 'contact.display_name')
        ->addJoin('Participant AS participant', 'LEFT', ['participant.id', '=', 'entity_id'])
        ->addJoin('Contact AS contact', 'LEFT', ['contact.id', '=', 'participant.contact_id'])
        ->addWhere('entity_table', '=', 'civicrm_participant')
        ->addWhere('contribution_id', '=', $id)
        ->addGroupBy('entity_id')
        ->execute();
    }
    catch (CRM_Core_Exception $e) {
      // likely don't have permission for events/participants
      $participantLineItems = [];
    }

    $associatedParticipants = empty($participantLineItems) ? FALSE : [];
    foreach ($participantLineItems as $participant) {
      $associatedParticipants[] = [
        'participantLink' => CRM_Utils_System::url('civicrm/contact/view/participant',
          "action=view&reset=1&id={$participant['entity_id']}&cid={$participant['participant.contact_id']}&context=home"
        ),
        'participantName' => $participant['contact.display_name'],
        'fee' => implode(', ', $participant['participant.fee_level'] ?? []),
        'role' => implode(', ', $participant['participant.role_id:label']),
      ];
    }
    $this->assign('associatedParticipants', $associatedParticipants);

    $groupTree = CRM_Core_BAO_CustomGroup::getTree('Contribution', NULL, $id, 0, $values['financial_type_id'] ?? NULL,
      NULL, TRUE, NULL, FALSE, CRM_Core_Permission::VIEW);
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, FALSE, NULL, NULL, NULL, $id);

    $premiumId = NULL;
    $dao = new CRM_Contribute_DAO_ContributionProduct();
    $dao->contribution_id = $id;
    if ($dao->find(TRUE)) {
      $premiumId = $dao->id;
      $productID = $dao->product_id;
    }

    $this->assign('premium', '');
    if ($premiumId) {
      $productDAO = new CRM_Contribute_DAO_Product();
      $productDAO->id = $productID;
      $productDAO->find(TRUE);

      // If the option has a key/val that are not identical, display as "label (key)"
      // where the "key" is somewhat assumed to be the SKU of the option
      $options = CRM_Contribute_BAO_Premium::parseProductOptions($productDAO->options);
      $option_key = $option_label = $dao->product_option;
      if ($option_key && !empty($options[$option_key]) && $options[$option_key] != $option_key) {
        $option_label = $options[$option_key] . ' (' . $option_key . ')';
      }

      $this->assign('premium', $productDAO->name);
      $this->assign('option', $option_label);
      $this->assign('fulfilled', $dao->fulfilled_date);
    }

    // Get Note
    $noteValue = CRM_Core_BAO_Note::getNote($values['id'], 'civicrm_contribution');
    $values['note'] = array_values($noteValue);

    // show billing address location details, if exists
    $values['billing_address'] = '';
    if (!empty($values['address_id'])) {
      $addressParams = ['id' => $values['address_id']];
      $addressDetails = CRM_Core_BAO_Address::getValues($addressParams, FALSE, 'id');
      $addressDetails = array_values($addressDetails);
      $values['billing_address'] = $addressDetails[0]['display'];
    }

    //assign soft credit record if exists.
    $SCRecords = CRM_Contribute_BAO_ContributionSoft::getSoftContribution($this->getContributionID(), TRUE);
    $this->assign('softContributions', empty($SCRecords['soft_credit']) ? NULL : $SCRecords['soft_credit']);
    // unset doesn't complain if array member missing
    unset($SCRecords['soft_credit']);
    foreach ($SCRecords as $name => $value) {
      $this->assign($name, $value);
    }

    $lineItems = [CRM_Price_BAO_LineItem::getLineItemsByContributionID(($id))];
    $this->assign('lineItem', $lineItems);
    $values['totalAmount'] = $values['total_amount'];
    $this->assign('displayLineItemFinancialType', TRUE);

    //do check for campaigns
    $values['campaign'] = '';
    $campaignId = $values['campaign_id'] ?? NULL;
    if ($campaignId) {
      $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns($campaignId);
      $values['campaign'] = $campaigns[$campaignId];
    }
    if ($contributionStatus === 'Refunded') {
      $this->assign('refund_trxn_id', CRM_Core_BAO_FinancialTrxn::getRefundTransactionTrxnID($id));
    }

    // assign values to the template
    $this->assignVariables($values, array_keys($values));
    $invoicing = \Civi::settings()->get('invoicing');
    $this->assign('invoicing', $invoicing);
    $this->assign('isDeferred', Civi::settings()->get('deferred_revenue_enabled'));
    if ($invoicing && isset($values['tax_amount'])) {
      $this->assign('totalTaxAmount', $values['tax_amount']);
    }

    // omitting contactImage from title for now since the summary overlay css doesn't work outside of our crm-container
    $displayName = CRM_Contact_BAO_Contact::displayName($values['contact_id']);
    $this->assign('displayName', $displayName);
    // Check if this is default domain contact CRM-10482
    if (CRM_Contact_BAO_Contact::checkDomainContact($values['contact_id'])) {
      $displayName .= ' (' . ts('default organization') . ')';
    }

    if (empty($values['is_template'])) {
      $this->setTitle(ts('View Contribution from') . ' ' . $displayName);
    }
    else {
      $this->setTitle(ts('View Template Contribution from') . ' ' . $displayName);
    }

    // add viewed contribution to recent items list
    $url = CRM_Utils_System::url('civicrm/contact/view/contribution',
      "action=view&reset=1&id={$values['id']}&cid={$values['contact_id']}&context=home"
    );

    $title = $displayName . ' - (' . CRM_Utils_Money::format($values['total_amount'], $values['currency']) . ' ' . ' - ' . $values['financial_type'] . ')';

    $recentOther = [];
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
    $statusOptionValueNames = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $contributionStatus = $statusOptionValueNames[$values['contribution_status_id']];
    $this->assign('addRecordPayment', in_array($contributionStatus, ['Partially paid', 'Pending refund', 'Pending']));
    $this->assignPaymentInfoBlock($id);

    $searchKey = NULL;
    if ($this->controller->_key) {
      $searchKey = $this->controller->_key;
    }

    if ($this->isHasAccess('update')) {
      $urlParams = "reset=1&id={$id}&cid={$values['contact_id']}&action=update&context={$context}";
      if (($context === 'fulltext' || $context === 'search') && $searchKey) {
        $urlParams = "reset=1&id={$id}&cid={$values['contact_id']}&action=update&context={$context}&key={$searchKey}";
      }
      $linkButtons[] = [
        'title' => ts('Edit'),
        'url' => 'civicrm/contact/view/contribution',
        'qs' => $urlParams,
        'icon' => 'fa-pencil',
        'accessKey' => 'e',
        'ref' => '',
        'name' => '',
        'extra' => '',
      ];
    }

    if ($this->isHasAccess('delete')) {
      $urlParams = "reset=1&id={$id}&cid={$values['contact_id']}&action=delete&context={$context}";
      if (($context === 'fulltext' || $context === 'search') && $searchKey) {
        $urlParams = "reset=1&id={$id}&cid={$values['contact_id']}&action=delete&context={$context}&key={$searchKey}";
      }
      $linkButtons[] = [
        'title' => ts('Delete'),
        'url' => 'civicrm/contact/view/contribution',
        'qs' => $urlParams,
        'icon' => 'fa-trash',
        'accessKey' => '',
        'ref' => '',
        'name' => '',
        'extra' => '',
      ];
    }

    $pdfUrlParams = "reset=1&id={$id}&cid={$values['contact_id']}";
    $emailUrlParams = "reset=1&id={$id}&cid={$values['contact_id']}&select=email";
    if (Civi::settings()->get('invoicing') && !$contribution['is_template']) {
      if (($values['contribution_status'] !== 'Refunded') && ($values['contribution_status'] !== 'Cancelled')) {
        $invoiceButtonText = ts('Download Invoice');
      }
      else {
        $invoiceButtonText = ts('Download Invoice and Credit Note');
      }
      $linkButtons[] = [
        'title' => $invoiceButtonText,
        'url' => 'civicrm/contribute/invoice',
        'qs' => $pdfUrlParams,
        'class' => 'no-popup',
        'icon' => 'fa-download',
      ];
      $linkButtons[] = [
        'title' => ts('Email Invoice'),
        'url' => 'civicrm/contribute/invoice/email',
        'qs' => $emailUrlParams,
        'icon' => 'fa-paper-plane',
      ];
    }
    $this->assign('linkButtons', $linkButtons ?? []);
    // These next 3 parameters are used to construct a url in PaymentInfo.tpl
    $this->assign('contactId', $values['contact_id']);
    $this->assign('componentId', $id);
    $this->assign('component', 'contribution');
    $this->assignPaymentInfoBlock($id);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addButtons([
      [
        'type' => 'cancel',
        'name' => ts('Done'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ],
    ]);
  }

  /**
   * Assign the values to build the payment info block.
   *
   * @todo - this is a bit too much copy & paste from AbstractEditPayment
   * (justifying on the basis it's 'pretty short' and in a different inheritance
   * tree. I feel like traits are probably the longer term answer).
   *
   * @param int $id
   *
   * @return string
   *   Block title.
   */
  protected function assignPaymentInfoBlock($id) {
    // component is used in getPaymentInfo primarily to retrieve the contribution id, we
    // already have that.
    $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($id, 'contribution', TRUE);
    $title = ts('View Payment');
    $this->assign('transaction', TRUE);
    // Used in paymentInfoBlock.tpl
    $this->assign('payments', $paymentInfo['transaction']);
    $this->assign('paymentLinks', $paymentInfo['payment_links']);
    return $title;
  }

  /**
   * @param string $action
   *
   * @return bool
   */
  private function isHasAccess(string $action): bool {
    try {
      return Contribution::checkAccess()
        ->setAction($action)
        ->addValue('id', $this->getContributionID())
        ->execute()->first()['access'];
    }
    catch (CRM_Core_Exception $e) {
      return FALSE;
    }
  }

  /**
   * Get the selected Contribution ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getContributionID(): ?int {
    $id = $this->get('id');
    if (!$id) {
      $id = CRM_Utils_Request::retrieve('id', 'Positive');
    }
    return (int) $id;
  }

  /**
   * Get id of contribution page being acted on.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getContributionPageID(): ?int {
    return $this->getContributionID() ? $this->getContributionValue('contribution_page_id') : NULL;
  }

}
