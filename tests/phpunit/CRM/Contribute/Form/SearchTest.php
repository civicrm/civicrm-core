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
 *  Include parent class definition
 */

/**
 *  Test Contribution Search form filters
 *
 * @package CiviCRM
 */
class CRM_Contribute_Form_SearchTest extends CiviUnitTestCase {

  protected $_individual;
  protected $_tablesToTruncate = ['civicrm_contribution', 'civicrm_line_item'];

  public function setUp() {
    parent::setUp();
    $this->_individual = $this->individualCreate();
    $this->ids['Contact']['contactID1'] = $this->individualCreate([], 1);
    $this->ids['Contact']['contactID2'] = $this->individualCreate([], 2);
  }

  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   *  CRM-19325: Test CRM_Contribute_Form_Search batch filters
   */
  public function testBatchFilter() {
    $this->quickCleanup($this->_tablesToTruncate);
    $contactID1 = $this->individualCreate([], 1);
    $contactID2 = $this->individualCreate([], 2);
    $batchTitle = CRM_Batch_BAO_Batch::generateBatchName();

    // create batch
    $batch = $this->callAPISuccess('Batch', 'create', [
      'created_id' => $this->_individual,
      'created_date' => CRM_Utils_Date::processDate(date("Y-m-d"), date("H:i:s")),
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Batch_BAO_Batch', 'status_id', 'Data Entry'),
      'title' => $batchTitle,
      'item_count' => 2,
      'total' => 100,
      'type_id' => array_search('Contribution', CRM_Batch_BAO_Batch::buildOptions('type_id')),
    ]);
    $batchID = $batch['id'];

    $batchEntry = [
      'primary_profiles' => [1 => NULL, 2 => NULL, 3 => NULL],
      'primary_contact_id' => [
        1 => $contactID1,
        2 => $contactID2,
      ],
      'field' => [
        1 => [
          'financial_type' => 1,
          'total_amount' => 70,
          'receive_date' => '2013-07-24',
          'payment_instrument' => 1,
          'check_number' => NULL,
          'contribution_status_id' => 1,
        ],
        2 => [
          'financial_type' => 1,
          'total_amount' => 30,
          'receive_date' => '2014-07-24',
          'payment_instrument' => 1,
          'check_number' => NULL,
          'contribution_status_id' => 1,
        ],
      ],
      'actualBatchTotal' => 100,
    ];

    // create random contribution to check IS NULL filter more precisely
    $nonBatchContri = $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => 1,
      'total_amount' => 123,
      'receive_date' => '2014-07-24',
      'payment_instrument' => 1,
      'check_number' => NULL,
      'contribution_status_id' => 1,
      'contact_id' => $this->_individual,
    ]);
    $nonBatchContriID = $nonBatchContri['id'];

    // process batch entries
    $form = new CRM_Batch_Form_Entry();
    $form->setBatchID($batchID);
    $form->testProcessContribution($batchEntry);

    // fetch created contributions
    $entities = $this->callAPISuccess('EntityBatch', 'get', ['batch_id' => $batchID]);
    $ids = [];
    foreach ($entities['values'] as $value) {
      $ids[] = $value['entity_id'];
    }
    list($batchContriID1, $batchContriID2) = $ids;

    $useCases = [
      // Case 1: Search for ONLY those contributions which are created from batch
      [
        'form_value' => ['contribution_batch_id' => 'IS NOT NULL'],
        'expected_count' => 2,
        'expected_contribution' => [$batchContriID1, $batchContriID2],
        'expected_qill' => 'Batch Name Not Null',
      ],
      // Case 2: Search for ONLY those contributions which are NOT created from batch
      [
        'form_value' => ['contribution_batch_id' => 'IS NULL'],
        'expected_count' => 1,
        'expected_contribution' => [$nonBatchContriID],
        'expected_qill' => 'Batch Name Is Null',
      ],
      // Case 3: Search for ONLY those contributions which are created from batch ID - $batchID
      [
        'form_value' => ['contribution_batch_id' => $batchID],
        'expected_count' => 2,
        'expected_contribution' => [$batchContriID1, $batchContriID2],
        'expected_qill' => 'Batch Name = ' . $batchTitle,
      ],
    ];
    foreach ($useCases as $case) {
      $fv = $case['form_value'];
      CRM_Contact_BAO_Query::processSpecialFormValue($fv, ['contribution_batch_id']);
      $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($fv));
      list($select, $from, $where) = $query->query();

      // get and assert contribution count
      $contributions = CRM_Core_DAO::executeQuery(sprintf('SELECT DISTINCT civicrm_contribution.id %s %s AND civicrm_contribution.id IS NOT NULL', $from, $where))->fetchAll();
      foreach ($contributions as $key => $value) {
        $contributions[$key] = $value['id'];
      }
      // assert the contribution count
      $this->assertEquals($case['expected_count'], count($contributions));
      // assert the contribution IDs
      $this->checkArrayEquals($case['expected_contribution'], $contributions);
      // get and assert qill string
      $qill = trim(implode($query->getOperator(), CRM_Utils_Array::value(0, $query->qill())));
      $this->assertEquals($case['expected_qill'], $qill);
    }
  }

  /**
   *  CRM-20286: Test CRM_Contribute_Form_Search Card type filters
   */
  public function testCardTypeFilter() {
    $this->quickCleanup($this->_tablesToTruncate);
    $contactID1 = $this->individualCreate([], 1);
    $contactID2 = $this->individualCreate([], 2);
    $Contribution1 = $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => 1,
      'total_amount' => 100,
      'receive_date' => date('Ymd'),
      'payment_instrument' => 1,
      'contribution_status_id' => 1,
      'contact_id' => $contactID1,
    ]);
    $params = [
      'to_financial_account_id' => 1,
      'status_id' => 1,
      'contribution_id' => $Contribution1['id'],
      'payment_instrument_id' => 1,
      'card_type_id' => 1,
      'total_amount' => 100,
    ];
    CRM_Core_BAO_FinancialTrxn::create($params);
    $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => 1,
      'total_amount' => 150,
      'receive_date' => date('Ymd'),
      'payment_instrument' => 1,
      'contribution_status_id' => 1,
      'contact_id' => $contactID1,
    ]);
    $Contribution3 = $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => 1,
      'total_amount' => 200,
      'receive_date' => date('Ymd'),
      'payment_instrument' => 1,
      'contribution_status_id' => 1,
      'contact_id' => $contactID2,
    ]);
    $params = [
      'to_financial_account_id' => 1,
      'status_id' => 1,
      'contribution_id' => $Contribution3['id'],
      'payment_instrument_id' => 1,
      'card_type_id' => 2,
      'total_amount' => 200,
    ];
    CRM_Core_BAO_FinancialTrxn::create($params);

    $useCases = [
      // Case 1: Search for ONLY those contributions which have card type
      [
        'form_value' => ['financial_trxn_card_type_id' => 'IS NOT NULL'],
        'expected_count' => 2,
        'expected_contribution' => [$Contribution1['id'], $Contribution3['id']],
        'expected_qill' => 'Card Type Not Null',
      ],
      // Case 2: Search for ONLY those contributions which have Card Type as Visa
      [
        'form_value' => ['financial_trxn_card_type_id' => [1]],
        'expected_count' => 1,
        'expected_contribution' => [$Contribution1['id']],
        'expected_qill' => 'Card Type In Visa',
      ],
      // Case 3: Search for ONLY those contributions which have Card Type as Amex
      [
        'form_value' => ['financial_trxn_card_type_id' => [3]],
        'expected_count' => 0,
        'expected_contribution' => [],
        'expected_qill' => 'Card Type In Amex',
      ],
      // Case 4: Search for ONLY those contributions which have Card Type as Visa or MasterCard
      [
        'form_value' => ['financial_trxn_card_type_id' => [1, 2]],
        'expected_count' => 2,
        'expected_contribution' => [$Contribution1['id'], $Contribution3['id']],
        'expected_qill' => 'Card Type In Visa, MasterCard',
      ],
    ];

    foreach ($useCases as $case) {
      $fv = $case['form_value'];
      CRM_Contact_BAO_Query::processSpecialFormValue($fv, ['financial_trxn_card_type_id']);
      $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($fv));
      list($select, $from, $where) = $query->query();

      // get and assert contribution count
      $contributions = CRM_Core_DAO::executeQuery(sprintf('SELECT DISTINCT civicrm_contribution.id %s %s AND civicrm_contribution.id IS NOT NULL', $from, $where))->fetchAll();
      foreach ($contributions as $key => $value) {
        $contributions[$key] = $value['id'];
      }
      // assert the contribution count
      //$this->assertEquals($case['expected_count'], count($contributions));
      // assert the contribution IDs
      $this->checkArrayEquals($case['expected_contribution'], $contributions);
      // get and assert qill string
      $qill = trim(implode($query->getOperator(), CRM_Utils_Array::value(0, $query->qill())));
      $this->assertEquals($case['expected_qill'], $qill);
    }
  }

  /**
   *  CRM-20391: Test CRM_Contribute_Form_Search Card Number filters
   */
  public function testCardNumberFilter() {
    $this->quickCleanup($this->_tablesToTruncate);
    $contactID1 = $this->individualCreate([], 1);
    $contactID2 = $this->individualCreate([], 2);
    $Contribution1 = $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => 1,
      'total_amount' => 100,
      'receive_date' => date('Ymd'),
      'payment_instrument' => 1,
      'contribution_status_id' => 1,
      'contact_id' => $contactID1,
    ]);
    $params = [
      'to_financial_account_id' => 1,
      'status_id' => 1,
      'contribution_id' => $Contribution1['id'],
      'payment_instrument_id' => 1,
      'card_type_id' => 1,
      'total_amount' => 100,
      'pan_truncation' => 1234,
    ];
    CRM_Core_BAO_FinancialTrxn::create($params);
    $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => 1,
      'total_amount' => 150,
      'receive_date' => date('Ymd'),
      'payment_instrument' => 1,
      'contribution_status_id' => 1,
      'contact_id' => $contactID1,
    ]);
    $Contribution3 = $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => 1,
      'total_amount' => 200,
      'receive_date' => date('Ymd'),
      'payment_instrument' => 1,
      'contribution_status_id' => 1,
      'contact_id' => $contactID2,
    ]);
    $params = [
      'to_financial_account_id' => 1,
      'status_id' => 1,
      'contribution_id' => $Contribution3['id'],
      'payment_instrument_id' => 1,
      'card_type_id' => 2,
      'total_amount' => 200,
      'pan_truncation' => 5678,
    ];
    CRM_Core_BAO_FinancialTrxn::create($params);

    $useCases = [
      // Case 1: Search for ONLY those contributions which have card number
      [
        'form_value' => ['financial_trxn_pan_truncation' => 'IS NOT NULL'],
        'expected_count' => 2,
        'expected_contribution' => [$Contribution1['id'], $Contribution3['id']],
        'expected_qill' => 'Card Number Not Null',
      ],
      // Case 2: Search for ONLY those contributions which have Card Number as 1234
      [
        'form_value' => ['financial_trxn_pan_truncation' => 1234],
        'expected_count' => 1,
        'expected_contribution' => [$Contribution1['id']],
        'expected_qill' => 'Card Number Like %1234%',
      ],
      // Case 3: Search for ONLY those contributions which have Card Number as 8888
      [
        'form_value' => ['financial_trxn_pan_truncation' => 8888],
        'expected_count' => 0,
        'expected_contribution' => [],
        'expected_qill' => 'Card Number Like %8888%',
      ],
    ];

    foreach ($useCases as $case) {
      $fv = $case['form_value'];
      CRM_Contact_BAO_Query::processSpecialFormValue($fv, ['financial_trxn_pan_truncation']);
      $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($fv));
      list($select, $from, $where) = $query->query();

      // get and assert contribution count
      $contributions = CRM_Core_DAO::executeQuery(sprintf('SELECT DISTINCT civicrm_contribution.id %s %s AND civicrm_contribution.id IS NOT NULL', $from, $where))->fetchAll();
      foreach ($contributions as $key => $value) {
        $contributions[$key] = $value['id'];
      }
      // assert the contribution count
      $this->assertEquals($case['expected_count'], count($contributions));
      // assert the contribution IDs
      $this->checkArrayEquals($case['expected_contribution'], $contributions);
      // get and assert qill string
      $qill = trim(implode($query->getOperator(), CRM_Utils_Array::value(0, $query->qill())));
      $this->assertEquals($case['expected_qill'], $qill);
    }
  }

  /**
   *  Test contact contributions.
   */
  public function testContributionSearchWithContactID() {
    $contactID = $this->individualCreate([], 1);
    $fv = ['contact_id' => $contactID];
    $queryParams = CRM_Contact_BAO_Query::convertFormValues($fv);
    $selector = new CRM_Contribute_Selector_Search($queryParams, CRM_Core_Action::ADD);
    list($select, $from, $where) = $selector->getQuery()->query();

    // get and assert contribution count
    $contributions = CRM_Core_DAO::executeQuery("{$select} {$from} {$where}")->fetchAll();
    $this->assertEquals(count($contributions), 0);

    $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => "Donation",
      'receive_date' => date('Y-m-d'),
      'total_amount' => 10,
      'contact_id' => $contactID,
    ]);
    $selector = new CRM_Contribute_Selector_Search($queryParams, CRM_Core_Action::ADD);
    list($select, $from, $where) = $selector->getQuery()->query();

    // get and assert contribution count
    $contributions = CRM_Core_DAO::executeQuery("{$select} {$from} {$where}")->fetchAll();
    $this->assertEquals(count($contributions), 1);
  }

  /**
   *  Test CRM_Contribute_Form_Search Recurring Contribution Status Id filters
   *
   * @dataProvider getSearchData
   */
  public function testContributionRecurSearchFilters($formValues, $expectedCount, $expectedContact, $expectedQill, $expectedWhere = NULL) {
    $this->setUpRecurringContributions();

    $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($formValues));
    list($select, $from, $where, $having) = $query->query();

    // get and assert contribution count
    $contacts = CRM_Core_DAO::executeQuery(sprintf('SELECT DISTINCT contact_a.id, contact_a.display_name %s %s AND contact_a.id IS NOT NULL', $from, $where))->fetchAll();
    if ($expectedCount) {
      foreach ($contacts as $key => $value) {
        $this->assertEquals($expectedContact[$key], $value['display_name']);
      }
    }
    // assert the contribution count
    $this->assertEquals($expectedCount, count($contacts));
    // get and assert qill string
    $qill = $query->qill();
    $qillString = !empty($qill[1]) ? $qill[1] : CRM_Utils_Array::value(0, $qill);
    $qill = trim(implode($query->getOperator(), $qillString));
    $this->assertEquals($expectedQill, $qill);

    if ($expectedWhere) {
      $this->assertEquals($expectedWhere, $query->_where[1][0]);
    }
  }

  /**
   *  CRM-21343: Test CRM_Contribute_Form_Search Cancelled filters
   *
   * @throws CRM_Core_Exception
   */
  public function testCancelledFilter() {
    $this->quickCleanup($this->_tablesToTruncate);
    $contactID1 = $this->individualCreate([], 1);
    $contactID2 = $this->individualCreate([], 2);
    $Contribution1 = $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => 1,
      'total_amount' => 100,
      'receive_date' => date('Y-m-d'),
      'payment_instrument' => 1,
      'contribution_status_id' => 3,
      'cancel_date' => date('Ymd'),
      'cancel_reason' => 'Insufficient funds',
      'contact_id' => $contactID1,
    ]);
    $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => 1,
      'total_amount' => 150,
      'receive_date' => date('Y-m-d', strtotime(date('Y-m-d') . ' - 1 days')),
      'payment_instrument' => 1,
      'contribution_status_id' => 3,
      'cancel_date' => date('Y-m-d', strtotime(date('Y-m-d') . ' - 1 days')),
      'cancel_reason' => 'Insufficient funds',
      'contact_id' => $contactID2,
    ]);
    $Contribution3 = $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => 1,
      'total_amount' => 200,
      'receive_date' => date('Ymd'),
      'payment_instrument' => 1,
      'contribution_status_id' => 3,
      'cancel_date' => date('Ymd'),
      'cancel_reason' => 'Invalid Credit Card Number',
      'contact_id' => $contactID1,
    ]);

    $useCases = [
      // Case 1: Search for Cancelled Date
      [
        'form_value' => ['contribution_cancel_date' => date('Y-m-d')],
        'expected_count' => 2,
        'expected_contribution' => [$Contribution1['id'], $Contribution3['id']],
        'expected_qill' => "Cancelled / Refunded Date = " . date('F jS, Y') . " 12:00 AM",
      ],
      // Case 2: Search for Cancelled Reason
      [
        'form_value' => ['cancel_reason' => 'Invalid Credit Card Number'],
        'expected_count' => 1,
        'expected_contribution' => [$Contribution3['id']],
        'expected_qill' => "Cancellation / Refund Reason Like '%Invalid Credit Card Number%'",
      ],
      // Case 3: Search for Cancelled Date and Cancelled Reason
      [
        'form_value' => ['contribution_cancel_date' => date('Y-m-d'), 'cancel_reason' => 'Insufficient funds'],
        'expected_count' => 1,
        'expected_contribution' => [$Contribution1['id']],
        'expected_qill' => "Cancellation / Refund Reason Like '%Insufficient funds%'ANDCancelled / Refunded Date = " . date('F jS, Y') . " 12:00 AM",
      ],
    ];

    foreach ($useCases as $case) {
      $fv = $case['form_value'];
      CRM_Contact_BAO_Query::processSpecialFormValue($fv, ['cancel_reason']);
      $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($fv));
      list($select, $from, $where) = $query->query();

      // get and assert contribution count
      $contributions = CRM_Core_DAO::executeQuery(sprintf('SELECT DISTINCT civicrm_contribution.id %s %s AND civicrm_contribution.id IS NOT NULL AND civicrm_contribution.contribution_status_id = 3', $from, $where))->fetchAll();
      foreach ($contributions as $key => $value) {
        $contributions[$key] = $value['id'];
      }
      // assert the contribution count
      $this->assertEquals($case['expected_count'], count($contributions));
      // assert the contribution IDs
      $this->checkArrayEquals($case['expected_contribution'], $contributions);
      // get and assert qill string
      $qill = trim(implode($query->getOperator(), CRM_Utils_Array::value(0, $query->qill())));
      $this->assertEquals($case['expected_qill'], $qill);
    }
  }

  /**
   * Set up recurring contributions for the test.
   */
  protected function setUpRecurringContributions() {
    // "In Progress" recurring contribution for contactID1
    $ContributionRecur1 = $this->callAPISuccess('ContributionRecur', 'create', [
      'sequential' => 1,
      'contact_id' => $this->ids['Contact']['contactID1'],
      'frequency_interval' => 1,
      'frequency_unit' => "month",
      'amount' => 11,
      'currency' => "CAD",
      'payment_instrument_id' => 1,
      'contribution_status_id' => 5,
      'financial_type_id' => "Donation",
    ]);
    $Contribution1 = $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => 'Donation',
      'total_amount' => 11,
      'receive_date' => date('Ymd'),
      'receive_date_time' => NULL,
      'payment_instrument_id' => 1,
      'contribution_status_id' => 1,
      'contact_id' => $this->ids['Contact']['contactID1'],
      'contribution_recur_id' => $ContributionRecur1['id'],
    ]);
    $params = [
      'to_financial_account_id' => 1,
      'status_id' => 1,
      'contribution_id' => $Contribution1['id'],
      'payment_instrument_id' => 1,
      'card_type_id' => 1,
      'total_amount' => 11,
    ];
    CRM_Core_BAO_FinancialTrxn::create($params);
    // "Completed" recurring contribution for contactID2
    $ContributionRecur2 = $this->callAPISuccess('ContributionRecur', 'create', [
      'sequential' => 1,
      'contact_id' => $this->ids['Contact']['contactID2'],
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
      'amount' => 22,
      'currency' => "CAD",
      'payment_instrument_id' => 1,
      'contribution_status_id' => 1,
      'financial_type_id' => 'Donation',
      'trxn_id' => 'a transaction',
      'processor_id' => 'a processor',
      'start_date' => '20180101',
    ]);
    $Contribution2 = $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => 'Donation',
      'total_amount' => 22,
      'receive_date' => '20180101',
      'receive_date_time' => NULL,
      'payment_instrument' => 1,
      'contribution_status_id' => 1,
      'contact_id' => $this->ids['Contact']['contactID2'],
      'contribution_recur_id' => $ContributionRecur2['id'],
    ]);
    $params = [
      'to_financial_account_id' => 1,
      'status_id' => 1,
      'contribution_id' => $Contribution2['id'],
      'payment_instrument_id' => 1,
      'card_type_id' => 1,
      'total_amount' => 22,
    ];
    CRM_Core_BAO_FinancialTrxn::create($params);
  }

  /**
   * @return array
   */
  public function getSearchData() {
    $useCases = [
      // Case 1: Search for ONLY those recurring contributions with status "In Progress"
      'in_progress_search' => [
        'form_value' => ['contribution_recur_contribution_status_id' => 5],
        'expected_count' => 1,
        'expected_contact' => ['Mr. Joe Miller II'],
        'expected_qill' => "Recurring Contribution Status = 'In Progress'",
      ],
      // Case 2: Search for ONLY those recurring contributions with status "Completed"
      [
        'form_value' => ['contribution_recur_contribution_status_id' => 1],
        'expected_count' => 1,
        'expected_contact' => ['Mr. Terrence Smith II'],
        'expected_qill' => "Recurring Contribution Status = 'Completed'",
      ],
      // Case 3: Search for ONLY those recurring contributions with status "Cancelled"
      [
        'form_value' => ['contribution_recur_contribution_status_id' => 3],
        'expected_count' => 0,
        'expected_contact' => [],
        'expected_qill' => "Recurring Contribution Status = 'Cancelled'",
      ],
      'trxn_id_search' => [
        'form_value' => ['contribution_recur_trxn_id' => 'a transaction'],
        'expected_count' => 1,
        'expected_contact' => ['Mr. Terrence Smith II'],
        'expected_qill' => "Recurring Contribution Transaction ID = 'a transaction'",
      ],
      'processor_id_search' => [
        'form_value' => ['contribution_recur_processor_id' => 'a processor'],
        'expected_count' => 1,
        'expected_contact' => ['Mr. Terrence Smith II'],
        'expected_qill' => "Recurring Contribution Processor ID = 'a processor'",
      ],
      'receive_date_search' => [
        'form_value' => [['receive_date_high', '=', 20180101, 1, 0]],
        'expectedResult' => 1,
        'expected_contact' => ['Mr. Terrence Smith II'],
        'expected_qill' => 'Date Received - less than or equal to "January 1st, 2018 12:00 AM"',
        'expected_where' => "civicrm_contribution.receive_date <= '20180101000000'",
      ],
      'thankyou_date_search' => [
        'form_value' => [['thankyou_date_high', '=', 20180101, 1, 0]],
        'expectedResult' => 0,
        'expected_contact' => [],
        'expected_qill' => 'Thank-you Date - less than or equal to "January 1st, 2018 12:00 AM"',
        'expected_where' => "civicrm_contribution.thankyou_date <= '20180101000000'",
      ],
      'cancel_date_search_low' => [
        'form_value' => [['contribution_cancel_date_low', '=', 20180101, 1, 0]],
        'expectedResult' => 0,
        'expected_contact' => [],
        'expected_qill' => 'Cancelled / Refunded Date - greater than or equal to "January 1st, 2018 12:00 AM"',
        'expected_where' => "civicrm_contribution.cancel_date >= '20180101000000'",
      ],
      'cancel_date_search' => [
        'form_value' => [['contribution_cancel_date', '=', 20180101, 1, 0]],
        'expectedResult' => 0,
        'expected_contact' => [],
        'expected_qill' => 'Cancelled / Refunded Date = January 1st, 2018 12:00 AM',
        'expected_where' => "civicrm_contribution.cancel_date = '20180101000000'",
      ],
      'cancel_date_relative' => [
        'form_value' => [['contribution_cancel_date_relative', '=', 'this.year', 1, 0]],
        'expectedResult' => 0,
        'expected_contact' => [],
        'expected_qill' => 'Cancelled / Refunded Date is This calendar year (between January 1st, ' . date('Y') . ' 12:00 AM and December 31st, ' . date('Y') . ' 11:59 PM)',
        'expected_where' => "civicrm_contribution.cancel_date BETWEEN '" . date('Y') . "0101000000' AND '" . date('Y') . "1231235959'",
      ],
      'receipt_date_search_low' => [
        'form_value' => [['receipt_date_low', '=', 20180101, 1, 0]],
        'expectedResult' => 0,
        'expected_contact' => [],
        'expected_qill' => 'Receipt Date - greater than or equal to "January 1st, 2018 12:00 AM"',
        'expected_where' => "civicrm_contribution.receipt_date >= '20180101000000'",
      ],
      'receipt_date_search' => [
        'form_value' => [['receipt_date', '=', 20180101, 1, 0]],
        'expectedResult' => 0,
        'expected_contact' => [],
        'expected_qill' => 'Receipt Date = \'20180101\'',
        'expected_where' => "civicrm_contribution.receipt_date = 20180101",
      ],
      'revenue_recognition_search_high' => [
        'form_value' => [['revenue_recognition_date_high', '=', 20180101, 1, 0]],
        'expectedResult' => 0,
        'expected_contact' => [],
        'expected_qill' => 'Revenue Recognition Date - less than or equal to "January 1st, 2018 12:00 AM"',
        'expected_where' => "civicrm_contribution.revenue_recognition_date <= '20180101000000'",
      ],
      'revenue_recognition_search' => [
        'form_value' => [['revenue_recognition_date', '=', 20180101, 1, 0]],
        'expectedResult' => 0,
        'expected_contact' => [],
        'expected_qill' => 'Revenue Recognition Date = \'20180101\'',
        'expected_where' => "civicrm_contribution.revenue_recognition_date = 20180101",
      ],
      'start_date_search' => [
        'form_value' => [['contribution_recur_start_date', '=', 20180101, 1, 0]],
        'expectedResult' => 1,
        'expected_contact' => ['Mr. Terrence Smith II'],
        'expected_qill' => 'Recurring Contribution Start Date = January 1st, 2018 12:00 AM',
        'expected_where' => "civicrm_contribution_recur.start_date = '20180101000000'",
      ],
      'start_date_search_high' => [
        'form_value' => [['contribution_recur_start_date_high', '<=', 20180101, 1, 0]],
        'expectedResult' => 1,
        'expected_contact' => ['Mr. Terrence Smith II'],
        'expected_qill' => 'Recurring Contribution Start Date - less than or equal to "January 1st, 2018 12:00 AM"',
        'expected_where' => "civicrm_contribution_recur.start_date <= '20180101000000'",
      ],
      'start_date_search_relative' => [
        'form_value' => [['contribution_recur_start_date_relative', '=', 'this.year', 1, 0]],
        'expectedResult' => 1,
        'expected_contact' => ['Mr. Joe Miller II'],
        'expected_qill' => 'Start Date is This calendar year (between January 1st, ' . date('Y') . ' 12:00 AM and December 31st, ' . date('Y') . ' 11:59 PM)',
        'expected_where' => "civicrm_contribution_recur.start_date BETWEEN '" . date('Y') . "0101000000' AND '" . date('Y') . "1231235959'",
      ],

    ];
    return $useCases;
  }

}
