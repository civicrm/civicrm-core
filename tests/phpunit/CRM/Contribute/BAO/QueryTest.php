<?php

/**
 *  Include dataProvider for tests
 *
 * @group headless
 */
class CRM_Contribute_BAO_QueryTest extends CiviUnitTestCase {

  public function tearDown(): void {
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
      $this->assertStringContainsString('field(', $sql);
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
   * Searching contributions by membership should follow the line items.
   */
  public function testContributionMembershipSearchUsesLineItems(): void {
    // The second line item added below is deliberately not reflected in the
    // financial records.
    $this->isValidateFinancialsOnPostAssert = FALSE;
    $contactID = $this->individualCreate();
    $membershipID = $this->contactMembershipCreate(['contact_id' => $contactID]);
    $contributionID = $this->contributionCreate([
      'contact_id' => $contactID,
      'financial_type_id' => 'Member Dues',
    ]);
    CRM_Core_DAO::executeQuery(
      "UPDATE civicrm_line_item SET entity_table = 'civicrm_membership', entity_id = %1 WHERE contribution_id = %2",
      [1 => [$membershipID, 'Integer'], 2 => [$contributionID, 'Integer']]
    );
    // A site that no longer writes the legacy records is linked by line item alone.
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_membership_payment WHERE contribution_id = %1', [1 => [$contributionID, 'Integer']]);

    $this->assertEquals([$contributionID], $this->searchContributionsByMembership($membershipID));

    // A membership can hold more than one line item on the same contribution,
    // which must not duplicate the contribution in the results.
    CRM_Core_DAO::executeQuery(
      "INSERT INTO civicrm_line_item (entity_table, entity_id, contribution_id, label, qty, unit_price, line_total, financial_type_id)
       SELECT entity_table, entity_id, contribution_id, label, qty, unit_price, line_total, financial_type_id
       FROM civicrm_line_item WHERE contribution_id = %1",
      [1 => [$contributionID, 'Integer']]
    );

    $this->assertEquals([$contributionID], $this->searchContributionsByMembership($membershipID));

    CRM_Core_DAO::reenableFullGroupByMode();
  }

  /**
   * Returns the contributions the contribution search finds for a membership.
   */
  private function searchContributionsByMembership(int $membershipID): array {
    $queryObj = new CRM_Contact_BAO_Query([['contribution_membership_id', '=', $membershipID, 0, 0]], NULL, NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CONTRIBUTE);
    // Mirrors what CRM_Contribute_Selector_Search sets up.
    $queryObj->_distinctComponentClause = ' civicrm_contribution.id';
    $queryObj->_groupByComponentClause = ' GROUP BY civicrm_contribution.id ';
    $dao = CRM_Core_DAO::executeQuery($queryObj->getSearchSQL());
    $found = [];
    while ($dao->fetch()) {
      $found[] = (int) $dao->contribution_id;
    }
    return $found;
  }

  /**
   * Data provider for sort fields
   */
  public static function getSortFields() {
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
  public function testRelativeContributionDates(): void {
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

  public function testContributionWithoutSoftCredits(): void {
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
