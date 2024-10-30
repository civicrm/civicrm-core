<?php

use Civi\Api4\UFField;
use Civi\Api4\UFGroup;
use Civi\Api4\WorkflowMessage;
use Civi\Test;
use Civi\WorkflowMessage\WorkflowMessageExample;

/**
 * Basic profile example.
 *
 * @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 */
class CRM_Core_WorkflowMessage_Profile_Profile extends WorkflowMessageExample {

  /**
   * Get the examples this class is able to deliver.
   */
  public function getExamples(): iterable {
    $workflows = ['uf_notify'];
    foreach ($workflows as $workflow) {
      yield [
        'name' => 'workflow/' . $workflow . '/general',
        'title' => ts('Profile Notification'),
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
    $this->addExampleData($messageTemplate, $example);
    $example['data'] = $this->toArray($messageTemplate);
  }

  /**
   * Add relevant example data.
   *
   * @param \CRM_Core_WorkflowMessage_UFNotify $messageTemplate
   * @param array $example
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function addExampleData(\CRM_Core_WorkflowMessage_UFNotify $messageTemplate, $example): void {
    $contact = Test::example('entity/Contact/Barb');
    $messageTemplate->setContact($contact);
    $profile = UFGroup::get(FALSE)->setLimit(1)->execute()->first();
    $fields = UFField::get(FALSE)->addWhere('id', '=', $profile['id'])->execute();
    $values = [];
    foreach ($fields as $field) {
      if (isset($contact[$field['field_name']])) {
        $values[$field['label']] = $contact[$field['field_name']];
      }
      else {
        $values[$field['label']] = ts('User entered field');
      }
    }
    $messageTemplate->setProfileID($profile['id']);
    $messageTemplate->setProfileFields($values);
  }

}
