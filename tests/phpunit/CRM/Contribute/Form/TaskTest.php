<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * Class CRM_Contribute_Form_Tasktest
 */
class CRM_Contribute_Form_TaskTest extends CiviUnitTestCase {

  protected $_individualId;

  /**
   * Clean up after each test.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    CRM_Utils_Hook::singleton()->reset();
  }

  /**
   * CRM-19722 - Check CRM_Contribute_Form_Task::preProcessCommon()
   * executes without any error after sorting the search result.
   */
  public function testPreProcessCommonAfterSorting() {
    $fields = array(
      'source' => 'contribution_source',
      'status' => 'contribution_status',
      'financialTypes' => 'financial_type',
    );
    $financialTypes = array('Member Dues', 'Event Fee', 'Donation');
    $status = array('Completed', 'Partially paid', 'Pending');
    $source = array('test source text', 'check source text', 'source text');
    $this->_individualId = $this->individualCreate();

    for ($i = 0; $i < 3; $i++) {
      $contributionParams = array(
        'contact_id' => $this->_individualId,
        'total_amount' => 100,
        'source' => $source[$i],
        'financial_type_id' => $financialTypes[$i],
        'contribution_status_id' => $status[$i],
      );
      $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);
      $contributionIds[] = $contribution['id'];
    }

    // Generate expected sorted array.
    $expectedValues = array();
    foreach ($fields as $key => $fld) {
      $sortedFields = array_combine($$key, $contributionIds);
      ksort($sortedFields);
      $expectedValues[$fld] = $sortedFields;
    }

    // Assert contribIds are returned in a sorted order.
    $form = new CRM_Contribute_Form_Task();
    $form->controller = new CRM_Core_Controller();
    foreach ($fields as $val) {
      $form->set(CRM_Utils_Sort::SORT_ORDER, "`{$val}` asc");
      CRM_Contribute_Form_Task::preProcessCommon($form);

      $contribIds = array_filter(array_map('intval', $form->get('contributionIds')));
      $expectedValues = array_map('array_values', $expectedValues);

      $this->assertEquals(array_values($contribIds), $expectedValues[$val], "Failed asserting values for {$val}");
    }
  }

}
