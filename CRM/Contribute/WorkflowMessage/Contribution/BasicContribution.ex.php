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
        'name' => 'workflow/' . $workflow . '/' . $this->getExampleName(),
        'title' => ts('Completed Contribution'),
        'tags' => ['preview'],
        'workflow' => $workflow,
      ];
    }
  }

  /**
   * Build an example to use when rendering the workflow.
   *
   * @param array $example
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function build(array &$example): void {
    $workFlow = WorkflowMessage::get(TRUE)->addWhere('name', '=', $example['workflow'])->execute()->first();
    $this->setWorkflowName($workFlow['name']);
    $messageTemplate = new $workFlow['class']();
    $this->addExampleData($messageTemplate);
    $example['data'] = $this->toArray($messageTemplate);
  }

  /**
   * Add relevant example data.
   *
   * @param \CRM_Contribute_WorkflowMessage_ContributionOfflineReceipt|\CRM_Contribute_WorkflowMessage_ContributionOnlineReceipt|\CRM_Contribute_WorkflowMessage_ContributionInvoiceReceipt $messageTemplate
   *
   * @throws \CRM_Core_Exception
   */
  private function addExampleData(GenericWorkflowMessage $messageTemplate): void {
    $messageTemplate->setContact(\Civi\Test::example('entity/Contact/Barb'));
    $messageTemplate->setContribution(\Civi\Test::example('entity/Contribution/Euro5990/completed'));
  }

}
