<?php

use Civi\Api4\PriceField;
use Civi\Api4\PriceFieldValue;
use Civi\Api4\PriceSet;
use Civi\Api4\Product;
use Civi\Api4\WorkflowMessage;
use Civi\Test;
use Civi\WorkflowMessage\GenericWorkflowMessage;
use Civi\WorkflowMessage\WorkflowMessageExample;

/**
 * Basic contribution example for contribution templates.
 *
 * @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 */
class CRM_Contribute_WorkflowMessage_Contribution_BasicContribution extends WorkflowMessageExample {

  /**
   * Get the examples this class is able to deliver.
   */
  public function getExamples(): iterable {
    $workflows = ['contribution_online_receipt', 'contribution_offline_receipt', 'contribution_invoice_receipt', 'payment_or_refund_notification'];
    $defaultCurrency = \Civi::settings()->get('defaultCurrency');
    $currencies = [$defaultCurrency => $defaultCurrency, 'EUR' => 'EUR', 'CAD' => 'CAD'];
    foreach ($workflows as $workflow) {
      foreach ($currencies as $currency) {
        yield [
          'name' => 'workflow/' . $workflow . '/basic_' . $currency,
          'title' => ts('Completed Contribution') . ' : ' . $currency,
          'tags' => $workflow === 'contribution_offline_receipt' ? ['phpunit', 'preview'] : ['preview'],
          'workflow' => $workflow,
          // If there are no non-quick-config we have no show line items example.
          'is_show_line_items' => $this->getNonQuickConfigPriceSet() ? TRUE : FALSE,
        ];
      }
      yield [
        'name' => 'workflow/' . $workflow . '/' . 'partially paid' . $currency,
        'title' => ts('Partially Paid Contribution') . ' : ' . $currency,
        'tags' => ['preview'],
        'workflow' => $workflow,
        'is_show_line_items' => $this->getNonQuickConfigPriceSet() ? TRUE : FALSE,
        'contribution_params' => ['contribution_status_id' => \CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Partially paid')],
      ];
      $priceSet = $this->getNonQuickConfigPriceSet();
      if ($priceSet) {
        yield [
          'name' => 'workflow/' . $workflow . '/' . 'price_set_' . $priceSet['name'],
          'title' => ts('Completed Contribution') . ' : ' . $priceSet['title'],
          'tags' => ['preview'],
          'workflow' => $workflow,
          'is_show_line_items' => TRUE,
        ];
        yield [
          'name' => 'workflow/' . $workflow . '/' . 'refunded_price_set_' . $priceSet['name'],
          'title' => ts('Refunded Contribution') . ' : ' . $priceSet['title'],
          'tags' => ['preview'],
          'workflow' => $workflow,
          'is_show_line_items' => TRUE,
          'contribution_params' => ['contribution_status_id' => \CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Refunded')],
        ];
      }
    }
  }

  /**
   * Build an example to use when rendering the workflow.
   *
   * @param array $example
   *
   * @throws \CRM_Core_Exception
   */
  public function build(array &$example): void {
    $workFlow = WorkflowMessage::get(TRUE)->addWhere('name', '=', $example['workflow'])->execute()->first();
    $this->setWorkflowName($workFlow['name']);
    $messageTemplate = new $workFlow['class']();
    $this->addExampleData($messageTemplate, $example);
    $example['data'] = $this->toArray($messageTemplate);
    switch ($example['workflow']) {
      case 'contribution_online_receipt':
        $example['asserts'] = [
          'default' => [
            ['for' => 'subject', 'regex' => '/Receipt - FIXME Contribution Title - Barbara Johnson/'],
            ['for' => 'html', 'regex' => '/table id="crm-event_receipt"/'],
            ['for' => 'html', 'regex' => '/Dear Barb,/'],
          ],
        ];
        break;

      // This does not yet get hit - placeholder
      case 'contribution_offline_receipt':
        $example['asserts'] = [
          'default' => [
            ['for' => 'subject', 'regex' => '/Contribution Receipt - Barbara Johnson/'],
            ['for' => 'text', 'regex' => '/Transaction ID: 123/'],
          ],
        ];
        break;

      // This does not yet get hit - placeholder
      case 'contribution_invoice_receipt':
        $example['asserts'] = [
          'default' => [
            ['for' => 'subject', 'regex' => '/Invoice - Barbara Johnso/'],
            ['for' => 'html', 'regex' => '/Amount Paid/'],
          ],
        ];
        break;
    }
  }

  /**
   * Add relevant example data.
   *
   * @param \CRM_Contribute_WorkflowMessage_ContributionOfflineReceipt|\CRM_Contribute_WorkflowMessage_ContributionOnlineReceipt|\CRM_Contribute_WorkflowMessage_ContributionInvoiceReceipt $messageTemplate
   * @param array $example
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function addExampleData(GenericWorkflowMessage $messageTemplate, $example): void {
    $messageTemplate->setContact(Test::example('entity/Contact/Barb'));
    $contribution = Test::example('entity/Contribution/Euro5990/completed');
    $example['currency'] ??= \Civi::settings()->get('defaultCurrency');
    if (isset($example['contribution_params'])) {
      $contribution = array_merge($contribution, $example['contribution_params']);
    }
    $contribution['contribution_status_id:name'] = \CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contribution['contribution_status_id']);
    $contribution['contribution_status_id:label'] = \CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contribution['contribution_status_id']);
    if ($contribution['contribution_status_id:name'] === 'Partially paid') {
      $contribution['paid_amount'] = round($contribution['total_amount'] / 2, 2);
    }
    elseif ($contribution['contribution_status_id:name'] === 'Pending' || $contribution['contribution_status_id:name'] === 'Refunded') {
      $contribution['paid_amount'] = 0;
    }
    else {
      $contribution['paid_amount'] = $contribution['total_amount'];
    }
    $contribution['balance_amount'] = $contribution['total_amount'] - $contribution['paid_amount'];
    if ($contribution['contribution_status_id:name'] === 'Refunded') {
      $contribution['balance_amount'] = 0;
    }
    $contribution['net_amount'] = $contribution['total_amount'] - $contribution['fee_amount'];
    $mockOrder = new CRM_Financial_BAO_Order();
    $mockOrder->setTemplateContributionID(50);

    if (empty($example['is_show_line_items'])) {
      $mockOrder->setPriceSetToDefault('contribution');
      $mockOrder->setOverrideTotalAmount($contribution['total_amount']);
      $mockOrder->setDefaultFinancialTypeID($contribution['financial_type_id']);
    }
    else {
      $priceSet = $this->getNonQuickConfigPriceSet();
      $mockOrder->setPriceSetID($priceSet['id']);
      if ($priceSet['financial_type_id']) {
        $mockOrder->setDefaultFinancialTypeID($priceSet['financial_type_id']);
      }
    }
    foreach (PriceField::get(FALSE)->addWhere('price_set_id', '=', $mockOrder->getPriceSetID())->execute() as $index => $priceField) {
      $priceFieldValue = PriceFieldValue::get()->addWhere('price_field_id', '=', $priceField['id'])->execute()->first();
      if (empty($example['is_show_line_items'])) {
        $priceFieldValue['amount'] = $contribution['total_amount'];
        $priceFieldValue['financial_type_id'] = $contribution['financial_type_id'];
      }
      $this->setLineItem($mockOrder, $priceField, $priceFieldValue, $index);
    }

    $contribution['address_id.name'] = 'Barbara Johnson';
    $contribution['address_id.display'] = '790L Lincoln St S
Baltimore, New York 10545
United States';
    $contribution['total_amount'] = $mockOrder->getTotalAmount();
    $contribution['tax_amount'] = $mockOrder->getTotalTaxAmount() ? round($mockOrder->getTotalTaxAmount(), 2) : 0;
    $messageTemplate->setContribution($contribution);
    $messageTemplate->setOrder($mockOrder);
    $messageTemplate->setContribution($contribution);
    $financialTrxn = [
      'trxn_date' => date('Y-m-d H:i:s'),
      'total_amount' => $contribution['contribution_status_id:name'] === 'Refunded' ? -$contribution['total_amount'] : $contribution['paid_amount'],
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Financial_BAO_FinancialTrxn', 'payment_instrument_id', 'Credit Card'),
      'card_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Financial_BAO_FinancialTrxn', 'card_type_id', 'Visa'),
      'pan_truncation' => 5679,
    ];
    $financialTrxn['payment_instrument_id:label'] = \CRM_Core_PseudoConstant::getLabel('CRM_Financial_BAO_FinancialTrxn', 'payment_instrument_id', $financialTrxn['payment_instrument_id']);
    $financialTrxn['payment_instrument_id:name'] = \CRM_Core_PseudoConstant::getName('CRM_Financial_BAO_FinancialTrxn', 'payment_instrument_id', $financialTrxn['payment_instrument_id']);
    $financialTrxn['card_type_id:label'] = \CRM_Core_PseudoConstant::getLabel('CRM_Financial_BAO_FinancialTrxn', 'card_type_id', $financialTrxn['card_type_id']);
    $financialTrxn['card_type_id:name'] = \CRM_Core_PseudoConstant::getName('CRM_Financial_BAO_FinancialTrxn', 'card_type_id', $financialTrxn['card_type_id']);

    $messageTemplate->setFinancialTrxn($financialTrxn);

    $product = Product::get(FALSE)->setLimit(1)->execute()->first();
    if ($product) {
      $option = '';
      if (is_array($product['options'])) {
        $option = reset($product['options']);
      }
      $messageTemplate->setContributionProduct([
        'product_id.name' => $product['name'],
        'product_id.sku' => $product['sku'],
        'product_option:label' => $option,
        'id' => 1,
        'fulfilled_date' => 'yesterday',
      ]);
    }
  }

  /**
   * Get a non-quick-config price set.
   *
   * @return array|null
   * @throws \CRM_Core_Exception
   */
  private function getNonQuickConfigPriceSet(): ?array {
    return PriceSet::get(FALSE)
      ->addWhere('is_quick_config', '=', FALSE)
      ->execute()
      ->first();
  }

  /**
   * @param \CRM_Financial_BAO_Order $mockOrder
   * @param $priceField
   * @param array|null $priceFieldValue
   * @param $index
   *
   * @throws \CRM_Core_Exception
   */
  private function setLineItem(CRM_Financial_BAO_Order $mockOrder, $priceField, ?array $priceFieldValue, $index): void {
    $mockOrder->setLineItem([
      'price_field_id' => $priceField['id'],
      'price_field_id.label' => $priceField['label'],
      'price_field_value_id' => $priceFieldValue['id'],
      'qty' => $priceField['is_enter_qty'] ? 2 : 1,
      'unit_price' => $priceFieldValue['amount'],
      'line_total' => $priceField['is_enter_qty'] ? ($priceFieldValue['amount'] * 2) : $priceFieldValue['amount'],
      'label' => $priceFieldValue['label'],
      'financial_type_id' => $priceFieldValue['financial_type_id'],
      'non_deductible_amount' => $priceFieldValue['non_deductible_amount'],
    ], $index);
  }

}
