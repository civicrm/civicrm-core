<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | Use of this source code is governed by the AGPL license with some  |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Contribute_Page_AjaxTest
 * @group headless
 */
class CRM_Contribute_Page_AjaxTest extends CiviUnitTestCase {

  /**
   * Setup for test.
   */
  public function setUp(): void {
    parent::setUp();
    $this->_params = [
      'page' => 1,
      'rp' => 50,
      'offset' => 0,
      'rowCount' => 50,
      'sort' => NULL,
    ];
    $softContactParams = [
      'first_name' => 'soft',
      'last_name' => 'Contact',
    ];
    $this->ids['Contact']['SoftCredit'] = $this->individualCreate($softContactParams);

    // Create three sample contacts.
    foreach ([0, 1, 2] as $seq) {
      $this->individualCreate([], $seq);
    }
  }

  /**
   * Cleanup after test.
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Test retrieve Soft Contribution through AJAX.
   */
  public function testGetSoftContributionSelector(): void {
    $softCreditList = $amountSortedList = $softTypeSortedList = [];
    $this->createThreeSoftCredits();

    $_GET = array_merge($this->_params,
      [
        'cid' => $this->ids['Contact']['SoftCredit'],
        'context' => 'contribution',
      ]
    );
    try {
      CRM_Contribute_Page_AJAX::getSoftContributionRows();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $softCreditList = $e->errorData;
    }

    $_GET['columns'] = [['data' => 'amount'], ['data' => 'sct_label']];
    // get the results in descending order
    $_GET['order'] = [
      '0' => [
        'column' => 0,
        'dir' => 'desc',
      ],
    ];
    try {
      CRM_Contribute_Page_AJAX::getSoftContributionRows();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $amountSortedList = $e->errorData;
    }

    $this->assertEquals(3, $softCreditList['recordsTotal']);
    $this->assertEquals(3, $amountSortedList['recordsTotal']);

    $this->assertEquals('$600.00', $amountSortedList['data'][0]['amount']);
    $this->assertEquals('$150.00', $amountSortedList['data'][1]['amount']);
    $this->assertEquals('$100.00', $amountSortedList['data'][2]['amount']);

    // sort with soft credit types
    $_GET['order'][0]['column'] = 1;
    try {
      CRM_Contribute_Page_AJAX::getSoftContributionRows();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $softTypeSortedList = $e->errorData;
    }

    $this->assertEquals('Workplace Giving', $softTypeSortedList['data'][0]['sct_label']);
    $this->assertEquals('Solicited', $softTypeSortedList['data'][1]['sct_label']);
    $this->assertEquals('In Memory of', $softTypeSortedList['data'][2]['sct_label']);
  }

  /**
   * Test retrieve Soft Contribution For Membership.
   */
  public function testGetSoftContributionForMembership(): void {
    $softCreditList = [];
    $this->createThreeSoftCredits();
    // Check soft credit for membership.
    $membershipParams = [
      'contribution_contact_id' => $this->ids['Contact']['individual_0'],
      'contact_id' => $this->ids['Contact']['SoftCredit'],
      'contribution_status_id' => 1,
      'financial_type_id' => 2,
      'status_id' => 1,
      'total_amount' => 100,
      'receive_date' => '2018-06-08',
      'soft_credit' => [
        'soft_credit_type_id' => 11,
        'contact_id' => $this->ids['Contact']['SoftCredit'],
      ],
    ];
    $membershipID = $this->contactMembershipCreate($membershipParams);
    $_GET = array_merge($this->_params,
      [
        'cid' => $this->ids['Contact']['SoftCredit'],
        'context' => 'membership',
        'entityID' => $membershipID,
      ]
    );

    try {
      CRM_Contribute_Page_AJAX::getSoftContributionRows();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $softCreditList = $e->errorData;
    }
    $this->assertEquals(1, $softCreditList['recordsTotal']);
    $this->assertEquals('Gift', $softCreditList['data'][0]['sct_label']);
    $this->assertEquals('$100.00', $softCreditList['data'][0]['amount']);
    $this->assertEquals('Member Dues', $softCreditList['data'][0]['financial_type']);
  }

  /**
   * Create three soft credits against different contacts.
   */
  private function createThreeSoftCredits(): void {
    $credits = [
      [
        'contact_id' => $this->ids['Contact']['individual_0'],
        'soft_credit_type_id' => 3,
        'amount' => 100,
      ],
      [
        'contact_id' => $this->ids['Contact']['individual_1'],
        'soft_credit_type_id' => 2,
        'amount' => 600,
      ],
      [
        'contact_id' => $this->ids['Contact']['individual_2'],
        'soft_credit_type_id' => 5,
        'amount' => 150,
      ],
    ];
    // Create sample soft contribution for contact.
    foreach ($credits as $index => $credit) {
      $this->callAPISuccess('Contribution', 'create', [
        'contact_id' => $credit['contact_id'],
        // The assumption is the last to be created will have a later time.
        'receive_date' => ' + ' . $index . ' minutes',
        'total_amount' => $credit['amount'],
        'financial_type_id' => 1,
        'non_deductible_amount' => '10',
        'contribution_status_id' => 1,
        'soft_credit' => [
          '1' => [
            'contact_id' => $this->ids['Contact']['SoftCredit'],
            'amount' => $credit['amount'],
            'soft_credit_type_id' => $credit['soft_credit_type_id'],
          ],
        ],
      ]);
    }
  }

}
