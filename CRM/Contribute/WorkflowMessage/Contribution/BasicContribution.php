<?php

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
        'tags' => ['preview'],
        'workflow' => $workflow,
      ];
      yield [
        'name' => 'workflow/' . $workflow . '/' . 'basic_cad',
        'title' => ts('Completed Contribution') . ' : ' . 'CAD',
        'tags' => ['preview'],
        'workflow' => 'contribution_offline_receipt',
        'currency' => 'CAD',
      ];
    }
  }

  /**
   * Build an example to use when rendering the workflow.
   *
   * @param array $example
   *
   * @throws \API_Exception
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
   * @param \CRM_Contribute_WorkflowMessage_ContributionOfflineReceipt|\CRM_Contribute_WorkflowMessage_ContributionOnlineReceipt|\CRM_Contribute_WorkflowMessage_ContributionInvoiceReceipt $messageTemplate
   * @param array $example
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function addExampleData(GenericWorkflowMessage $messageTemplate, $example): void {
    $messageTemplate->setContact(\Civi\Test::example('entity/Contact/Barb'));
    $contribution = \Civi\Test::example('entity/Contribution/Euro5990/completed');
    if (isset($example['currency'])) {
      $contribution['currency'] = $example['currency'];
    }
    $mockOrder = new CRM_Financial_BAO_Order();
    $mockOrder->setTemplateContributionID(50);
    $mockOrder->setPriceSetToDefault('contribution');
    $messageTemplate->setOrder($mockOrder);
    $messageTemplate->setContribution($contribution);
  }

}
