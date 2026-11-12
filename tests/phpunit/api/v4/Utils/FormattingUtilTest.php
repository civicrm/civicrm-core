<?php

namespace api\v4\Utils;

use api\v4\Api4TestBase;
use Civi\Api4\Contact;
use Civi\Api4\Utils\FormattingUtil;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class FormattingUtilTest extends Api4TestBase implements TransactionalInterface {

  /**
   * @dataProvider getFilterByPathExamples
   * @param string $fieldPath
   * @param string $fieldName
   * @param array $originalValues
   * @param array $expectedValues
   */
  public function testFilterByPath(string $fieldPath, string $fieldName, array $originalValues, array $expectedValues): void {
    $this->assertEquals($expectedValues, FormattingUtil::filterByPath($originalValues, $fieldPath, $fieldName));
  }

  public static function getFilterByPathExamples(): array {
    $originalValueSets = [
      [
        'id' => 'a',
        'name' => 'a_name',
        'id_foo.id' => 'b',
        'id_foo.name' => 'b_name',
        'id.id_foo.id' => 'c',
        'id.id_foo.name' => 'c_name',
      ],
    ];
    return [
      ['id', 'id', $originalValueSets[0], $originalValueSets[0]],
      ['id_foo.id', 'id', $originalValueSets[0], ['id' => 'b', 'name' => 'b_name']],
      ['id.id_foo.id', 'id', $originalValueSets[0], ['id' => 'c', 'name' => 'c_name']],
    ];
  }

  /**
   * Boolean fields accept the usual string spellings, when writing and when filtering.
   *
   * @dataProvider getBooleanStrings
   */
  public function testBooleanStringInput(string $input, bool $expected): void {
    $optedOut = $this->createTestRecord('Contact', ['is_opt_out' => TRUE])['id'];
    $optedIn = $this->createTestRecord('Contact', ['is_opt_out' => FALSE])['id'];

    $created = $this->createTestRecord('Contact', ['is_opt_out' => $input])['id'];
    $this->assertSame($expected, Contact::get(FALSE)->addWhere('id', '=', $created)->addSelect('is_opt_out')->execute()->single()['is_opt_out']);

    Contact::update(FALSE)->addWhere('id', '=', $optedOut)->addValue('do_not_email', $input)->execute();
    $this->assertSame($expected, Contact::get(FALSE)->addWhere('id', '=', $optedOut)->addSelect('do_not_email')->execute()->single()['do_not_email']);

    $found = Contact::get(FALSE)
      ->addWhere('id', 'IN', [$optedOut, $optedIn])
      ->addWhere('is_opt_out', '=', $input)
      ->execute()->column('id');
    $this->assertEquals([$expected ? $optedOut : $optedIn], $found);

    $found = Contact::get(FALSE)
      ->addWhere('id', 'IN', [$optedOut, $optedIn])
      ->addWhere('is_opt_out', 'IN', [$input])
      ->execute()->column('id');
    $this->assertEquals([$expected ? $optedOut : $optedIn], $found);
  }

  /**
   * Operators that take a pattern keep their operand as a string.
   */
  public function testBooleanFieldPatternFilter(): void {
    $optedOut = $this->createTestRecord('Contact', ['is_opt_out' => TRUE])['id'];
    $optedIn = $this->createTestRecord('Contact', ['is_opt_out' => FALSE])['id'];
    foreach ([['LIKE', '0%'], ['CONTAINS', '0'], ['REGEXP', '^0$']] as [$operator, $pattern]) {
      $found = Contact::get(FALSE)
        ->addWhere('id', 'IN', [$optedOut, $optedIn])
        ->addWhere('is_opt_out', $operator, $pattern)
        ->execute()->column('id');
      $this->assertEquals([$optedIn], $found, $operator);
    }
    // 'no' stays a substring that neither 0 nor 1 contains.
    $found = Contact::get(FALSE)
      ->addWhere('id', 'IN', [$optedOut, $optedIn])
      ->addWhere('is_opt_out', 'CONTAINS', 'no')
      ->execute()->column('id');
    $this->assertEquals([], $found);
  }

  public static function getBooleanStrings(): array {
    return [
      ['true', TRUE],
      ['TRUE', TRUE],
      ['yes', TRUE],
      ['false', FALSE],
      ['No', FALSE],
    ];
  }

  /**
   * The string 'null' still unsets a boolean field.
   */
  public function testBooleanFieldAcceptsNullString(): void {
    $params = ['is_override' => 'null'];
    FormattingUtil::formatWriteParams($params, ['is_override' => ['name' => 'is_override', 'data_type' => 'Boolean']]);
    $this->assertSame('Null', $params['is_override']);
  }

  /**
   * A string that is no boolean spelling is refused, not stored as FALSE.
   */
  public function testInvalidBooleanStringInput(): void {
    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage('"maybe" is not a valid boolean value for is_opt_out.');
    $this->createTestRecord('Contact', ['is_opt_out' => 'maybe']);
  }

  /**
   * Test that contextual pseudoconstants resolve when control fields are selected only with suffixes.
   */
  public function testContextualPseudoconstantOutputFormatting(): void {
    $groupTitle = 'Test Group ' . substr(sha1(uniqid()), 0, 8);
    $group = $this->createTestRecord('Group', ['title' => $groupTitle]);
    $acl = $this->createTestRecord('ACL', [
      'name' => 'Volunteer Access',
      'entity_table' => 'civicrm_acl_role',
      'entity_id' => 1,
      'operation' => 'View',
      'object_table' => 'civicrm_group',
      'object_id' => $group['id'],
    ]);

    $acl = \Civi\Api4\ACL::get(FALSE)
      ->addWhere('id', '=', $acl['id'])
      ->addSelect('name', 'object_table:label', 'object_id:label')
      ->execute()
      ->first();

    $this->assertSame('Volunteer Access', $acl['name']);
    $this->assertSame('Group', $acl['object_table:label']);
    $this->assertSame($groupTitle, $acl['object_id:label']);
    $this->assertArrayNotHasKey('object_table', $acl);
    $this->assertArrayNotHasKey('object_id', $acl);
  }

}
