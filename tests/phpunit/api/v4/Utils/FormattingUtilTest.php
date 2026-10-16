<?php

namespace api\v4\Utils;

use api\v4\Api4TestBase;
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
