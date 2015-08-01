<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class CRM_Core_BAO_FinancialTrxnTest
 */
class CRM_Core_BAO_FinancialTrxnTest extends CiviUnitTestCase {
  public function setUp() {
    parent::setUp();
  }

  /**
   * Check method create()
   */
  public function testCreate() {
    $contactId = $this->individualCreate();
    $financialTypeId = 1;
    $this->contributionCreate(array('contact_id' => $contactId), $financialTypeId);
    $params = array(
      'contribution_id' => $financialTypeId,
      'to_financial_account_id' => 1,
      'trxn_date' => 20091021184930,
      'trxn_type' => 'Debit',
      'total_amount' => 10,
      'net_amount' => 90.00,
      'currency' => 'USD',
      'payment_processor' => 'Dummy',
      'trxn_id' => 'test_01014000',
    );
    $FinancialTrxn = CRM_Core_BAO_FinancialTrxn::create($params);

    $result = $this->assertDBNotNull('CRM_Core_BAO_FinancialTrxn', $FinancialTrxn->id,
      'total_amount', 'id',
      'Database check on updated financial trxn record.'
    );

    $this->assertEquals($result, 10, 'Verify financial trxn total_amount.');
  }

}
