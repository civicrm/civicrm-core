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
 * Test class for CRM_Pledge_BAO_PledgeBlock BAO
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Pledge_BAO_PledgeBlockTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp(): void {
    parent::setUp();
    $contributionPage = $this->contributionPageCreate();
    $this->_contributionPageId = $contributionPage['id'];
  }

  /**
   *  create() and deletepledgeblock() method
   */
  public function testCreateAndDeletePledgeBlock() {

    $pledgeFrequencyUnit = [
      'week' => 1,
      'month' => 1,
      'year' => 1,
    ];

    $params = [
      'entity_id' => $this->_contributionPageId,
      'entity_table' => 'civicrm_contribution_page',
      'pledge_frequency_unit' => $pledgeFrequencyUnit,
      'max_reminders' => 2,
      'initial_reminder_day' => 2,
      'additional_reminder_day' => 1,
    ];

    //Checking for pledgeBlock id in the Pledge_block table.
    $pledgeBlock = CRM_Pledge_BAO_PledgeBlock::create($params);
    $this->assertDBNotNull('CRM_Pledge_DAO_PledgeBlock', $pledgeBlock->id, 'id',
      'id', 'Check DB for Pledge block id'
    );

    //Checking for pledgeBlock id after delete.
    CRM_Pledge_BAO_PledgeBlock::deletePledgeBlock($pledgeBlock->id);
    $this->assertDBNull('CRM_Pledge_DAO_PledgeBlock', $pledgeBlock->id, 'id',
      'id', 'Check DB for Pledge block id'
    );
  }

  /**
   * Add() method (add and edit modes of pledge block)
   */
  public function testAddPledgeBlock() {

    $pledgeFrequencyUnit = [
      'week' => 1,
      'month' => 0,
      'year' => 1,
    ];
    $pledgeFrequencySerialized = implode(CRM_Core_DAO::VALUE_SEPARATOR, array_keys(array_filter($pledgeFrequencyUnit)));

    $params = [
      'entity_id' => $this->_contributionPageId,
      'entity_table' => 'civicrm_contribution_page',
      'pledge_frequency_unit' => $pledgeFrequencyUnit,
      'max_reminders' => 2,
      'initial_reminder_day' => 2,
      'additional_reminder_day' => 1,
    ];

    // check for add pledge block
    $pledgeBlock = CRM_Pledge_BAO_PledgeBlock::add($params);
    // This param is expected to get serialized
    $params['pledge_frequency_unit'] = $pledgeFrequencySerialized;
    foreach ($params as $param => $value) {
      $this->assertEquals($value, $pledgeBlock->$param);
    }

    $params = [
      'id' => $pledgeBlock->id,
      'entity_id' => $this->_contributionPageId,
      'entity_table' => 'civicrm_contribution_page',
      'pledge_frequency_unit' => $pledgeFrequencyUnit,
      'max_reminders' => 3,
      'initial_reminder_day' => 3,
      'additional_reminder_day' => 2,
      'is_pledge_interval' => 1,
    ];

    // also check for edit pledge block
    $pledgeBlock = CRM_Pledge_BAO_PledgeBlock::add($params);
    // This param is expected to get serialized
    $params['pledge_frequency_unit'] = $pledgeFrequencySerialized;
    foreach ($params as $param => $value) {
      $this->assertEquals($value, $pledgeBlock->$param);
    }
  }

  /**
   * Retrieve() and getPledgeBlock() method of  pledge block
   */
  public function testRetrieveAndGetPledgeBlock() {

    $pledgeFrequencyUnit = [
      'week' => 1,
      'month' => 1,
      'year' => 1,
    ];
    $pledgeFrequencySerialized = implode(CRM_Core_DAO::VALUE_SEPARATOR, array_keys(array_filter($pledgeFrequencyUnit)));

    $params = [
      'entity_id' => $this->_contributionPageId,
      'entity_table' => 'civicrm_contribution_page',
      'pledge_frequency_unit' => $pledgeFrequencyUnit,
      'max_reminders' => 2,
      'initial_reminder_day' => 2,
      'additional_reminder_day' => 1,
    ];

    $pledgeBlock = CRM_Pledge_BAO_PledgeBlock::create($params);

    // use retrieve() method
    $retrieveParams = [
      'entity_id' => $this->_contributionPageId,
      'entity_table' => 'civicrm_contribution_page',
    ];
    $default = [];
    $retrievePledgeBlock = CRM_Pledge_BAO_PledgeBlock::retrieve($retrieveParams, $default);

    // use getPledgeBlock() method
    $getPledgeBlock = CRM_Pledge_BAO_PledgeBlock::getPledgeBlock($this->_contributionPageId);

    // This param is expected to get serialized
    $params['pledge_frequency_unit'] = $pledgeFrequencySerialized;
    // check on both retrieve and getPledgeBlock values
    foreach ($params as $param => $value) {
      $this->assertEquals($value, $retrievePledgeBlock->$param);
      $this->assertEquals($value, $getPledgeBlock[$param]);
    }

    // Also check for pledgeBlock id.
    $this->assertEquals($pledgeBlock->id, $retrievePledgeBlock->id);
    $this->assertEquals($pledgeBlock->id, $getPledgeBlock['id']);
  }

}
