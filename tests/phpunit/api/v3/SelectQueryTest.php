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
 * Test APIv3 ability to join across multiple entities
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_SelectQueryTest extends CiviUnitTestCase {

  private $hookEntity;
  private $hookCondition = [];

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
    CRM_Utils_Hook::singleton()->setHook('civicrm_selectWhereClause', [$this, 'hook_civicrm_selectWhereClause']);
  }

  public function testHookPhoneClause() {
    $person1 = $this->callAPISuccess('Contact', 'create', ['contact_type' => 'Individual', 'first_name' => 'Bob', 'last_name' => 'Tester']);
    $cid = $person1['id'];
    for ($number = 1; $number < 6; ++$number) {
      $this->callAPISuccess('Phone', 'create', [
        'contact_id' => $cid,
        'phone' => $number,
      ]);
    }
    $this->hookEntity = 'Phone';
    $this->hookCondition = [
      'phone' => ['= 3'],
    ];
    $phone = $this->callAPISuccessGetSingle('Phone', ['contact_id' => $cid, 'check_permissions' => 1]);
    $this->assertEquals(3, $phone['phone']);
  }

  public function testHookContactClause() {
    $person1 = $this->callAPISuccess('Contact', 'create', ['contact_type' => 'Individual', 'first_name' => 'Bob', 'last_name' => 'Tester', 'email' => 'bob@test.er']);
    $person2 = $this->callAPISuccess('Contact', 'create', ['contact_type' => 'Individual', 'first_name' => 'Tom', 'last_name' => 'Tester', 'email' => 'tom@test.er']);
    $person3 = $this->callAPISuccess('Contact', 'create', ['contact_type' => 'Individual', 'first_name' => 'Tim', 'last_name' => 'Tester', 'email' => 'tim@test.er']);
    $this->hookEntity = 'Contact';
    $this->hookCondition = ['id' => ['= ' . $person2['id']]];
    $email = $this->callAPISuccessGetSingle('Email', ['check_permissions' => 1]);
    $this->assertEquals($person2['id'], $email['contact_id']);
  }

  /**
   * Implements hook_civicrm_selectWhereClause().
   */
  public function hook_civicrm_selectWhereClause($entity, &$clauses) {
    if ($entity == $this->hookEntity) {
      foreach ($this->hookCondition as $field => $clause) {
        $clauses[$field] = array_merge(CRM_Utils_Array::value($field, $clauses, []), $clause);
      }
    }
  }

}
