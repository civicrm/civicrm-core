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
namespace Civi\Financialacls;

// I fought the Autoloader and the autoloader won.
use CRM_Core_Exception;
use Civi\Api4\Contribution;
use Civi\Api4\ContributionSoft;
use CRM_Contribute_BAO_ContributionSoft;
use CRM_Core_DAO;
use CRM_Utils_System;

require_once 'BaseTestClass.php';

/**
 * Class CRM_Contribute_BAO_ContributionTest
 * @group headless
 */
class ContributionSoftTest extends BaseTestClass {

  /**
   * Test getSoftContributionList method.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetSoftContributionList(): void {
    $this->createTwoSoftCredits();
    $expectedCredits = [
      1 => [
        'amount' => '$100.00',
        'currency' => 'USD',
        'contributor_id' => $this->ids['Contact'][0],
        'contribution_id' => $this->ids['Contribution']['permitted'],
        'contributor_name' => CRM_Utils_System::href(
          'Mr. Anthony Anderson II',
          'civicrm/contact/view',
          'reset=1&cid=' . $this->ids['Contact'][0]
        ),
        'financial_type' => 'Donation',
        'pcp_id' => NULL,
        'pcp_title' => 'n/a',
        'pcp_display_in_roll' => FALSE,
        'pcp_roll_nickname' => NULL,
        'pcp_personal_note' => NULL,
        'contribution_status' => 'Completed',
        'sct_label' => NULL,
      ],
      2 => [
        'amount' => '$200.00',
        'currency' => 'USD',
        'contributor_id' => $this->ids['Contact'][1],
        'contribution_id' => $this->ids['Contribution'][1],
        'contributor_name' => CRM_Utils_System::href(
          'Mr. Anthony Anderson II',
          'civicrm/contact/view',
          'reset=1&cid=' . $this->ids['Contact'][1]
        ),
        'financial_type' => 'Campaign Contribution',
        'pcp_id' => NULL,
        'pcp_title' => 'n/a',
        'pcp_display_in_roll' => FALSE,
        'pcp_roll_nickname' => NULL,
        'pcp_personal_note' => NULL,
        'contribution_status' => 'Completed',
        'sct_label' => NULL,
      ],
    ];
    $dataTableParameters = [];
    $list = CRM_Contribute_BAO_ContributionSoft::getSoftContributionList($this->ids['Contact']['credited'], NULL, 0, $dataTableParameters);
    $this->assertEquals(2, $dataTableParameters['total']);
    foreach ($expectedCredits[1] as $key => $value) {
      $this->assertEquals($value, $list[1][$key], $key);
    }
    foreach ($expectedCredits[2] as $key => $value) {
      $this->assertEquals($value, $list[2][$key], $key);
    }
    $this->assertEquals($this->ids['Contribution']['permitted'], $list[1]['contribution_id']);

    // And with ACL restrictions.
    $this->setupLoggedInUserWithLimitedFinancialTypeAccess();
    $list = CRM_Contribute_BAO_ContributionSoft::getSoftContributionList($this->ids['Contact']['credited']);
    $this->assertArrayNotHasKey(2, $list);
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS  ac');
    CRM_Core_DAO::executeQuery('CREATE TABLE ac SELECT id FROM civicrm_relationship');
    foreach ($expectedCredits[1] as $key => $value) {
      $this->assertEquals($value, $list[1][$key], $key);
    }
  }

  /**
   * Test getSoftContributionTotals method.
   *
   */
  public function testGetSoftContributionTotals(): void {
    $this->createTwoSoftCredits();
    $totals = CRM_Contribute_BAO_ContributionSoft::getSoftContributionTotals($this->ids['Contact']['credited']);
    $this->assertEquals('$ 300.00', $totals[2], 'test total of completed soft credits');
    $this->assertEquals('$ 150.00', $totals[3], 'test average of completed soft credits');
    $this->assertEquals('', $totals[4], 'test count of cancelled soft credits');

    // And again, with ACLs enabled.
    $this->setupLoggedInUserWithLimitedFinancialTypeAccess();

    $totals = CRM_Contribute_BAO_ContributionSoft::getSoftContributionTotals($this->ids['Contact']['credited']);
    $this->assertEquals('$ 100.00', $totals[2], 'test total of completed soft credits');
    $this->assertEquals('$ 100.00', $totals[3], 'test average of completed soft credits');
    $this->assertEquals('', $totals[4], 'test count of cancelled soft credits');
  }

  /**
   * Creates two donor contacts and one soft credited contact.
   *
   * Creates a contribution for each donor contact, soft crediting the credited.
   * The contributions have different financial types ("Donation" and "Campaign Contribution")
   * to facilitate testing ACLs.
   *
   * @return array
   */
  protected function createTwoSoftCredits(): array {
    try {
      $this->ids['Contact'][0] = $this->individualCreate();
      $this->ids['Contact'][1] = $this->individualCreate();
      $this->ids['Contact']['credited'] = $this->individualCreate();
      $this->ids['Contribution']['permitted'] = $this->contributionCreate([
        'financial_type_id:name' => 'Donation',
        'contact_id' => $this->ids['Contact'][0],
      ]);
      $this->ids['Contribution'][1] = $this->contributionCreate([
        'financial_type_id:name' => 'Campaign Contribution',
        'contact_id' => $this->ids['Contact'][1],
        'total_amount' => 100,
      ]);

      $params = [
        'contribution_id' => $this->ids['Contribution']['permitted'],
        'amount' => 100,
        'contact_id' => $this->ids['Contact']['credited'],
      ];
      ContributionSoft::create(FALSE)->setValues($params)->execute();
      $params2 = [
        'contribution_id' => $this->ids['Contribution'][1],
        'amount' => 200,
        'contact_id' => $this->ids['Contact']['credited'],
      ];
      ContributionSoft::create(FALSE)->setValues($params2)->execute();
    }
    catch (CRM_Core_Exception $e) {
      $this->fail($e->getMessage());
    }
    return [];
  }

  /**
   * Create a contribution with some useful defaults.
   *
   * @param array $params
   *
   * @return int
   * @throws \CRM_Core_Exception
   */
  private function contributionCreate(array $params): int {
    return Contribution::create(FALSE)->setValues(array_merge([
      'receive_date' => 'now',
      'total_amount' => 150.00,
      'payment_instrument_id' => 1,
      'contribution_status_id' => 1,
    ], $params))->execute()->first()['id'];
  }

}
