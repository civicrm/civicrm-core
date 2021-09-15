<?php

class CRM_Case_WorkflowMessage_CaseActivity_CaseAdhocExample extends \Civi\WorkflowMessage\WorkflowMessageExample {

  /**
   * @inheritDoc
   */
  public function getExamples(): iterable {
    if (!class_exists($this->wfClass)) {
      return []; /* CaseActivity WfMsg is temporarily in tests/phpunit, so it's not reliably loadable.  Temp work-around. */
    }
    yield [
      'name' => "workflow/{$this->wfName}/{$this->exName}",
      'title' => ts('Case Activity (Adhoc-style example)'),
      'tags' => [],
    ];
  }

  /**
   * @inheritDoc
   */
  public function build(array &$example): void {
    $alex = \Civi\Test::example('workflow/generic/Alex');
    $contact = $this->extend($alex['modelProps']['contact'], [
      'role' => 'myrole',
    ]);
    $example['data'] = [
      'workflow' => $this->wfName,
      'tokenContext' => [
        'contact' => $contact,
      ],
      'tplParams' => [
        'contact' => $contact,
        'isCaseActivity' => 1,
        'client_id' => 101,
        'activityTypeName' => 'Follow up',
        'activitySubject' => 'Test 123',
        'idHash' => 'abcdefg',
        'activity' => [
          'fields' => [
            [
              'label' => 'Case ID',
              'type' => 'String',
              'value' => '1234',
            ],
          ],
        ],
      ],
    ];
  }

}
