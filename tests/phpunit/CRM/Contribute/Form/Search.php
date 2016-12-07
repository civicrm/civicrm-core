<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *  Include parent class definition
 */

/**
 *  Test Contribution Search form filters
 *
 * @package CiviCRM
 */
class CRM_Contribute_Form_Search extends CiviUnitTestCase {

  protected $_individual;
  protected $_tablesToTruncate = array('civicrm_contribution');

  public function setUp() {
    $this->_individual = $this->individualCreate();
    parent::setUp();
  }

  public function tearDown() {
  }

  /**
   *  Test CRM_Contribute_Form_Search batch filters
   */
  public function testBatchFilter() {
    $this->quickCleanup($this->_tablesToTruncate);
    $contactID1 = $this->individualCreate(array(), 1);
    $contactID2 = $this->individualCreate(array(), 2);
    $batchTitle = CRM_Batch_BAO_Batch::generateBatchName();

    // create batch
    $batch = civicrm_api3('Batch', 'create', array(
      'created_id' => $this->_individual,
      'created_date' => CRM_Utils_Date::processDate(date("Y-m-d"), date("H:i:s")),
      'status_id' => CRM_Core_OptionGroup::getValue('batch_status', 'Data Entry', 'name'),
      'title' => $batchTitle,
      'item_count' => 2,
      'total' => 100,
      'type_id' => array_search('Contribution', CRM_Batch_BAO_Batch::buildOptions('type_id')),
    ));
    $batchID = $batch['id'];

    $batchEntry = array(
      'primary_profiles' => array(1 => NULL, 2 => NULL, 3 => NULL),
      'primary_contact_id' => array(
        1 => $contactID1,
        2 => $contactID2,
      ),
      'field' => array(
        1 => array(
          'financial_type' => 1,
          'total_amount' => 70,
          'receive_date' => '07/24/2013',
          'receive_date_time' => NULL,
          'payment_instrument' => 1,
          'check_number' => NULL,
          'contribution_status_id' => 1,
        ),
        2 => array(
          'financial_type' => 1,
          'total_amount' => 30,
          'receive_date' => '07/24/2013',
          'receive_date_time' => NULL,
          'payment_instrument' => 1,
          'check_number' => NULL,
          'contribution_status_id' => 1,
        ),
      ),
      'actualBatchTotal' => 100,
    );

    // create random contribution to check IS NULL filter more precisely
    $randomContribution = civicrm_api3('Contribution', 'create', array(
      'financial_type_id' => 1,
      'total_amount' => 123,
      'receive_date' => '07/24/2014',
      'receive_date_time' => NULL,
      'payment_instrument' => 1,
      'check_number' => NULL,
      'contribution_status_id' => 1,
      'contact_id' => $this->_individual,
    ));

    // process batch entries
    $form = new CRM_Batch_Form_Entry();
    $form->setBatchID($batchID);
    $form->testProcessContribution($batchEntry);

    $useCases = array(
      // Case 1: Search for ONLY those contributions which are created from batch
      array(
        'form_value' => array('contribution_batch_id' => 'IS NOT NULL'),
        'expected_count' => 2,
        'expected_qill' => 'Batch Name Not Null',
      ),
      // Case 2: Search for ONLY those contributions which are NOT created from batch
      array(
        'form_value' => array('contribution_batch_id' => 'IS NULL'),
        'expected_count' => 1,
        'expected_qill' => 'Batch Name Is Null',
      ),
      // Case 3: Search for ONLY those contributions which are created from batch ID - $batchID
      array(
        'form_value' => array('contribution_batch_id' => $batchID),
        'expected_count' => 2,
        'expected_qill' => 'Batch Name = ' . $batchTitle,
      ),
    );
    foreach ($useCases as $case) {
      $fv = $case['form_value'];
      CRM_Contact_BAO_Query::processSpecialFormValue($fv, array('contribution_batch_id'));
      $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($fv));
      list($select, $from, $where, $having) = $query->query();

      // get and assert contribution count
      $count = CRM_Core_DAO::singleValueQuery(sprintf('SELECT count(*) %s %s AND civicrm_contribution.id IS NOT NULL', $from, $where));
      $this->assertEquals($case['expected_count'], $count);

      // get and assert qill string
      $qill = trim(implode($query->getOperator(), CRM_Utils_Array::value(0, $query->qill())));
      $this->assertEquals($case['expected_qill'], $qill);
    }
  }

}
