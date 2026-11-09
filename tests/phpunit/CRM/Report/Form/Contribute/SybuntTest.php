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
 * @group headless
 */
class CRM_Report_Form_Contribute_SybuntTest extends CiviReportTestCase {

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  public function testListsEveryDonorWhenAllFitOnOnePage(): void {
    $lastYear = (int) date('Y') - 1;
    $donorIDs = [];
    foreach (['a', 'b', 'c'] as $identifier) {
      $donorIDs[] = $this->createTestEntity('Contact', ['contact_type' => 'Individual', 'first_name' => 'Donor ' . $identifier], $identifier)['id'];
      $this->createTestEntity('Contribution', [
        'contact_id' => end($donorIDs),
        'financial_type_id:name' => 'Donation',
        'total_amount' => 10,
        'receive_date' => $lastYear . '-03-01',
      ], $identifier);
    }

    $report = $this->getReportObject('CRM_Report_Form_Contribute_Sybunt', [
      'fields' => ['sort_name'],
      'filters' => ['yid_op' => 'calendar', 'yid_value' => $lastYear + 1],
    ]);

    $this->assertEqualsCanonicalizing($donorIDs, array_keys($report->getResultSet()));
    $this->assertNull($report::getTemplate()->getTemplateVars('grandStat'));
  }

}
