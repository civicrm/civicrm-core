<?php

use Civi\Api4\PriceSetEntity;
use Civi\Api4\WorkflowMessage;
use Civi\WorkflowMessage\GenericWorkflowMessage;
use Civi\WorkflowMessage\WorkflowMessageExample;

/**
 * Basic contribution example for contribution templates.
 *
 * @noinspection PhpUnused
 */
class CRM_Event_WorkflowMessage_EventExamples extends WorkflowMessageExample {

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
          'title' => ts('Completed Registration') . ' : ' . $priceSet['title'],
          'tags' => ['preview'],
          'workflow' => $workflow,
          'is_show_line_items' => !$priceSet['is_quick_config'],
          'event_id' => $priceSet['event_id'],
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
   * @param \CRM_Event_WorkflowMessage_EventOnlineReceipt|\CRM_Event_WorkflowMessage_EventOfflineReceipt $messageTemplate
   * @param array $example
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function addExampleData(GenericWorkflowMessage $messageTemplate, $example): void {
    $messageTemplate->setContact(\Civi\Test::example('entity/Contact/Barb'));
    $messageTemplate->setEventID($example['event_id']);
  }

  /**
   * Get prices sets from the site - ideally one quick config & one not.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  private function getPriceSets(): ?array {
    // Permission check defaults to true - likely implicitly OK but may need to be false.
    $quickConfigPriceSet = $this->getPriceSet(TRUE);
    $nonQuickConfigPriceSet = $this->getPriceSet(FALSE);

    return array_filter([$quickConfigPriceSet, $nonQuickConfigPriceSet]);
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
    $priceSetEntity = PriceSetEntity::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_event')
      ->addSelect('price_set_id.id', 'entity_id', 'price_set_id.is_quick_config', 'price_set_id.name', 'price_set_id.title')
      ->setLimit(1)
      ->addWhere('price_set_id.is_quick_config', '=', $isQuickConfig)
      ->execute()->first();

    return empty($priceSetEntity) ? NULL : [
      'id' => $priceSetEntity['price_set_id'],
      'name' => $priceSetEntity['price_set_id.name'],
      'title' => $priceSetEntity['price_set_id.title'],
      'event_id' => $priceSetEntity['entity_id'],
      'is_quick_config' => $priceSetEntity['price_set_id.is_quick_config'],
    ];
  }

}
