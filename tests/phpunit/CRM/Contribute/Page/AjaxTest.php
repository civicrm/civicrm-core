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

  protected $_params = [];

  public function setUp() {
    parent::setUp();

    $this->_fields = ['amount', 'sct_label'];

    $this->_params = [
      'page' => 1,
      'rp' => 50,
      'offset' => 0,
      'rowCount' => 50,
      'sort' => NULL,
      'is_unit_test' => TRUE,
    ];
    $softContactParams = [
      'first_name' => 'soft',
      'last_name' => 'Contact',
    ];
    $this->_softContactId = $this->individualCreate($softContactParams);

    //create three sample contacts
    foreach ([0, 1, 2] as $seq) {
      $this->_primaryContacts[] = $this->individualCreate([], $seq);
    }
  }

  /**
   * Test retrieve Soft Contribution through AJAX
   */
  public function testGetSoftContributionSelector() {
    $softTypes = [3, 2, 5];
    $amounts = ['100', '600', '150'];

    // create sample soft contribution for contact
    foreach ($this->_primaryContacts as $seq => $contactId) {
      $this->callAPISuccess('Contribution', 'create', [
        'contact_id' => $contactId,
        'receive_date' => date('Ymd'),
        'total_amount' => $amounts[$seq],
        'financial_type_id' => 1,
        'non_deductible_amount' => '10',
        'contribution_status_id' => 1,
        'soft_credit' => [
          '1' => [
            'contact_id' => $this->_softContactId,
            'amount' => $amounts[$seq],
            'soft_credit_type_id' => $softTypes[$seq],
          ],
        ],
      ]);
    }

    $_GET = array_merge($this->_params,
      [
        'cid' => $this->_softContactId,
        'context' => 'contribution',
      ]
    );
    $softCreditList = CRM_Contribute_Page_AJAX::getSoftContributionRows();

    foreach ($this->_fields as $columnName) {
      $_GET['columns'][] = [
        'data' => $columnName,
      ];
    }
    // get the results in descending order
    $_GET['order'] = [
      '0' => [
        'column' => 0,
        'dir' => 'desc',
      ],
    ];
    $amountSortedList = CRM_Contribute_Page_AJAX::getSoftContributionRows();

    $this->assertEquals(3, $softCreditList['recordsTotal']);
    $this->assertEquals(3, $amountSortedList['recordsTotal']);
    rsort($amounts);
    foreach ($amounts as $key => $amount) {
      $amount = CRM_Utils_Money::format($amount, 'USD');
      $this->assertEquals($amount, $amountSortedList['data'][$key]['amount']);
    }

    // sort with soft credit types
    $_GET['order'][0]['column'] = 1;
    foreach ($softTypes as $id) {
      $softLabels[] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_ContributionSoft', 'soft_credit_type_id', $id);
    }
    rsort($softLabels);
    $softTypeSortedList = CRM_Contribute_Page_AJAX::getSoftContributionRows();
    foreach ($softLabels as $key => $labels) {
      $this->assertEquals($labels, $softTypeSortedList['data'][$key]['sct_label']);
    }
  }

  /**
   * Test retrieve Soft Contribution For Membership
   */
  public function testGetSoftContributionForMembership() {
    //Check soft credit for membership
    $memParams = [
      'contribution_contact_id' => $this->_primaryContacts[0],
      'contact_id' => $this->_softContactId,
      'contribution_status_id' => 1,
      'financial_type_id' => 2,
      'status_id' => 1,
      'total_amount' => 100,
      'receive_date' => '2018-06-08',
      'soft_credit' => [
        'soft_credit_type_id' => 11,
        'contact_id' => $this->_softContactId,
      ],
    ];
    $_GET = array_merge($this->_params,
      [
        'cid' => $this->_softContactId,
        'context' => 'membership',
        'entityID' => $this->contactMembershipCreate($memParams),
      ]
    );

    $softCreditList = CRM_Contribute_Page_AJAX::getSoftContributionRows();
    $this->assertEquals(1, $softCreditList['recordsTotal']);
    $this->assertEquals('Gift', $softCreditList['data'][0]['sct_label']);
    $this->assertEquals('$ 100.00', $softCreditList['data'][0]['amount']);
    $this->assertEquals('Member Dues', $softCreditList['data'][0]['financial_type']);
  }

}
