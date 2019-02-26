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
   *
   * @dataProvider getSortFields
   */
  public function testSearchPseudoReturnProperties($sort) {
    $contactID = $this->individualCreate();
    $this->contributionCreate(['contact_id' => $contactID, 'financial_type_id' => 'Campaign Contribution']);
    $this->contributionCreate(['contact_id' => $contactID, 'financial_type_id' => 'Donation']);
    $donationTypeID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation');

    $params = [
      ['financial_type_id', '=', $donationTypeID , 1, 0],
    ];

    $queryObj = new CRM_Contact_BAO_Query($params);
    try {
      $resultDAO = $queryObj->searchQuery(0, 0, $sort . ' asc');
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
      ['payment_instrument'],
      ['individual_prefix'],
      ['communication_style'],
      ['gender'],
      ['state_province'],
      ['country'],
    ];
  }

}
