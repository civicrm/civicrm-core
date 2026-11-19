<?php

/**
 * @group headless
 * @group locale
 */
class CRM_Core_I18n_TsTest extends CiviUnitTestCase {

  use CRM_Core_I18n_CatalogTestTrait;

  protected function tearDown(): void {
    $this->resetCatalog();
    parent::tearDown();
  }

  /**
   * Data provider for testTranslateWithArgs
   *
   * @return array
   */
  public function translateDataProvider(): array {
    return [
      // Basic numbered arguments
      'simple_numbered_arg' => [
        'input' => 'Hello %1',
        'args' => [1 => 'World'],
        'expected' => 'Hello World',
      ],
      'multiple_numbered_args' => [
        'input' => '%3 has %2 items',
        'args' => [3 => 'John', 2 => '5'],
        'expected' => 'John has 5 items',
      ],
      'numbered_args_out_of_order' => [
        'input' => '%1 belongs to %2',
        'args' => [2 => 'Alice', 1 => 'Book'],
        'expected' => 'Book belongs to Alice',
      ],
      // Count argument
      'count_arg_single' => [
        'input' => 'Found %count result',
        'args' => ['count' => 1, 'plural' => 'Found %count results'],
        'expected' => 'Found 1 result',
      ],
      'count_arg_multiple' => [
        'input' => 'Found 1 result',
        'args' => ['count' => 42, 'plural' => 'Found %count results'],
        'expected' => 'Found 42 results',
      ],
      // Plural handling
      'plural_one' => [
        'input' => 'Added a %3 to One %1',
        'args' => ['count' => 1, 'plural' => 'Added a %3 to %count %2', 2 => 'items', 1 => 'item', 3 => 'test'],
        'expected' => 'Added a test to One item',
      ],
      'plural_multiple' => [
        'input' => 'Added a %3 to One %1',
        'args' => ['count' => 5, 'plural' => 'Added a %3 to %count %2', 1 => 'item', 3 => 'test', 2 => 'items'],
        'expected' => 'Added a test to 5 items',
      ],
      'plural_zero' => [
        'input' => 'Added a %3 to One %2',
        'args' => ['count' => 0, 'plural' => 'Added a %3 to %count %1', 3 => 'test', 2 => 'item', 1 => 'items'],
        'expected' => 'Added a test to 0 items',
      ],
    ];
  }

  public function testAPluralMissingFromTheFirstDomainComesFromTheNext(): void {
    $this->useCatalog(
      ["1 thing\0%count things" => "1 Ding\0%count Dinge"],
      ['org.example.other' => ['Unrelated' => 'Unbeteiligt']],
    );
    $params = ['plural' => '%count things', 'domain' => ['org.example.other', NULL]];
    $this->assertSame('1 Ding', ts('1 thing', $params + ['count' => 1]));
    $this->assertSame('2 Dinge', ts('1 thing', $params + ['count' => 2]));
    $this->assertSame('2 others', ts('1 other', ['plural' => '%count others', 'count' => 2] + $params));
  }

  public function testRawKeepsTheCountPlaceholderForTheClient(): void {
    $params = ['plural' => 'Found %count results', 'count' => 2];
    $this->assertSame('Found 2 results', CRM_Core_I18n::singleton()->crm_translate('Found %count result', $params));
    $this->assertSame('Found %count results', CRM_Core_I18n::singleton()->crm_translate('Found %count result', $params + ['raw' => TRUE]));
  }

  /**
   * @dataProvider translateDataProvider
   */
  public function testTranslateWithArgs(
    string $input,
    array $args,
    string $expected,
  ): void {
    $result = CRM_Core_I18n::singleton()->crm_translate($input, $args);

    $this->assertSame($expected, $result);
  }

}
