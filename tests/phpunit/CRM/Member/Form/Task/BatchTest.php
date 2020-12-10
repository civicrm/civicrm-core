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
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class CRM_Member_Form_Task_BatchTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;

  /**
   * Clean up after each test.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown() {
    $this->validateAllContributions();
    $this->validateAllPayments();
    $this->quickCleanUpFinancialEntities();
  }

  /**
   * Test batch submission.
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchSubmit() {
    $form = $this->getFormObject('CRM_Member_Form_Task_Batch');
    $membership1 = $this->contactMembershipCreate(['contact_id' => $this->individualCreate()]);
    $membership2 = $this->contactMembershipCreate(['contact_id' => $this->individualCreate()]);
    $this->createCustomGroupWithFieldOfType(['extends' => 'Membership'], 'text');
    $form->submit([
      'field' => [
        $membership1 => [
          $this->getCustomFieldName('text') => '80',
          'membership_join_date' => '2019-12-26',
        ],
        $membership2 => [
          $this->getCustomFieldName('text') => '100',
          'membership_join_date' => '2019-11-26',
          'membership_source' => 'form',
        ],
      ],
    ]);
    $memberships = $this->callAPISuccess('Membership', 'get', [])['values'];
    $this->assertEquals('2019-12-26', $memberships[$membership1]['join_date']);
    $this->assertEquals('2019-11-26', $memberships[$membership2]['join_date']);
    $this->assertEquals('form', $memberships[$membership2]['source']);
    $this->assertEquals(80, $memberships[$membership1][$this->getCustomFieldName('text')]);
    $this->assertEquals(100, $memberships[$membership2][$this->getCustomFieldName('text')]);
  }

}
