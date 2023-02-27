<?php

class CRM_Contribute_WorkflowMessage_RecurringEdit_BarbPending extends \Civi\WorkflowMessage\WorkflowMessageExample {

  public function getExamples(): iterable {
    yield [
      'name' => "workflow/{$this->wfName}/{$this->exName}",
      // This title is not very clear. When we have some more examples to compare against, feel free to change/clarify.
      'title' => ts('Recurring Edit: Barbara, Pending'),
      'tags' => ['preview'],
    ];
  }

  public function build(array &$example): void {
    $msg = (new CRM_Contribute_WorkflowMessage_RecurringEdit())
      ->setReceiptFromEmail('info@example.com')
      ->setContact(\Civi\Test::example('entity/Contact/Barb'))
      ->setContributionRecur(\Civi\Test::example('entity/ContributionRecur/Euro5990/pending'));
    $example['data'] = $this->toArray($msg);

    $example['asserts'] = [
      'default' => [
        ['for' => 'subject', 'regex' => '/Recurring Contribution Update.*Barb/'],
        ['for' => 'text', 'regex' => '/Recurring contribution is for €5,990.99, every 2 year.s. for 24 installments/'],
      ],
    ];
  }

}
