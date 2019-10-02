<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */


namespace api\v4\Spec;

use Civi\Api4\Service\Spec\CustomFieldSpec;
use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Api4\Service\Spec\SpecFormatter;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class SpecFormatterTest extends UnitTestCase {

  public function testSpecToArray() {
    $spec = new RequestSpec('Contact', 'get');
    $fieldName = 'last_name';
    $field = new FieldSpec($fieldName, 'Contact');
    $spec->addFieldSpec($field);
    $arraySpec = SpecFormatter::specToArray($spec->getFields());

    $this->assertEquals('String', $arraySpec[$fieldName]['data_type']);
  }

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

  public function testCustomFieldWillBeReturned() {
    $customGroupId = 1432;
    $customFieldId = 3333;
    $name = 'MyFancyField';

    $data = [
      'custom_group_id' => $customGroupId,
      'custom_group.name' => 'my_group',
      'id' => $customFieldId,
      'name' => $name,
      'data_type' => 'String',
      'html_type' => 'Multi-Select',
    ];

    /** @var \Civi\Api4\Service\Spec\CustomFieldSpec $field */
    $field = SpecFormatter::arrayToField($data, 'TestEntity');

    $this->assertInstanceOf(CustomFieldSpec::class, $field);
    $this->assertEquals('my_group', $field->getCustomGroupName());
    $this->assertEquals($customFieldId, $field->getCustomFieldId());
    $this->assertEquals(\CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND, $field->getSerialize());
    $this->assertEquals('Select', $field->getInputType());
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

}
