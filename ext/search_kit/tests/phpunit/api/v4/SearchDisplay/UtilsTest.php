<?php
namespace api\v4\SearchDisplay;

use Civi\Api4\Contact;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class UtilsTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function tokenExamples(): array {
    return [
      [
        '',
        [],
      ],
      [
        'Hello :]',
        [],
      ],
      [
        '[whatever:',
        [],
      ],
      [
        '#[id] [participant_id.role_id:label] [id]',
        ['id', 'participant_id.role_id:label'],
      ],
      [
        '[contact_id.display_name] - [event_id.title]',
        ['contact_id.display_name', 'event_id.title'],
      ],
    ];
  }

  /**
   * @dataProvider tokenExamples
   */
  public function testGetTokens($input, $expected) {
    $method = new \ReflectionMethod('\Civi\Api4\Generic\AutocompleteAction', 'getTokens');
    $method->setAccessible(TRUE);

    $action = Contact::autocomplete();
    $this->assertEquals($expected, $method->invoke($action, $input));
  }

}
