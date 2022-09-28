<?php

use Civi\Api4\PriceField;
use Civi\Api4\PriceFieldValue;
use Civi\Api4\PriceSet;
use Civi\Api4\WorkflowMessage;
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
    $workflows = ['contribution_online_receipt', 'contribution_offline_receipt', 'contribution_invoice_receipt'];
    foreach ($workflows as $workflow) {
      yield [
        'name' => 'workflow/' . $workflow . '/basic_eur',
        'title' => ts('Completed Contribution') . ' : ' . 'EUR',
        'tags' => $workflow === 'contribution_offline_receipt' ? ['phpunit', 'preview'] : ['preview'],
        'workflow' => $workflow,
      ];
      yield [
        'name' => 'workflow/' . $workflow . '/' . 'basic_cad',
        'title' => ts('Completed Contribution') . ' : ' . 'CAD',
        'tags' => ['preview'],
        'workflow' => $workflow,
        'contribution_params' => ['currency' => 'CAD'],
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
    $messageTemplate->setContact(\Civi\Test::example('entity/Contact/Barb'));
    $contribution = \Civi\Test::example('entity/Contribution/Euro5990/completed');
    if (isset($example['contribution_params'])) {
      $contribution = array_merge($contribution, $example['contribution_params']);
    }
    $contribution['contribution_status_id:name'] = \CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contribution['contribution_status_id']);
    $contribution['contribution_status_id:label'] = \CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contribution['contribution_status_id']);

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
    foreach (PriceField::get()->addWhere('price_set_id', '=', $mockOrder->getPriceSetID())->execute() as $index => $priceField) {
      $priceFieldValue = PriceFieldValue::get()->addWhere('price_field_id', '=', $priceField['id'])->execute()->first();
      if (empty($example['is_show_line_items'])) {
        $priceFieldValue['amount'] = $contribution['total_amount'];
        $priceFieldValue['financial_type_id'] = $contribution['financial_type_id'];
      }
      $this->setLineItem($mockOrder, $priceField, $priceFieldValue, $index);
    }

    $contribution['total_amount'] = $mockOrder->getTotalAmount();
    $contribution['tax_amount'] = $mockOrder->getTotalTaxAmount() ? round($mockOrder->getTotalTaxAmount(), 2) : 0;
    $messageTemplate->setContribution($contribution);
    $messageTemplate->setOrder($mockOrder);
    $messageTemplate->setContribution($contribution);
  }

  /**
   * Get a non-quick-config price set.
   *
   * @return array|null
   * @throws \CRM_Core_Exception
   */
  private function getNonQuickConfigPriceSet(): ?array {
    // Permission check defaults to true - likely implicitly OK but may need to be false.
    return PriceSet::get()
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
