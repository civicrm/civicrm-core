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

use Civi\Api4\ContributionRecur;

/**
 *  Test Activity report outcome
 *
 * @package CiviCRM
 */
class CRM_Report_Form_Member_DetailTest extends CiviReportTestCase {

  /**
   * @var int
   */
  private $membershipTypeID;

  public function setUp(): void {
    parent::setUp();
    $this->membershipStatusCreate('test status');
    $this->membershipTypeID = $this->membershipTypeCreate(['name' => 'Test Member']);
  }

  /**
   * Test the display of the auto renew memberships.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAutoRenewDisplay(): void {
    $indContactID1 = $this->individualCreate();
    $indContactID2 = $this->individualCreate();
    $indContactID3 = $this->individualCreate();
    $recurParams = [
      'contact_id' => $indContactID1,
      'amount' => '5.00',
      'currency' => 'USD',
      'frequency_unit' => 'day',
      'frequency_interval' => 30,
      'create_date' => '2019-06-22',
      'start_date' => '2019-06-22',
      'contribution_status_id:name' => 'In Progress',
    ];
    $recur1 = ContributionRecur::create()->setValues($recurParams)->execute()->first();
    $memParams = [
      'membership_type_id' => $this->membershipTypeID,
      'contact_id' => $indContactID1,
      'status_id:name' => 'test status',
      'contribution_recur_id' => $recur1['id'],
      'join_date' => '2019-06-22',
      'start_date' => '2019-06-22',
      'end_date' => '2019-07-22',
      'source' => 'Payment',
    ];
    $this->callAPISuccess('Membership', 'create', $memParams);
    $recurParams['end_date'] = '2019-06-23';
    $recurParams['contact_id'] = $indContactID1;
    $recur2 = ContributionRecur::create()->setValues($recurParams)->execute()->first();
    $memParams['contact_id'] = $indContactID2;
    $memParams['contribution_recur_id'] = $recur2['id'];
    civicrm_api3('Membership', 'create', $memParams);
    $memParams['contact_id'] = $indContactID3;
    unset($memParams['contribution_recur_id']);
    civicrm_api3('Membership', 'create', $memParams);

    $input = [
      'fields' => ['autorenew_status_id'],
      'filters' => [
        'tid_op' => 'in',
        'tid_value' => $this->membershipTypeID,
        'autorenew_status_id_op' => 'in',
        'autorenew_status_id_value' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionRecur', 'contribution_status_id', 'In Progress'),
      ],
    ];
    $obj = $this->getReportObject('CRM_Report_Form_Member_Detail', $input);
    $results = $obj->getResultSet();
    $this->assertCount(2, $results);
    foreach ($results as $result) {
      if ($result['civicrm_contact_id'] === $indContactID1) {
        $this->assertStringNotContainsString('(ended)', $result['civicrm_contribution_recur_autorenew_status_id']);
      }
      if ($result['civicrm_contact_id'] === $indContactID2) {
        $this->assertStringContainsString('(ended)', $result['civicrm_contribution_recur_autorenew_status_id']);
      }
    }

    $input['filters']['autorenew_status_id_value'] = '0,' . CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionRecur', 'status_id', 'In Progress');
    $obj = $this->getReportObject('CRM_Report_Form_Member_Detail', $input);
    $results = $obj->getResultSet();
    $this->assertCount(3, $results);

    $input['filters']['autorenew_status_id_op'] = 'nll';
    $obj = $this->getReportObject('CRM_Report_Form_Member_Detail', $input);
    $results = $obj->getResultSet();
    $this->assertCount(1, $results);

    $input['filters']['autorenew_status_id_op'] = 'in';
    $input['filters']['autorenew_status_id_value'] = 0;
    $obj = $this->getReportObject('CRM_Report_Form_Member_Detail', $input);
    $results = $obj->getResultSet();
    $this->assertCount(1, $results);

    $input['filters']['autorenew_status_id_op'] = 'in';
    $input['filters']['autorenew_status_id_value'] = 1000;
    $obj = $this->getReportObject('CRM_Report_Form_Member_Detail', $input);
    $results = $obj->getResultSet();
    $this->assertCount(0, $results);

    $input['filters']['autorenew_status_id_op'] = 'notin';
    $input['filters']['autorenew_status_id_value'] = 1000;
    $obj = $this->getReportObject('CRM_Report_Form_Member_Detail', $input);
    $results = $obj->getResultSet();
    $this->assertCount(3, $results);

    $input['filters']['autorenew_status_id_value'] = '0,1000';
    $obj = $this->getReportObject('CRM_Report_Form_Member_Detail', $input);
    $results = $obj->getResultSet();
    $this->assertCount(2, $results);
  }

}
