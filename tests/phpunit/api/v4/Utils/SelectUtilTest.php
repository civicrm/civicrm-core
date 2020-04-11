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
 * $Id$
 *
 */


namespace api\v4\Utils;

use api\v4\UnitTestCase;
use Civi\Api4\Utils\SelectUtil;

/**
 * @group headless
 */
class SelectUtilTest extends UnitTestCase {

  private $emailFieldNames = [
    'id',
    'contact_id',
    'location_type_id',
    'email',
    'is_primary',
    'is_billing',
    'on_hold',
    'is_bulkmail',
    'hold_date',
    'reset_date',
    'signature_text',
    'signature_html',
    'contact.id',
    'contact.display_name',
    'contact.sort_name',
    'contact.phone.id',
    'contact.phone.phone',
    'contact.phone.phone_type_id',
  ];

  public function getSelectExamples() {
    return [
      ['any', ['*'], TRUE],
      ['any', ['*', 'one', 'two'], TRUE],
      ['one', ['one', 'two'], TRUE],
      ['one', ['o*', 'two'], TRUE],
      ['one', ['*o', 'two'], FALSE],
      ['zoo', ['one', 'two'], FALSE],
      ['one.id', ['one.id', 'two'], TRUE],
      ['one.id', ['one.*', 'two'], TRUE],
    ];
  }

  /**
   * @dataProvider getSelectExamples
   * @param string $field
   * @param array $selects
   * @param bool $expected
   */
  public function testIsFieldSelected($field, $selects, $expected) {
    $this->assertEquals($expected, SelectUtil::isFieldSelected($field, $selects));
  }

  public function getMatchingExamples() {
    return [
      [array_slice($this->emailFieldNames, 0, 12), '*'],
      [[], 'nothing'],
      [['email'], 'email'],
      [['contact_id', 'location_type_id'], '*_id'],
      [['contact_id', 'location_type_id'], '*o*_id'],
      [['contact_id'], 'con*_id'],
      [['is_primary', 'is_billing', 'is_bulkmail'], 'is_*'],
      [['is_billing', 'is_bulkmail'], 'is_*l*'],
      [['contact.id', 'contact.display_name', 'contact.sort_name'], 'contact.*'],
      [['contact.display_name', 'contact.sort_name'], 'contact.*_name'],
      [['contact.phone.id', 'contact.phone.phone', 'contact.phone.phone_type_id'], 'contact.phone.*'],
      [['contact.phone.phone', 'contact.phone.phone_type_id'], 'contact.phone.phone*'],
    ];
  }

  /**
   * @dataProvider getMatchingExamples
   * @param $expected
   * @param $pattern
   */
  public function testGetMatchingFields($expected, $pattern) {
    $this->assertEquals($expected, SelectUtil::getMatchingFields($pattern, $this->emailFieldNames));
  }

}
