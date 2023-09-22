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
 *  Test APIv3 civicrm_contribute_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_ContributionSoft
 * @group headless
 */
class api_v3_ContributionSoftTest extends CiviUnitTestCase {

  /**
   * The hard credit contact.
   *
   * @var int
   */
  protected $_individualId;

  /**
   * The first soft credit contact.
   *
   * @var int
   */
  protected $_softIndividual1Id;

  /**
   * The second soft credit contact.
   *
   * @var int
   */
  protected $_softIndividual2Id;
  protected $_contributionId;
  protected $_financialTypeId = 1;
  protected $_apiversion = 3;
  protected $_entity = 'Contribution';
  public $debug = 0;
  protected $_params;

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction(TRUE);

    $this->_individualId = $this->individualCreate();
    $this->_softIndividual1Id = $this->individualCreate();
    $this->_softIndividual2Id = $this->individualCreate();
    $this->_contributionId = $this->contributionCreate(['contact_id' => $this->_individualId]);

    $this->processorCreate();
    $this->_params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 5.00,
      'net_amount' => 95.00,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    ];
  }

  /**
   * Test get methods.
   *
   * @todo - this might be better broken down into more smaller tests
   */
  public function testGetContributionSoft(): void {
    //We don't test for PCP fields because there's no PCP API, so we can't create campaigns.
    $p = [
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
      'soft_credit_type_id' => 4,
    ];

    $softContribution = $this->callAPISuccess('contribution_soft', 'create', $p);
    $params = [
      'id' => $softContribution['id'],
    ];
    $softContribution = $this->callAPISuccess('contribution_soft', 'get', $params);
    $this->assertEquals(1, $softContribution['count']);
    $this->assertEquals($softContribution['values'][$softContribution['id']]['contribution_id'], $this->_contributionId);
    $this->assertEquals($softContribution['values'][$softContribution['id']]['contact_id'], $this->_softIndividual1Id);
    $this->assertEquals($softContribution['values'][$softContribution['id']]['amount'], '10.00');
    $this->assertEquals($softContribution['values'][$softContribution['id']]['currency'], 'USD');
    $this->assertEquals($softContribution['values'][$softContribution['id']]['soft_credit_type_id'], 4);

    //create a second soft contribution on the same hard contribution - we are testing that 'id' gets the right soft contribution id (not the contribution id)
    $p['contact_id'] = $this->_softIndividual2Id;
    $softContribution2 = $this->callAPISuccess('contribution_soft', 'create', $p);

    // now we have 2 - test getcount
    $softContributionCount = $this->callAPISuccess('contribution_soft', 'getcount', []);
    $this->assertEquals(2, $softContributionCount);

    //check first contribution
    $result = $this->callAPISuccess('contribution_soft', 'get', [
      'id' => $softContribution['id'],
    ]);
    $this->assertEquals(1, $result['count']);
    $this->assertEquals($softContribution['id'], $result['id']);

    //test id only format - second soft credit
    $resultID2 = $this->callAPISuccess('contribution_soft', 'get', [
      'id' => $softContribution2['id'],
      'format.only_id' => 1,
    ]);
    $this->assertEquals($softContribution2['id'], $resultID2);

    //test get by contact id works
    $result = $this->callAPISuccess('contribution_soft', 'get', [
      'contact_id' => $this->_softIndividual2Id,
    ]);
    $this->assertEquals(1, $result['count']);

    $this->callAPISuccess('contribution_soft', 'Delete', [
      'id' => $softContribution['id'],
    ]);
    // check one soft credit remains
    $expectedCount = 1;
    $this->callAPISuccess('contribution_soft', 'getcount', [], $expectedCount);

    //check id is same as 2
    $this->assertEquals($softContribution2['id'], $this->callAPISuccess('contribution_soft', 'getvalue', ['return' => 'id']));

    $this->callAPISuccess('ContributionSoft', 'Delete', [
      'id' => $softContribution2['id'],
    ]);
  }

  /**
   * civicrm_contribution_soft.
   */
  public function testCreateEmptyParamsContributionSoft(): void {
    $this->callAPIFailure('contribution_soft', 'create', [],
      'Mandatory key(s) missing from params array: contribution_id, amount, contact_id'
    );
  }

  public function testCreateParamsWithoutRequiredKeysContributionSoft(): void {
    $this->callAPIFailure('contribution_soft', 'create', [],
      'Mandatory key(s) missing from params array: contribution_id, amount, contact_id'
    );
  }

  public function testCreateContributionSoftInvalidContact(): void {
    $params = [
      'contact_id' => 999,
      'contribution_id' => $this->_contributionId,
      'amount' => 10.00,
      'currency' => 'USD',
    ];

    $this->callAPIFailure('contribution_soft', 'create', $params,
      'contact_id is not valid : 999'
    );
  }

  public function testCreateContributionSoftInvalidContributionId(): void {
    $params = [
      'contribution_id' => 999999,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
    ];

    $this->callAPIFailure('contribution_soft', 'create', $params,
      'contribution_id is not valid : 999999'
    );
  }

  /**
   * Function tests that additional financial records are created when fee amount is recorded.
   */
  public function testCreateContributionSoft(): void {
    $params = [
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
      'soft_credit_type_id' => 5,
    ];

    $softContribution = $this->callAPISuccess('contribution_soft', 'create', $params);
    $this->assertEquals($softContribution['values'][$softContribution['id']]['contribution_id'], $this->_contributionId);
    $this->assertEquals($softContribution['values'][$softContribution['id']]['contact_id'], $this->_softIndividual1Id);
    $this->assertEquals($softContribution['values'][$softContribution['id']]['amount'], '10');
    $this->assertEquals($softContribution['values'][$softContribution['id']]['currency'], 'USD');
    $this->assertEquals($softContribution['values'][$softContribution['id']]['soft_credit_type_id'], 5);
  }

  /**
   * To Update Soft Contribution.
   *
   */
  public function testCreateUpdateContributionSoft(): void {
    //create a soft credit
    $params = [
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
      'soft_credit_type_id' => 6,
    ];

    $softContribution = $this->callAPISuccess('contribution_soft', 'create', $params);
    $softContributionID = $softContribution['id'];

    $old_params = [
      'contribution_soft_id' => $softContributionID,
    ];
    $original = $this->callAPISuccess('contribution_soft', 'get', $old_params);
    //Make sure it came back
    $this->assertEquals($original['id'], $softContributionID);
    //set up list of old params, verify
    $old_contribution_id = $original['values'][$softContributionID]['contribution_id'];
    $old_contact_id = $original['values'][$softContributionID]['contact_id'];
    $old_amount = $original['values'][$softContributionID]['amount'];
    $old_currency = $original['values'][$softContributionID]['currency'];
    $old_soft_credit_type_id = $original['values'][$softContributionID]['soft_credit_type_id'];

    //check against original values
    $this->assertEquals($old_contribution_id, $this->_contributionId);
    $this->assertEquals($old_contact_id, $this->_softIndividual1Id);
    $this->assertEquals($old_amount, 10.00);
    $this->assertEquals($old_currency, 'USD');
    $this->assertEquals($old_soft_credit_type_id, 6);
    $params = [
      'id' => $softContributionID,
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 7.00,
      'currency' => 'CAD',
      'soft_credit_type_id' => 7,
    ];

    $softContribution = $this->callAPISuccess('contribution_soft', 'create', $params);

    $new_params = [
      'id' => $softContribution['id'],
    ];
    $softContribution = $this->callAPISuccess('contribution_soft', 'get', $new_params);
    //check against original values
    $this->assertEquals($softContribution['values'][$softContributionID]['contribution_id'], $this->_contributionId);
    $this->assertEquals($softContribution['values'][$softContributionID]['contact_id'], $this->_softIndividual1Id);
    $this->assertEquals($softContribution['values'][$softContributionID]['amount'], 7.00);
    $this->assertEquals($softContribution['values'][$softContributionID]['currency'], 'CAD');
    $this->assertEquals($softContribution['values'][$softContributionID]['soft_credit_type_id'], 7);

    $params = [
      'id' => $softContributionID,
    ];
    $this->callAPISuccess('contribution_soft', 'delete', $params);
  }

  /**
   * civicrm_contribution_soft_delete methods.
   *
   */
  public function testDeleteEmptyParamsContributionSoft(): void {
    $params = [];
    $this->callAPIFailure('contribution_soft', 'delete', $params);
  }

  public function testDeleteWrongParamContributionSoft(): void {
    $params = [
      'contribution_source' => 'SSF',
    ];
    $this->callAPIFailure('contribution_soft', 'delete', $params);
  }

  public function testDeleteContributionSoft(): void {
    //create a soft credit
    $params = [
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
    ];

    $softContribution = $this->callAPISuccess('contribution_soft', 'create', $params);
    $softContributionID = $softContribution['id'];
    $params = [
      'id' => $softContributionID,
    ];
    $this->callAPISuccess('contribution_soft', 'delete', $params);
  }

  ///////////////// civicrm_contribution_search methods

  /**
   * Test civicrm_contribution_search with empty params.
   * All available contributions expected.
   */
  public function testSearchEmptyParams(): void {
    $p = [
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
    ];
    $softContribution = $this->callAPISuccess('contribution_soft', 'create', $p);

    $result = $this->callAPISuccess('contribution_soft', 'get', []);
    // We're taking the first element.
    $res = $result['values'][$softContribution['id']];

    $this->assertEquals($p['contribution_id'], $res['contribution_id']);
    $this->assertEquals($p['contact_id'], $res['contact_id']);
    $this->assertEquals($p['amount'], $res['amount']);
    $this->assertEquals($p['currency'], $res['currency']);
  }

  /**
   * Test civicrm_contribution_soft_search. Success expected.
   */
  public function testSearch(): void {
    $p1 = [
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual1Id,
      'amount' => 10.00,
      'currency' => 'USD',
    ];
    $softContribution1 = $this->callAPISuccess('contribution_soft', 'create', $p1);

    $p2 = [
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_softIndividual2Id,
      'amount' => 25.00,
      'currency' => 'CAD',
    ];
    $softContribution2 = $this->callAPISuccess('contribution_soft', 'create', $p2);

    $params = [
      'id' => $softContribution2['id'],
    ];
    $result = $this->callAPISuccess('contribution_soft', 'get', $params);
    $res = $result['values'][$softContribution2['id']];

    $this->assertEquals($p2['contribution_id'], $res['contribution_id']);
    $this->assertEquals($p2['contact_id'], $res['contact_id']);
    $this->assertEquals($p2['amount'], $res['amount']);
    $this->assertEquals($p2['currency'], $res['currency']);
  }

}
