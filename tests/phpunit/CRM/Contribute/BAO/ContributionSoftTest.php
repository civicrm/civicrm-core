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
 * Class CRM_Contribute_BAO_ContributionTest
 * @group headless
 */
class CRM_Contribute_BAO_ContributionSoftTest extends CiviUnitTestCase {

  use CRMTraits_Financial_FinancialACLTrait;

  /**
   * Clean up after tests.
   */
  public function tearDown() : void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Creates two donor contacts and one soft creditee contact.
   * Creates a contribution for each donor contact, soft crediting the creditee.
   * The contributions have different financial types ("Donation" and "Campaign Contribution")
   * to facilitate testing ACLs.
   * @return array
   */
  protected function createTwoSoftCredits() {
    $donorId = $this->individualCreate();
    $donorId2 = $this->individualCreate();
    $crediteeId = $this->individualCreate();
    $contributionId = $this->contributionCreate(['financial_type_id' => 'Donation', 'contact_id' => $donorId]);
    $contributionId2 = $this->contributionCreate(['financial_type_id' => 'Campaign Contribution', 'contact_id' => $donorId2]);

    $params = [
      'contribution_id' => $contributionId,
      'amount' => 100,
      'contact_id' => $crediteeId,
    ];
    CRM_Contribute_BAO_ContributionSoft::add($params);

    $params2 = [
      'contribution_id' => $contributionId2,
      'amount' => 200,
      'contact_id' => $crediteeId,
    ];
    CRM_Contribute_BAO_ContributionSoft::add($params2);
    return [$donorId, $donorId2, $crediteeId, $contributionId, $contributionId2];
  }

  /**
   * Test add method
   *
   * @throws \CRM_Core_Exception
   */
  public function testAdd() {
    $donorId = $this->individualCreate();
    $contributionId = $this->contributionCreate(['receive_date' => '2018-01-02', 'contact_id' => $donorId]);
    $crediteeId = $this->individualCreate();

    $params = [
      'contribution_id' => $contributionId,
      'amount' => 100,
      'contact_id' => $crediteeId,
    ];

    $contributionSoft = CRM_Contribute_BAO_ContributionSoft::add($params);
    $this->assertEquals($params['amount'], $contributionSoft->amount);
    $this->assertEquals($crediteeId, $contributionSoft->contact_id);
  }

  /**
   * Test getSoftContributionList method.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetSoftContributionList() {
    list($donorId, $donorId2, $crediteeId, $contributionId, $contributionId2) = $this->createTwoSoftCredits();
    $list = CRM_Contribute_BAO_ContributionSoft::getSoftContributionList($crediteeId);
    $this->assertEquals('$ 100.00', $list[1]['amount']);
    $this->assertEquals('Donation', $list[1]['financial_type']);
    $this->assertEquals($donorId, $list[1]['contributor_id']);
    $this->assertEquals($contributionId, $list[1]['contribution_id']);
    $this->assertEquals('$ 200.00', $list[2]['amount']);
    $this->assertEquals('Campaign Contribution', $list[2]['financial_type']);
    $this->assertEquals($donorId2, $list[2]['contributor_id']);
    $this->assertEquals($contributionId2, $list[2]['contribution_id']);

    // And again, with ACLs enabled.
    $this->enableFinancialACLs();
    $this->setPermissions([
      'access CiviCRM',
      'access CiviContribute',
      'edit contributions',
      'view contributions of type Campaign Contribution',
    ]);

    $list = CRM_Contribute_BAO_ContributionSoft::getSoftContributionList($crediteeId);
    $this->assertArrayNotHasKey(1, $list);
    $this->assertEquals('$ 200.00', $list[2]['amount']);
    $this->assertEquals('Campaign Contribution', $list[2]['financial_type']);
    $this->assertEquals($donorId2, $list[2]['contributor_id']);
    $this->assertEquals($contributionId2, $list[2]['contribution_id']);
  }

  /**
   * Test getSoftContributionTotals method.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetSoftContributionTotals() {
    list($donorId, $donorId2, $crediteeId, $contributionId, $contributionId2) = $this->createTwoSoftCredits();
    $totals = CRM_Contribute_BAO_ContributionSoft::getSoftContributionTotals($crediteeId);
    $this->assertEquals('$ 300.00', $totals[2], 'test total of completed soft credits');
    $this->assertEquals('$ 150.00', $totals[3], 'test average of completed soft credits');
    $this->assertEquals('', $totals[4], 'test count of cancelled soft credits');

    // And again, with ACLs enabled.
    $this->enableFinancialACLs();
    $this->setPermissions([
      'access CiviCRM',
      'access CiviContribute',
      'edit contributions',
      'view contributions of type Campaign Contribution',
    ]);

    $totals = CRM_Contribute_BAO_ContributionSoft::getSoftContributionTotals($crediteeId);
    $this->assertEquals('$ 200.00', $totals[2], 'test total of completed soft credits');
    $this->assertEquals('$ 200.00', $totals[3], 'test average of completed soft credits');
    $this->assertEquals('', $totals[4], 'test count of cancelled soft credits');
  }

}
