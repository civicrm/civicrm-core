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

use Civi\Api4\Contribution;

/**
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class CRM_Contribute_Form_Task_BatchTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(['civicrm_uf_field', 'civicrm_uf_group']);
    parent::tearDown();
  }

  /**
   * Test batch submission.
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchSubmit(): void {
    $this->createTestEntity('Contribution', [
      'contact_id' => $this->individualCreate(),
      'contribution_status_id:name' => 'Pending',
      'total_amount' => 100,
      'financial_type_id:name' => 'Donation',
    ]);
    $this->createTestEntity('UFGroup', [
      'name' => 'contribution',
      'extends' => 'Contribution',
    ]);
    $this->createTestEntity('UFField', [
      'uf_group_id.name' => 'contribution',
      'name' => 'contribution_status_id',
      'field_name' => 'contribution_status_id',
    ]);
    $this->getTestForm('CRM_Contact_Form_Search_Basic', [
      'radio_ts' => 'ts_all',
    ], ['action' => 1])
      ->addSubsequentForm('CRM_Contribute_Form_Task_PickProfile', ['uf_group_id' => $this->ids['UFGroup']['default']])
      ->addSubsequentForm('CRM_Contribute_Form_Task_Batch', [
        'field' => [$this->ids['Contribution']['default'] => ['contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed')]],
      ])
      ->processForm();
    $contribution = Contribution::get(FALSE)->addWhere('id', '=', $this->ids['Contribution']['default'])
      ->addSelect('contribution_status_id:name')
      ->execute()->single();
    $this->assertEquals('Completed', $contribution['contribution_status_id:name']);
  }

}
