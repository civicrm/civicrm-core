<?php

namespace Civi\Test\ExampleData\Contact;

use Civi\Test\EntityExample;

class Anthony extends EntityExample {

  /**
   * Get examples that this class can provide in an iterable format.
   */
  public function getExamples(): iterable {
    yield [
      'name' => "entity/{$this->entityName}/" . $this->getExampleName(),
    ];
  }

  /**
   * Add available examples to the `$example` input.
   *
   * @param array $example
   */
  public function build(array &$example): void {
    $example['data'] = [
      'contact_type' => 'Individual',
      'first_name' => 'Anthony',
      'last_name' => 'Anderson',
      'middle_name' => 'J.',
      'prefix_id:label' => 'Dr.',
      'suffix_id:label' => 'III',
      'primary_email.email' => 'anthony.anderson@civicrm.org',
    ];
  }

}
