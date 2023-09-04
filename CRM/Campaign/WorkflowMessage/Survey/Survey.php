<?php

use Civi\Api4\WorkflowMessage;
use Civi\WorkflowMessage\GenericWorkflowMessage;
use Civi\WorkflowMessage\WorkflowMessageExample;

/**
 * Basic contribution example for contribution templates.
 *
 * @noinspection PhpUnused
 */
class CRM_Campaign_WorkflowMessage_Survey_Survey extends WorkflowMessageExample {

  /**
   * Get the examples this class is able to deliver.
   */
  public function getExamples(): iterable {
    $workflows = ['petition_sign', 'petition_confirmation_needed'];
    foreach ($workflows as $workflow) {
      yield [
        'name' => 'workflow/' . $workflow . '/basic_eur',
        'title' => ts('Save the whales'),
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
   * @throws \CRM_Core_Exception
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
   * @param \Civi\WorkflowMessage\GenericWorkflowMessage $messageTemplate
   *
   * @throws \CRM_Core_Exception
   */
  private function addExampleData(GenericWorkflowMessage $messageTemplate): void {
    $messageTemplate->setContact(\Civi\Test::example('entity/Contact/Barb'));
    $messageTemplate->setSurvey([
      'id' => 60,
      'title' => ts('Save the whales'),
    ]);
  }

}
