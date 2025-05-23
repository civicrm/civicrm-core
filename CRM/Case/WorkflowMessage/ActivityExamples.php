<?php

use Civi\Api4\WorkflowMessage;
use Civi\WorkflowMessage\WorkflowMessageExample;

/**
 * Basic contribution example for contribution templates.
 *
 * @noinspection PhpUnused
 */
class CRM_Case_WorkflowMessage_ActivityExamples extends WorkflowMessageExample {

  /**
   * Get the examples this class is able to deliver.
   */
  public function getExamples(): iterable {
    $workflows = ['case_activity'];
    foreach ($workflows as $workflow) {
      foreach ([TRUE, FALSE] as $caseID) {
        yield [
          'name' => 'workflow/' . $workflow . '/activity' . (bool) $caseID,
          'title' => $caseID ? ts('Case Activity') : ts('Activity'),
          'tags' => ['preview'],
          'workflow' => $workflow,
          'case_id' => $caseID,
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
   * @param \CRM_Case_WorkflowMessage_CaseActivity $messageTemplate
   * @param array $example
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function addExampleData(CRM_Case_WorkflowMessage_CaseActivity $messageTemplate, array $example): void {
    $messageTemplate->setContact(\Civi\Test::example('entity/Contact/Barb'));
    $messageTemplate->setActivity([
      'activity_type_id:label' => ts('Follow up'),
      // Ideally something better but let's not add more strings until we hae a good one.
      'subject' => ('Follow up'),
    ]);
    if (!empty($example['case_id'])) {
      $messageTemplate->setCase(['subject' => ts('Sample case')]);
    }
  }

}
