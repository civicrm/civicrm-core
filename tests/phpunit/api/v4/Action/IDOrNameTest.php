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

namespace api\v4\Action;

use api\v4\Api4TestBase;
use Civi\Test\TransactionalInterface;

/**
 * Test that APIv4 stores the same id whether it is given an id or a name (field:name).
 *
 * See CRMTraits_Api_IDOrNameTrait for the purpose and method.
 *
 * @group headless
 */
class IDOrNameTest extends Api4TestBase implements TransactionalInterface {

  use \CRMTraits_Api_IDOrNameTrait;

  protected ?array $enableComponents = ['CiviCase', 'CiviContribute', 'CiviEvent', 'CiviMail', 'CiviMember', 'CiviPledge'];

  public function tearDown(): void {
    $this->deleteIDOrNameCustomGroup();
    parent::tearDown();
  }

  /**
   * @dataProvider getIDOrNameFields
   *
   * @param string $entity
   * @param string $field
   * @param string|array $name
   * @param string $valuesMethod
   *   Method giving the record's other values.
   */
  public function testIDOrName(string $entity, string $field, $name, string $valuesMethod): void {
    // Called first as it creates the records some cases refer to by name.
    $this->$valuesMethod();
    $expected = $this->getIDForName($entity, $field, $name);
    foreach ([$field => $expected, "$field:name" => $name] as $key => $value) {
      $values = $this->$valuesMethod();
      unset($values[$field]);
      $values[$key] = $value;
      $id = civicrm_api4($entity, 'create', ['values' => $values, 'checkPermissions' => FALSE])->first()['id'];
      $this->assertEquals($expected, $this->getStoredIDOrNameValue($entity, $id, $field), "$entity.$field given as $key");
    }
  }

}
