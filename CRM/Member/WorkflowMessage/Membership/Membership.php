<?php

use Civi\Api4\MembershipType;
use Civi\Api4\PriceField;
use Civi\Api4\PriceFieldValue;
use Civi\Api4\PriceSet;
use Civi\Api4\WorkflowMessage;
use Civi\Test;
use Civi\WorkflowMessage\GenericWorkflowMessage;
use Civi\WorkflowMessage\WorkflowMessageExample;

/**
 * Examples for membership templates.
 *
 * @noinspection PhpUnused
 */
class CRM_Member_WorkflowMessage_Membership_Membership extends WorkflowMessageExample {

  /**
   * Get the examples this class is able to deliver.
   *
   * @throws \CRM_Core_Exception
   */
  public function getExamples(): iterable {
    if (!CRM_Core_Component::isEnabled('CiviMember')) {
      return;
    }
    $membershipType = MembershipType::get(FALSE)->execute()->first();
    if (empty($membershipType)) {
      return;
    }
    $workflows = ['membership_online_receipt', 'membership_offline_receipt'];
    $defaultCurrency = \Civi::settings()->get('defaultCurrency');
    $priceSets = $this->getPriceSet();

    foreach ($workflows as $workflow) {
      foreach ($priceSets as $priceSet) {
        yield [
          'name' => 'workflow/' . $workflow . '/' . strtolower($membershipType['name']) . '_' . strtolower($priceSet['name']) . '_' . strtolower($defaultCurrency),
          'title' => $priceSet['title'] . ' - ' . $membershipType['name'] . ' : ' . $defaultCurrency,
          'tags' => ['preview'],
          'workflow' => $workflow,
          'membership_type' => $membershipType,
          'currency' => $defaultCurrency,
          'price_set_id' => $priceSet['id'],
          'is_show_line_items' => !$priceSets['is_quick_config'],
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
  }

  /**
   * Add relevant example data.
   *
   * @param \CRM_Member_WorkflowMessage_MembershipOfflineReceipt|\CRM_Member_WorkflowMessage_MembershipOnlineReceipt $messageTemplate
   * @param array $example
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function addExampleData(GenericWorkflowMessage $messageTemplate, array $example): void {
    $messageTemplate->setContact(Test::example('entity/Contact/Barb'));

    $membership = [
      'membership_type_id' => $example['membership_type']['id'],
      'membership_type_id:name' => $example['membership_type']['name'],
      'membership_type_id:label' => $example['membership_type']['name'],
      'status_id:name' => 'Current',
      'start_date' => date('Y-m-05', strtotime('-1 month')),
      // Ideally we would leave blank for lifetime & maybe calculate more meaningfully.
      'end_date' => date('Y-m-05', strtotime('+ 11 months')),
    ];
    $messageTemplate->setMembership($membership);

    $contribution = [
      'id' => 50,
      'contact_id' => 100,
      'financial_type_id' => $example['membership_type']['financial_type_id'],
      'receive_date' => '2021-07-23 15:39:20',
      'total_amount' => $example['membership_type']['minimum_amount'],
      'fee_amount' => .99,
      'net_amount' => $example['membership_type']['minimum_amount'] - .99,
      'currency' => $example['currency'],
      'trxn_id' => 123,
      'invoice_id' => 'I-123',
      'contribution_status_id:name' => 'Completed',
      'contribution_status_id' => \CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
    ];
    $contribution['contribution_status_id:label'] = \CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contribution['contribution_status_id']);

    if (isset($example['contribution_params'])) {
      $contribution = array_merge($contribution, $example['contribution_params']);
    }

    $mockOrder = new CRM_Financial_BAO_Order();
    $mockOrder->setTemplateContributionID(50);

    if (empty($example['is_show_line_items'])) {
      $mockOrder->setPriceSetToDefault('membership');
      $mockOrder->setOverrideTotalAmount($example['membership_type']['minimum_fee']);
      $mockOrder->setDefaultFinancialTypeID($example['membership_type']['financial_type_id']);
    }
    else {
      $priceSet = $this->getPriceSet()[$example['price_set_id']];
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
      $this->setLineItem($mockOrder, $priceField, $priceFieldValue, $index, $membership);
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
  private function getPriceSet(): ?array {
    return (array) PriceSet::get(FALSE)
      ->addWhere('extends', '=', CRM_Core_Component::getComponentID('CiviMember'))
      ->addOrderBy('is_quick_config', 'DESC')
      ->execute()->indexBy('id');
  }

  /**
   * @param \CRM_Financial_BAO_Order $mockOrder
   * @param $priceField
   * @param array|null $priceFieldValue
   * @param $index
   * @param $membership
   *
   * @throws \CRM_Core_Exception
   */
  private function setLineItem(CRM_Financial_BAO_Order $mockOrder, $priceField, ?array $priceFieldValue, $index, $membership): void {
    $lineItem = [
      'price_field_id' => $priceField['id'],
      'price_field_id.label' => $priceField['label'],
      'price_field_value_id' => $priceFieldValue['id'],
      'qty' => $priceField['is_enter_qty'] ? 2 : 1,
      'unit_price' => $priceFieldValue['amount'],
      'line_total' => $priceField['is_enter_qty'] ? ($priceFieldValue['amount'] * 2) : $priceFieldValue['amount'],
      'label' => $priceFieldValue['label'],
      'financial_type_id' => $priceFieldValue['financial_type_id'],
      'non_deductible_amount' => $priceFieldValue['non_deductible_amount'],
    ];
    if (!empty($priceFieldValue['membership_type_id'])) {
      $lineItem['membership'] = ['start_date' => $membership['start_date'], 'end_date' => $membership['end_date']];
    }
    $mockOrder->setLineItem($lineItem, $index);
  }

}
