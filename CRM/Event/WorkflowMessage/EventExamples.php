<?php

use Civi\Api4\Event;
use Civi\Api4\PriceSetEntity;
use Civi\Api4\WorkflowMessage;
use Civi\WorkflowMessage\GenericWorkflowMessage;
use Civi\WorkflowMessage\WorkflowMessageExample;
use Civi\Api4\PriceField;
use Civi\Api4\PriceFieldValue;

/**
 * Basic contribution example for contribution templates.
 *
 * @noinspection PhpUnused
 */
class CRM_Event_WorkflowMessage_EventExamples extends WorkflowMessageExample {

  /**
   * IDs of events permitting multiple participants.
   *
   * We prefer these for more nuanced examples.
   *
   * @var array
   */
  private $multipleRegistrationEventIDs;

  /**
   * Get the examples this class is able to deliver.
   *
   * @throws \CRM_Core_Exception
   */
  public function getExamples(): iterable {
    $workflows = ['event_online_receipt', 'event_offline_receipt'];
    foreach ($workflows as $workflow) {
      $priceSets = $this->getPriceSets();
      foreach ($priceSets as $priceSet) {
        yield [
          'name' => 'workflow/' . $workflow . '/' . 'price_set_' . $priceSet['name'],
          'title' => ts('Completed Registration') . ($priceSet['is_multiple_registrations'] ? ' ' . ts('primary participant') : '') . ' : ' . $priceSet['title'],
          'tags' => ['preview'],
          'workflow' => $workflow,
          'is_show_line_items' => !$priceSet['is_quick_config'],
          'event_id' => $priceSet['event_id'],
          'is_multiple_registrations' => $priceSet['is_multiple_registrations'],
          'is_primary' => TRUE,
          'price_set_id' => $priceSet['id'],
        ];
        if ($priceSet['is_multiple_registrations']) {
          yield [
            'name' => 'workflow/' . $workflow . '/' . 'price_set_' . $priceSet['name'] . '/' . 'additional',
            'title' => ts('Completed Registration') . ' ' . ts('additional participant') . ' : ' . $priceSet['title'],
            'tags' => ['preview'],
            'workflow' => $workflow,
            'is_show_line_items' => !$priceSet['is_quick_config'],
            'event_id' => $priceSet['event_id'],
            'is_multiple_registrations' => $priceSet['is_multiple_registrations'],
            'is_primary' => FALSE,
            'price_set_id' => $priceSet['id'],
          ];
        }
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
   * @param \CRM_Event_WorkflowMessage_EventOnlineReceipt|\CRM_Event_WorkflowMessage_EventOfflineReceipt $messageTemplate
   * @param array $example
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function addExampleData(GenericWorkflowMessage $messageTemplate, array $example): void {
    $messageTemplate->setContact(\Civi\Test::example('entity/Contact/Barb'));
    $messageTemplate->setEventID($example['event_id']);
    $isPrimary = $example['is_primary'];
    $primaryParticipantID = 60;
    $otherParticipantID = 70;
    $isMultipleRegistrations = $example['is_multiple_registrations'];
    $participantContacts = [$primaryParticipantID => ['display_name' => 'Cindy Taylor']];
    if ($isMultipleRegistrations) {
      $participantContacts[$otherParticipantID] = ['display_name' => 'Melanie Mulder'];
    }

    $mockOrder = new CRM_Financial_BAO_Order();
    $mockOrder->setTemplateContributionID(50);
    $mockOrder->setPriceSetID($example['price_set_id']);

    foreach (PriceField::get(FALSE)->addWhere('price_set_id', '=', $mockOrder->getPriceSetID())->execute() as $index => $priceField) {
      $priceFieldValue = PriceFieldValue::get()->addWhere('price_field_id', '=', $priceField['id'])->execute();
      $this->setLineItem($mockOrder, $priceField, $priceFieldValue->first(), $index, $primaryParticipantID);
      if ($isMultipleRegistrations) {
        $this->setLineItem($mockOrder, $priceField, $priceFieldValue->last(), $index . '-' . $otherParticipantID, $otherParticipantID);
      }
    }
    $contribution['total_amount'] = $mockOrder->getTotalAmount();
    $contribution['tax_amount'] = $mockOrder->getTotalTaxAmount() ? round($mockOrder->getTotalTaxAmount(), 2) : 0;
    $contribution['tax_exclusive_amount'] = $contribution['total_amount'] - $contribution['tax_amount'];
    $messageTemplate->setContribution($contribution);
    $messageTemplate->setOrder($mockOrder);
    $messageTemplate->setParticipantContacts($participantContacts);
    $messageTemplate->setParticipant(['id' => $isPrimary ? $primaryParticipantID : $otherParticipantID, 'registered_by_id' => $isPrimary ? NULL : $primaryParticipantID, 'register_date' => date('Y-m-d')]);
  }

  /**
   * Get prices sets from the site - ideally one quick config & one not.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  private function getPriceSets(): ?array {
    $nonQuickConfigPriceSet = $this->getPriceSet(FALSE);
    $quickConfigPriceSet = $this->getPriceSet(TRUE);

    return array_filter([$nonQuickConfigPriceSet, $quickConfigPriceSet]);
  }

  /**
   * Get a price set.
   *
   * @param bool $isQuickConfig
   *
   * @return array|null
   * @throws \CRM_Core_Exception
   */
  private function getPriceSet(bool $isQuickConfig): ?array {
    // Try to find an event configured for multiple registrations
    $priceSetEntity = PriceSetEntity::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_event')
      ->addSelect('price_set_id.id', 'entity_id', 'price_set_id.is_quick_config', 'price_set_id.name', 'price_set_id.title')
      ->setLimit(1)
      ->addWhere('price_set_id.is_quick_config', '=', $isQuickConfig)
      ->addWhere('entity_id', 'IN', $this->getMultipleRegistrationEventIDs())
      ->execute()->first();
    if (empty($priceSetEntity)) {
      // Try again without limiting to multiple registrations.
      $priceSetEntity = PriceSetEntity::get(FALSE)
        ->addWhere('entity_table', '=', 'civicrm_event')
        ->addSelect('price_set_id.id', 'entity_id', 'price_set_id.is_quick_config', 'price_set_id.name', 'price_set_id.title')
        ->setLimit(1)
        ->addWhere('price_set_id.is_quick_config', '=', $isQuickConfig)
        ->execute()->first();
    }

    return empty($priceSetEntity) ? NULL : [
      'id' => $priceSetEntity['price_set_id.id'],
      'name' => $priceSetEntity['price_set_id.name'],
      'title' => $priceSetEntity['price_set_id.title'],
      'event_id' => $priceSetEntity['entity_id'],
      'is_quick_config' => $priceSetEntity['price_set_id.is_quick_config'],
      'is_multiple_registrations' => in_array($priceSetEntity['entity_id'], $this->getMultipleRegistrationEventIDs(), TRUE),
    ];
  }

  /**
   * @param \CRM_Financial_BAO_Order $mockOrder
   * @param $priceField
   * @param array|null $priceFieldValue
   * @param int $participantID
   * @param $index
   *
   * @throws \CRM_Core_Exception
   */
  private function setLineItem(CRM_Financial_BAO_Order $mockOrder, $priceField, ?array $priceFieldValue, $index, $participantID): void {
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
      'entity_table' => 'civicrm_participant',
      'entity_id' => $participantID,
    ], $index);
  }

  /**
   * Get the ids of (up to 25) recent multiple registration events.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function getMultipleRegistrationEventIDs(): array {
    if ($this->multipleRegistrationEventIDs === NULL) {
      $this->multipleRegistrationEventIDs = array_keys((array) Event::get(FALSE)
        ->addWhere('is_multiple_registrations', '=', TRUE)
        ->addWhere('max_additional_participants', '>', 0)
        ->addSelect('id')
        ->addOrderBy('start_date', 'DESC')
        ->setLimit(25)
        ->execute()
        ->indexBy('id'));

    }
    return $this->multipleRegistrationEventIDs;
  }

}
