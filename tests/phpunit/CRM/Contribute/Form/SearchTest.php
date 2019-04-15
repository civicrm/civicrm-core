<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
  }

  public function tearDown() {
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
      'status_id' => CRM_Core_Pseudoconstant::getKey('CRM_Batch_BAO_Batch', 'status_id', 'Data Entry'),
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
          'receive_date_time' => NULL,
          'payment_instrument' => 1,
          'check_number' => NULL,
          'contribution_status_id' => 1,
        ],
        2 => [
          'financial_type' => 1,
          'total_amount' => 30,
          'receive_date' => '2014-07-24',
          'receive_date_time' => NULL,
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
      'receive_date_time' => NULL,
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
    $entities = $this->callAPISuccess('EntityBatch', 'get', array('batch_id' => $batchID));
    $ids = [];
    foreach ($entities['values'] as $value) {
      $ids[] = $value['entity_id'];
    }
    list($batchContriID1, $batchContriID2) = $ids;

    $useCases = [
      // Case 1: Search for ONLY those contributions which are created from batch
      [
        'form_value' => array('contribution_batch_id' => 'IS NOT NULL'),
        'expected_count' => 2,
        'expected_contribution' => array($batchContriID1, $batchContriID2),
        'expected_qill' => 'Batch Name Not Null',
      ],
      // Case 2: Search for ONLY those contributions which are NOT created from batch
      [
        'form_value' => array('contribution_batch_id' => 'IS NULL'),
        'expected_count' => 1,
        'expected_contribution' => array($nonBatchContriID),
        'expected_qill' => 'Batch Name Is Null',
      ],
      // Case 3: Search for ONLY those contributions which are created from batch ID - $batchID
      [
        'form_value' => array('contribution_batch_id' => $batchID),
        'expected_count' => 2,
        'expected_contribution' => array($batchContriID1, $batchContriID2),
        'expected_qill' => 'Batch Name = ' . $batchTitle,
      ],
    ];
    foreach ($useCases as $case) {
      $fv = $case['form_value'];
      CRM_Contact_BAO_Query::processSpecialFormValue($fv, array('contribution_batch_id'));
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
    $contactID1 = $this->individualCreate(array(), 1);
    $contactID2 = $this->individualCreate(array(), 2);
    $Contribution1 = $this->callAPISuccess('Contribution', 'create', array(
      'financial_type_id' => 1,
      'total_amount' => 100,
      'receive_date' => date('Ymd'),
      'receive_date_time' => NULL,
      'payment_instrument' => 1,
      'contribution_status_id' => 1,
      'contact_id' => $contactID1,
    ));
    $params = array(
      'to_financial_account_id' => 1,
      'status_id' => 1,
      'contribution_id' => $Contribution1['id'],
      'payment_instrument_id' => 1,
      'card_type_id' => 1,
      'total_amount' => 100,
    );
    CRM_Core_BAO_FinancialTrxn::create($params);
    $this->callAPISuccess('Contribution', 'create', array(
      'financial_type_id' => 1,
      'total_amount' => 150,
      'receive_date' => date('Ymd'),
      'receive_date_time' => NULL,
      'payment_instrument' => 1,
      'contribution_status_id' => 1,
      'contact_id' => $contactID1,
    ));
    $Contribution3 = $this->callAPISuccess('Contribution', 'create', array(
      'financial_type_id' => 1,
      'total_amount' => 200,
      'receive_date' => date('Ymd'),
      'receive_date_time' => NULL,
      'payment_instrument' => 1,
      'contribution_status_id' => 1,
      'contact_id' => $contactID2,
    ));
    $params = array(
      'to_financial_account_id' => 1,
      'status_id' => 1,
      'contribution_id' => $Contribution3['id'],
      'payment_instrument_id' => 1,
      'card_type_id' => 2,
      'total_amount' => 200,
    );
    CRM_Core_BAO_FinancialTrxn::create($params);

    $useCases = array(
      // Case 1: Search for ONLY those contributions which have card type
      array(
        'form_value' => array('financial_trxn_card_type_id' => 'IS NOT NULL'),
        'expected_count' => 2,
        'expected_contribution' => array($Contribution1['id'], $Contribution3['id']),
        'expected_qill' => 'Card Type Not Null',
      ),
      // Case 2: Search for ONLY those contributions which have Card Type as Visa
      array(
        'form_value' => array('financial_trxn_card_type_id' => array(1)),
        'expected_count' => 1,
        'expected_contribution' => array($Contribution1['id']),
        'expected_qill' => 'Card Type In Visa',
      ),
      // Case 3: Search for ONLY those contributions which have Card Type as Amex
      array(
        'form_value' => array('financial_trxn_card_type_id' => array(3)),
        'expected_count' => 0,
        'expected_contribution' => array(),
        'expected_qill' => 'Card Type In Amex',
      ),
      // Case 4: Search for ONLY those contributions which have Card Type as Visa or MasterCard
      array(
        'form_value' => array('financial_trxn_card_type_id' => array(1, 2)),
        'expected_count' => 2,
        'expected_contribution' => array($Contribution1['id'], $Contribution3['id']),
        'expected_qill' => 'Card Type In Visa, MasterCard',
      ),
    );

    foreach ($useCases as $case) {
      $fv = $case['form_value'];
      CRM_Contact_BAO_Query::processSpecialFormValue($fv, array('financial_trxn_card_type_id'));
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
    $contactID1 = $this->individualCreate(array(), 1);
    $contactID2 = $this->individualCreate(array(), 2);
    $Contribution1 = $this->callAPISuccess('Contribution', 'create', array(
      'financial_type_id' => 1,
      'total_amount' => 100,
      'receive_date' => date('Ymd'),
      'receive_date_time' => NULL,
      'payment_instrument' => 1,
      'contribution_status_id' => 1,
      'contact_id' => $contactID1,
    ));
    $params = array(
      'to_financial_account_id' => 1,
      'status_id' => 1,
      'contribution_id' => $Contribution1['id'],
      'payment_instrument_id' => 1,
      'card_type_id' => 1,
      'total_amount' => 100,
      'pan_truncation' => 1234,
    );
    CRM_Core_BAO_FinancialTrxn::create($params);
    $this->callAPISuccess('Contribution', 'create', array(
      'financial_type_id' => 1,
      'total_amount' => 150,
      'receive_date' => date('Ymd'),
      'receive_date_time' => NULL,
      'payment_instrument' => 1,
      'contribution_status_id' => 1,
      'contact_id' => $contactID1,
    ));
    $Contribution3 = $this->callAPISuccess('Contribution', 'create', array(
      'financial_type_id' => 1,
      'total_amount' => 200,
      'receive_date' => date('Ymd'),
      'receive_date_time' => NULL,
      'payment_instrument' => 1,
      'contribution_status_id' => 1,
      'contact_id' => $contactID2,
    ));
    $params = array(
      'to_financial_account_id' => 1,
      'status_id' => 1,
      'contribution_id' => $Contribution3['id'],
      'payment_instrument_id' => 1,
      'card_type_id' => 2,
      'total_amount' => 200,
      'pan_truncation' => 5678,
    );
    CRM_Core_BAO_FinancialTrxn::create($params);

    $useCases = array(
      // Case 1: Search for ONLY those contributions which have card number
      array(
        'form_value' => array('financial_trxn_pan_truncation' => 'IS NOT NULL'),
        'expected_count' => 2,
        'expected_contribution' => array($Contribution1['id'], $Contribution3['id']),
        'expected_qill' => 'Card Number Not Null',
      ),
      // Case 2: Search for ONLY those contributions which have Card Number as 1234
      array(
        'form_value' => array('financial_trxn_pan_truncation' => 1234),
        'expected_count' => 1,
        'expected_contribution' => array($Contribution1['id']),
        'expected_qill' => 'Card Number Like %1234%',
      ),
      // Case 3: Search for ONLY those contributions which have Card Number as 8888
      array(
        'form_value' => array('financial_trxn_pan_truncation' => 8888),
        'expected_count' => 0,
        'expected_contribution' => array(),
        'expected_qill' => 'Card Number Like %8888%',
      ),
    );

    foreach ($useCases as $case) {
      $fv = $case['form_value'];
      CRM_Contact_BAO_Query::processSpecialFormValue($fv, array('financial_trxn_pan_truncation'));
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
   */
  public function testContributionRecurStatusFilter() {
    $this->quickCleanup($this->_tablesToTruncate);
    $contactID1 = $this->individualCreate([], 1);
    $contactID2 = $this->individualCreate([], 2);
    // "In Progress" recurring contribution for contactID1
    $ContributionRecur1 = $this->callAPISuccess('ContributionRecur', 'create', [
      'sequential' => 1,
      'contact_id' => $contactID1,
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
      'contact_id' => $contactID1,
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
      'contact_id' => $contactID2,
      'frequency_interval' => 1,
      'frequency_unit' => "month",
      'amount' => 22,
      'currency' => "CAD",
      'payment_instrument_id' => 1,
      'contribution_status_id' => 1,
      'financial_type_id' => "Donation",
    ]);
    $Contribution2 = $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => 'Donation',
      'total_amount' => 22,
      'receive_date' => date('Ymd'),
      'receive_date_time' => NULL,
      'payment_instrument' => 1,
      'contribution_status_id' => 1,
      'contact_id' => $contactID2,
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

    $useCases = [
      // Case 1: Search for ONLY those recurring contributions with status "In Progress"
      [
        'form_value' => ['contribution_recur_contribution_status_id' => 5],
        'expected_count' => 1,
        'expected_contact' => [$contactID1],
        'expected_qill' => "Recurring Contribution Status = 'In Progress'",
      ],
      // Case 2: Search for ONLY those recurring contributions with status "Completed"
      [
        'form_value' => ['contribution_recur_contribution_status_id' => 1],
        'expected_count' => 1,
        'expected_contact' => [$contactID2],
        'expected_qill' => "Recurring Contribution Status = 'Completed'",
      ],
      // Case 3: Search for ONLY those recurring contributions with status "Cancelled"
      [
        'form_value' => ['contribution_recur_contribution_status_id' => 3],
        'expected_count' => 0,
        'expected_contact' => [],
        'expected_qill' => "Recurring Contribution Status = 'Cancelled'",
      ],
    ];

    foreach ($useCases as $case) {
      $fv = $case['form_value'];
      $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($fv));
      list($select, $from, $where, $having) = $query->query();

      // get and assert contribution count
      $contacts = CRM_Core_DAO::executeQuery(sprintf('SELECT DISTINCT contact_a.id %s %s AND contact_a.id IS NOT NULL', $from, $where))->fetchAll();
      foreach ($contacts as $key => $value) {
        $contacts[$key] = $value['id'];
      }
      // assert the contribution count
      $this->assertEquals($case['expected_count'], count($contacts));
      // assert the contribution IDs
      $this->checkArrayEquals($case['expected_contact'], $contacts);
      // get and assert qill string
      $qill = trim(implode($query->getOperator(), CRM_Utils_Array::value(0, $query->qill())));
      $this->assertEquals($case['expected_qill'], $qill);
    }
  }

  /**
   *  CRM-21343: Test CRM_Contribute_Form_Search Cancelled filters
   */
  public function testCancelledFilter() {
    $this->quickCleanup($this->_tablesToTruncate);
    $contactID1 = $this->individualCreate([], 1);
    $contactID2 = $this->individualCreate([], 2);
    $Contribution1 = $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => 1,
      'total_amount' => 100,
      'receive_date' => date('Ymd'),
      'receive_date_time' => NULL,
      'payment_instrument' => 1,
      'contribution_status_id' => 3,
      'cancel_date' => date('Ymd'),
      'cancel_reason' => 'Insufficient funds',
      'contact_id' => $contactID1,
    ]);
    $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => 1,
      'total_amount' => 150,
      'receive_date' => date('Ymd', strtotime(date('Y-m-d') . ' - 1 days')),
      'receive_date_time' => NULL,
      'payment_instrument' => 1,
      'contribution_status_id' => 3,
      'cancel_date' => date('Ymd', strtotime(date('Y-m-d') . ' - 1 days')),
      'cancel_reason' => 'Insufficient funds',
      'contact_id' => $contactID2,
    ]);
    $Contribution3 = $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => 1,
      'total_amount' => 200,
      'receive_date' => date('Ymd'),
      'receive_date_time' => NULL,
      'payment_instrument' => 1,
      'contribution_status_id' => 3,
      'cancel_date' => date('Ymd'),
      'cancel_reason' => 'Invalid Credit Card Number',
      'contact_id' => $contactID1,
    ]);

    $useCases = [
      // Case 1: Search for Cancelled Date
      [
        'form_value' => ['cancel_date' => date('Y-m-d')],
        'expected_count' => 2,
        'expected_contribution' => [$Contribution1['id'], $Contribution3['id']],
        'expected_qill' => "Cancel Date Like '%" . date('Y-m-d') . "%'",
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
        'form_value' => ['cancel_date' => date('Y-m-d'), 'cancel_reason' => 'Insufficient funds'],
        'expected_count' => 1,
        'expected_contribution' => [$Contribution1['id']],
        'expected_qill' => "Cancel Date Like '%" . date('Y-m-d') . "%'ANDCancellation / Refund Reason Like '%Insufficient funds%'",
      ],
    ];

    foreach ($useCases as $case) {
      $fv = $case['form_value'];
      CRM_Contact_BAO_Query::processSpecialFormValue($fv, ['cancel_date', 'cancel_reason']);
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

}
