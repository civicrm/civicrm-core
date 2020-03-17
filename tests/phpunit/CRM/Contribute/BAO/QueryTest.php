<?php

/**
 *  Include dataProvider for tests
 *
 * @group headless
 */
class CRM_Contribute_BAO_QueryTest extends CiviUnitTestCase {

  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Check that we get a successful trying to return by pseudo-fields
   *  - financial_type.
   *
   * @param string $sort
   * @param bool $isUseKeySort
   *   Does the order by use a key sort. A key sort uses the mysql 'field' function to
   *   order by a passed in list. It makes sense for option groups & small sets
   *   but may not do for long lists like states - performance testing not done on that yet.
   *
   * @throws \CRM_Core_Exception
   *
   * @dataProvider getSortFields
   */
  public function testSearchPseudoReturnProperties($sort, $isUseKeySort) {
    $contactID = $this->individualCreate();
    $this->contributionCreate(['contact_id' => $contactID, 'financial_type_id' => 'Campaign Contribution']);
    $this->contributionCreate(['contact_id' => $contactID, 'financial_type_id' => 'Donation']);
    $donationTypeID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation');

    $params = [
      ['financial_type_id', '=', $donationTypeID , 1, 0],
    ];

    $queryObj = new CRM_Contact_BAO_Query($params);
    $sql = $queryObj->getSearchSQL(0, 0, $sort . ' asc');
    if ($isUseKeySort) {
      $this->assertContains('field(', $sql);
    }
    try {
      $resultDAO = CRM_Core_DAO::executeQuery($sql);
      $this->assertTrue($resultDAO->fetch());
      $this->assertEquals(1, $resultDAO->N);
    }
    catch (PEAR_Exception $e) {
      $err = $e->getCause();
      $this->fail('invalid SQL created' . $e->getMessage() . " " . $err->userinfo);

    }
  }

  /**
   * Data provider for sort fields
   */
  public function getSortFields() {
    return [
      ['financial_type', TRUE],
      ['payment_instrument', TRUE],
      ['individual_prefix', TRUE],
      ['communication_style', TRUE],
      ['gender', TRUE],
      ['state_province', FALSE],
      ['country', FALSE],
    ];
  }

  /**
   * Test receive_date_high, low & relative work.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRelativeContributionDates() {
    $contribution1 = $this->contributionCreate(['receive_date' => '2018-01-02', 'contact_id' => $this->individualCreate()]);
    $contribution2 = $this->contributionCreate(['receive_date' => '2017-01-02', 'contact_id' => $this->individualCreate()]);
    $queryObj = new CRM_Contact_BAO_Query([['receive_date_low', '=', 20170101, 1, 0]]);
    $this->assertEquals(2, $queryObj->searchQuery(0, 0, NULL, TRUE));
    $queryObj = new CRM_Contact_BAO_Query([['receive_date_low', '=', 20180101, 1, 0]]);
    $this->assertEquals(1, $queryObj->searchQuery(0, 0, NULL, TRUE));
    $queryObj = new CRM_Contact_BAO_Query([['receive_date_high', '=', 20180101, 1, 0]]);
    $this->assertEquals(1, $queryObj->searchQuery(0, 0, NULL, TRUE));
    $this->callAPISuccess('Contribution', 'delete', ['id' => $contribution1]);
    $this->callAPISuccess('Contribution', 'delete', ['id' => $contribution2]);
  }

  public function testContributionWithoutSoftCredits() {
    $contribution1 = $this->contributionCreate(['receive_date' => '2018-01-02', 'contact_id' => $this->individualCreate()]);
    $contact2 = $this->callAPISuccess('Contact', 'create', [
      'display_name' => 'superman',
      'contact_type' => 'Individual',
    ]);
    $contribution2 = $this->contributionCreate([
      'receive_date' => '2017-01-02',
      'contact_id' => $this->individualCreate(),
      'honor_contact_id' => $contact2['id'],
    ]);
    $queryObj = new CRM_Contact_BAO_Query([['contribution_or_softcredits', '=', 'only_contribs_unsoftcredited', 1, 0]], NULL, NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CONTRIBUTE);
    $this->assertEquals(1, $queryObj->searchQuery(0, 0, NULL, TRUE));
    $this->assertContains('contribution_search_scredit_combined.filter_id IS NULL', $queryObj->_where[1]);
    $queryObj = new CRM_Contact_BAO_Query([['contribution_or_softcredits', '=', 'only_scredits', 1, 0]], NULL, NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CONTRIBUTE);
    $this->assertEquals(1, $queryObj->searchQuery(0, 0, NULL, TRUE));
    $this->assertContains('contribution_search_scredit_combined.scredit_id IS NOT NULL', $queryObj->_where[1]);
    $queryObj = new CRM_Contact_BAO_Query([['contribution_or_softcredits', '=', 'both_related', 1, 0]], NULL, NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CONTRIBUTE);
    $this->assertEquals(2, $queryObj->searchQuery(0, 0, NULL, TRUE));
    $this->assertContains('contribution_search_scredit_combined.filter_id IS NOT NULL', $queryObj->_where[1]);
    $queryObj = new CRM_Contact_BAO_Query([['contribution_or_softcredits', '=', 'both', 1, 0]], NULL, NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CONTRIBUTE);
    $this->assertEquals(3, $queryObj->searchQuery(0, 0, NULL, TRUE));
    $this->assertEmpty($queryObj->_where[0]);
    $this->callAPISuccess('Contribution', 'delete', ['id' => $contribution1]);
    $this->callAPISuccess('Contribution', 'delete', ['id' => $contribution2]);
  }

}
