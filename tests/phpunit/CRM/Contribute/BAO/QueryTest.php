<?php

/**
 *  Include dataProvider for tests
 * @group headless
 */
class CRM_Contribute_BAO_QueryTest extends CiviUnitTestCase {

  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
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

}
