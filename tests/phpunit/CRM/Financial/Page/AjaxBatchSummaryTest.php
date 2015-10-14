<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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

class CRM_Financial_Page_AjaxBatchSummaryTest extends CiviUnitTestCase {

    public function testMakeBatchSummary() {
    	$batch = $this->callAPISuccess('Batch', 'create', array('title' => 'test', 'status_id' => 'Open'));
    	$batchID = $batch['id'];
    	$params = array('id' => $batchID);

    	$test = array(
    			'status' => 'some status', // how do i extract the batch's status?
    			'payment_instrument' => 'some instrument', // how do i extract the batch's payment instrument?
    		);

    	$makeBatchSummary = CRM_Financial_Page_AJAX::makeBatchSummary($batchID,$params);

    	$this->assertEquals($test['status'], $makeBatchSummary['status']);
    	$this->assertEquals($test['payment_instrument'], $makeBatchSummary['payment_instrument']);
    }
}