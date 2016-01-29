<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Test APIv3 ability to join across multiple entities
 *
 * @package CiviCRM_APIv3
 */
class api_v3_SelectQueryTest extends CiviUnitTestCase {

  private $hookEntity;
  private $hookCondition = array();

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
    CRM_Utils_Hook::singleton()->setHook('civicrm_selectWhereClause', array($this, 'hook_civicrm_selectWhereClause'));
  }

  public function testHookPhoneClause() {
    $person1 = $this->callAPISuccess('Contact', 'create', array('contact_type' => 'Individual', 'first_name' => 'Bob', 'last_name' => 'Tester'));
    $cid = $person1['id'];
    for ($number = 1; $number < 6; ++$number) {
      $this->callAPISuccess('Phone', 'create', array(
        'contact_id' => $cid,
        'phone' => $number,
      ));
    }
    $this->hookEntity = 'Phone';
    $this->hookCondition = array(
      'phone' => array('= 3'),
    );
    $phone = $this->callAPISuccessGetSingle('Phone', array('contact_id' => $cid, 'check_permissions' => 1));
    $this->assertEquals(3, $phone['phone']);
  }

  public function testHookContactClause() {
    $person1 = $this->callAPISuccess('Contact', 'create', array('contact_type' => 'Individual', 'first_name' => 'Bob', 'last_name' => 'Tester', 'email' => 'bob@test.er'));
    $person2 = $this->callAPISuccess('Contact', 'create', array('contact_type' => 'Individual', 'first_name' => 'Tom', 'last_name' => 'Tester', 'email' => 'tom@test.er'));
    $person3 = $this->callAPISuccess('Contact', 'create', array('contact_type' => 'Individual', 'first_name' => 'Tim', 'last_name' => 'Tester', 'email' => 'tim@test.er'));
    $this->hookEntity = 'Contact';
    $this->hookCondition = array('id' => array('= ' . $person2['id']));
    $email = $this->callAPISuccessGetSingle('Email', array('check_permissions' => 1));
    $this->assertEquals($person2['id'], $email['contact_id']);
  }

  /**
   * Implements hook_civicrm_selectWhereClause().
   */
  public function hook_civicrm_selectWhereClause($entity, &$clauses) {
    if ($entity == $this->hookEntity) {
      foreach ($this->hookCondition as $field => $clause) {
        $clauses[$field] = array_merge(CRM_Utils_Array::value($field, $clauses, array()), $clause);
      }
    }
  }

}
