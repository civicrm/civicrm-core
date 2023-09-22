<?php

namespace Civi\WorkflowMessage\GenericWorkflowMessage;

class Alex extends \Civi\WorkflowMessage\WorkflowMessageExample {

  /**
   * @inheritDoc
   */
  public function getExamples(): iterable {
    yield [
      'name' => "workflow/{$this->wfName}/{$this->exName}",
      'tags' => [],
    ];
  }

  /**
   * @inheritDoc
   */
  public function build(array &$example): void {
    $example['data'] = [
      'modelProps' => [
        'contact' => \Civi\Test::example('entity/Contact/Alex'),
      ],
    ];
  }

}
