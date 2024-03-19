<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace api\v4\Spec;

use Civi\Api4\Service\Spec\CustomFieldSpec;
use Civi\Api4\Service\Spec\SpecFormatter;
use api\v4\Api4TestBase;

/**
 * @group headless
 */
class SpecFormatterTest extends Api4TestBase {

  /**
   * @dataProvider arrayFieldSpecProvider
   *
   * @param array $fieldData
   * @param string $expectedName
   * @param string $expectedType
   */
  public function testArrayToField($fieldData, $expectedName, $expectedType) {
    $field = SpecFormatter::arrayToField($fieldData, 'TestEntity');

    $this->assertEquals($expectedName, $field->getName());
    $this->assertEquals($expectedType, $field->getDataType());
  }

  public function testCustomFieldWillBeReturned(): void {
    $customFieldId = 3333;
    $name = 'MyFancyField';

    $data = [
      'id' => $customFieldId,
      'name' => $name,
      'label' => $name,
      'data_type' => 'String',
      'html_type' => 'Select',
      'column_name' => $name,
      'serialize' => 1,
      'is_view' => FALSE,
    ];
    $customGroup = [
      'name' => 'my_group',
      'title' => 'My Group',
      'table_name' => 'civicrm_value_my_group',
    ];

    /** @var \Civi\Api4\Service\Spec\CustomFieldSpec $field */
    $field = SpecFormatter::arrayToField($data, 'TestEntity', $customGroup);

    $this->assertInstanceOf(CustomFieldSpec::class, $field);
    $this->assertEquals('my_group', $field->getCustomGroupName());
    $this->assertEquals($customFieldId, $field->getCustomFieldId());
    $this->assertEquals(\CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND, $field->getSerialize());
    $this->assertEquals('Select', $field->getInputType());
    $this->assertEquals('civicrm_value_my_group', $field->getTableName());
    $this->assertTrue($field->getInputAttrs()['multiple']);
  }

  /**
   * @return array
   */
  public function arrayFieldSpecProvider() {
    return [
      [
        [
          'name' => 'Foo',
          'title' => 'Bar',
          'type' => \CRM_Utils_Type::T_STRING,
        ],
        'Foo',
        'String',
      ],
      [
        [
          'name' => 'MyField',
          'title' => 'Bar',
          'type' => \CRM_Utils_Type::T_STRING,
          // this should take precedence
          'data_type' => 'Boolean',
        ],
        'MyField',
        'Boolean',
      ],
    ];
  }

  /**
   * @param int $dataTypeInt
   * @param string $htmlType
   * @param int|float|null $step
   * @param int|float $expectedStep
   *
   * @dataProvider numericFieldTypesProvider
   */
  public function testNumericFields(int $dataTypeInt, string $htmlType, $step, $expectedStep): void {
    $data = [
      'name' => 'Foo',
      'title' => 'Bar',
      'type' => $dataTypeInt,
      'html' => ['type' => $htmlType],
    ];
    if ($step !== NULL) {
      $data['html']['step'] = $step;
    }

    $fieldSpec = SpecFormatter::arrayToField($data, 'TestEntity');
    static::assertSame('Number', $fieldSpec->getInputType());
    static::assertSame(['step' => $expectedStep], $fieldSpec->getInputAttrs());
  }

  public function numericFieldTypesProvider(): iterable {
    yield [\CRM_Utils_Type::T_FLOAT, 'Text', NULL, .01];
    yield [\CRM_Utils_Type::T_FLOAT, 'Text', 2, 2];
    yield [\CRM_Utils_Type::T_FLOAT, 'Number', NULL, .01];
    yield [\CRM_Utils_Type::T_FLOAT, 'Number', 2, 2];
    yield [\CRM_Utils_Type::T_INT, 'Text', NULL, 1];
    yield [\CRM_Utils_Type::T_INT, 'Text', 2, 2];
    yield [\CRM_Utils_Type::T_INT, 'Number', NULL, 1];
    yield [\CRM_Utils_Type::T_INT, 'Number', 2, 2];
    yield [\CRM_Utils_Type::T_MONEY, 'Text', NULL, .01];
    yield [\CRM_Utils_Type::T_MONEY, 'Text', 2, 2];
    yield [\CRM_Utils_Type::T_MONEY, 'Number', NULL, .01];
    yield [\CRM_Utils_Type::T_MONEY, 'Number', 2, 2];
  }

}
