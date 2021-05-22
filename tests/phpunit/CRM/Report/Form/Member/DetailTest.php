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
 *  Test Activity report outcome
 *
 * @package CiviCRM
 */
class CRM_Report_Form_Member_DetailTest extends CiviReportTestCase {

  public function setUp(): void {
    parent::setUp();

    $this->_orgContactID = $this->organizationCreate();
    $this->_financialTypeId = 1;
    $this->_membershipStatusID = $this->membershipStatusCreate('test status');
    $this->_membershipTypeID = $this->membershipTypeCreate(['name' => 'Test Member']);
  }

  public function testAutoRenewDisplay() {

    $indContactID1 = $this->individualCreate();
    $indContactID2 = $this->individualCreate();
    $indContactID3 = $this->individualCreate();
    $recurStatus = array_search(
      'In Progress',
      CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name')
    );
    $recurParams = [
      'contact_id' => $indContactID1,
      'amount' => '5.00',
      'currency' => 'USD',
      'frequency_unit' => 'day',
      'frequency_interval' => 30,
      'create_date' => '2019-06-22',
      'start_date' => '2019-06-22',
      'contribution_status_id' => $recurStatus,
    ];
    $recur1 = civicrm_api3('ContributionRecur', 'create', $recurParams);
    $memParams = [
      'membership_type_id' => $this->_membershipTypeID,
      'contact_id' => $indContactID1,
      'status_id' => $this->_membershipStatusID,
      'contribution_recur_id' => $recur1['id'],
      'join_date' => '2019-06-22',
      'start_date' => '2019-06-22',
      'end_date' => '2019-07-22',
      'source' => 'Payment',
    ];
    $mem1 = civicrm_api3('Membership', 'create', $memParams);
    $recurParams['end_date'] = '2019-06-23';
    $recurParams['contact_id'] = $indContactID1;
    $recur2 = civicrm_api3('ContributionRecur', 'create', $recurParams);
    $memParams['contact_id'] = $indContactID2;
    $memParams['contribution_recur_id'] = $recur2['id'];
    $mem2 = civicrm_api3('Membership', 'create', $memParams);
    $memParams['contact_id'] = $indContactID3;
    unset($memParams['contribution_recur_id']);
    $mem3 = civicrm_api3('Membership', 'create', $memParams);

    $input = [
      'fields' => ['autorenew_status_id'],
      'filters' => [
        'tid_op' => 'in',
        'tid_value' => $this->_membershipTypeID,
        'autorenew_status_id_op' => 'in',
        'autorenew_status_id_value' => $recurStatus,
      ],
    ];
    $obj = $this->getReportObject('CRM_Report_Form_Member_Detail', $input);
    $results = $obj->getResultSet();
    $this->assertCount(2, $results);
    foreach ($results as $result) {
      if ($result['civicrm_contact_id'] == $indContactID1) {
        $this->assertStringNotContainsString('(ended)', $result['civicrm_contribution_recur_autorenew_status_id']);
      }
      if ($result['civicrm_contact_id'] == $indContactID2) {
        $this->assertStringContainsString('(ended)', $result['civicrm_contribution_recur_autorenew_status_id']);
      }
    }

    $input['filters']['autorenew_status_id_value'] = "0,$recurStatus";
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
