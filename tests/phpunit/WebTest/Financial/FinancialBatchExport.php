<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/


require_once 'CiviTest/CiviSeleniumTestCase.php';
class WebTest_Financial_FinancialBatchExport extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testAddFinancialBatch() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Log in using webtestLogin() method
    $this->webtestLogin(TRUE);
    $this->open($this->sboxPath . 'civicrm/financial/batch?reset=1&action=add');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_FinancialBatch_next-botttom');
    $setTitle = 'Batch ' . substr(sha1(rand()), 0, 7) . date('Y-m-d');
    $setDescription  = 'Test Batch Creation';
    $setPaymentInstrument = 'Credit Card';
    $numberOfTrxn = '10'; // can be 10, 25, 50, 100
    $totalAmt = '1000';
    $exportFormat = 'CSV';
    $batchId = $this->_testAddBatch(
      $setTitle,
      $setDescription,
      $setPaymentInstrument,
      $numberOfTrxn,
      $totalAmt
    );
    $this->_testAssignBatch($numberOfTrxn);
    $this->_testExportBatch($setTitle, $batchId, $exportFormat);
  }

  function _testAddBatch($setTitle, $setDescription, $setPaymentInstrument, $numberOfTrxn, $totalAmt) {
    // Enter Optional Constraints
    $this->type('title', $setTitle);
    $this->type('description', $setDescription);
    if ($setPaymentInstrument == 'Credit Card') {
      $this->select("payment_instrument_id", "value=1");
    }
    elseif ($setPaymentInstrument == 'Debit Card') {
      $this->select("payment_instrument_id", "value=2");
    }
    elseif ($setPaymentInstrument == 'Cash') {
      $this->select("payment_instrument_id", "value=3");
    }
    elseif ($setPaymentInstrument == 'Check') {
      $this->select("payment_instrument_id", "value=4");
    }
    elseif ($setPaymentInstrument == 'EFT') {
      $this->select("payment_instrument_id", "value=5");
    }
    $this->type('item_count', $numberOfTrxn);
    $this->type('total', $totalAmt);

    $this->click('_qf_FinancialBatch_next-botttom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // parse URL to grab the batch ID
    $elements = $this->parseURL();
    $batchId = $elements['queryString']['bid'];
    return $batchId;
  }

  function _testAssignBatch($numberOfTrxn) {
    $this->select( "xpath=//div[@id='crm-transaction-selector-assign_length']/label/select[@name='crm-transaction-selector-assign_length']", "value=$numberOfTrxn" );
    sleep(5);
    $this->click('toggleSelect');
    $this->select('trans_assign', 'value=Assign');
    $this->click('Go');
    sleep(5);
  }

  function _testExportBatch($setTitle, $batchId, $exportFormat) {
    $this->open($this->sboxPath . "civicrm/financial/batch?reset=1&action=export&id=$batchId");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    if ($exportFormat == 'CSV') {
      $this->click("xpath=//form[@id='FinancialBatch']/div[2]/table[@class='form-layout']/tbody/tr/td/input[2]");
      $this->click('_qf_FinancialBatch_next-botttom');
      sleep(5);
    }
    else {
      $this->click("xpath=//form[@id='FinancialBatch']/div[2]/table[@class='form-layout']/tbody/tr/td/input[1]");
      $this->click('_qf_FinancialBatch_next-botttom');
      sleep(5);
    }
    $this->open($this->sboxPath . "civicrm?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click("xpath=//div[@id='recently-viewed']/ul/li[1]/a");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Activity_cancel-bottom");
    $this->webtestVerifyTabularData(
      array(
        'Current Attachment(s)' => 'Financial_Transactions_'
      )
    );
  }
}
