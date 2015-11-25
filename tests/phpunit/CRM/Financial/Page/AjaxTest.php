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
 * Test for CRM_Financial_Page_Ajax class.
 */
class CRM_Financial_Page_AjaxTest extends CiviUnitTestCase {

  /**
   * Test the ajax function to get financial transactions.
   *
   * Test focus is on ensuring changes to how labels are retrieved does not cause regression.
   */
  public function testGetFinancialTransactionsList() {
    $individualID = $this->individualCreate();
    $this->contributionCreate(array('contact_id' => $individualID));
    $batch = $this->callAPISuccess('Batch', 'create', array('title' => 'test', 'status_id' => 'Open'));
    CRM_Core_DAO::executeQuery("
     INSERT INTO civicrm_entity_batch (entity_table, entity_id, batch_id)
     values('civicrm_financial_trxn', 1, 1)
   ");
    $_REQUEST['sEcho'] = 1;
    $_REQUEST['entityID'] = $batch['id'];
    $_REQUEST['return'] = TRUE;
    $json = CRM_Financial_Page_AJAX::getFinancialTransactionsList();
    $this->assertEquals($json, '{"sEcho": 1, "iTotalRecords": 1, "iTotalDisplayRecords": 1, "aaData": [ ["","<a href=\"/index.php?q=civicrm/profile/view&amp;reset=1&amp;gid=7&amp;id=3&amp;snippet=4\" class=\"crm-summary-link\"><div'
    . ' class=\"icon crm-icon Individual-icon\"></div></a>","<a href=/index.php?q=civicrm/contact/view&amp;reset=1&amp;cid=3>Anderson, Anthony</a>","$ 100.00","12345","' . CRM_Utils_Date::customFormat(date('Ymd')) . ' 12:00 AM",'
    . '"Credit Card","Completed","Donation","<span><a href=\"http://FIX ME/index.php?q=civicrm/contact/view/contribution&amp;reset=1&amp;id=1&amp;cid=3&amp;action=view&amp;context=contribution&amp;'
    . 'selectedChild=contribute\" class=\"action-item crm-hover-button\" title=\'View Contribution\' >View</a></span>"]] }');
  }

}
