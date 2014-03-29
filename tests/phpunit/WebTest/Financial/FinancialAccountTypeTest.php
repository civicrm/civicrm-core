<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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

class WebTest_Financial_FinancialAccountTypeTest extends CiviSeleniumTestCase {

  function testFinancialAccount() {
    // To Add Financial Account

    // class attributes.

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Add new Financial Type
    $financialType['name'] = 'FinancialType '.substr(sha1(rand()), 0, 4);
    $financialType['is_deductible'] = true;
    $financialType['is_reserved'] = false;
    $this->addeditFinancialType($financialType);
    $expected = array(
      array(
        'financial_account' => $financialType['name'],
        'account_relationship' => "Income Account is",
      ),
      array(
        'financial_account' => 'Banking Fees',
        'account_relationship' => 'Expense Account is',
      ),
      array(
        'financial_account' => 'Accounts Receivable',
        'account_relationship' => 'Accounts Receivable Account is',
      ),
      array(
        'financial_account' => 'Premiums',
        'account_relationship' => 'Cost of Sales Account is',
      ),
    );
    
    foreach ($expected as  $value => $label) {
      $this->verifyText("xpath=id('ltype')/div/table/tbody/tr/td[2][text()='$label[financial_account]']/../td[1]", preg_quote($label['account_relationship']));
    }
    $this->openCiviPage('admin/financial/financialType', 'reset=1', 'newFinancialType');
    $this->verifyText("xpath=id('ltype')/div/table/tbody/tr/td[1][text()='$financialType[name]']/../td[3]", 'Accounts Receivable,Banking Fees,Premiums,' . $financialType['name']);
    $this->click("xpath=id('ltype')/div/table/tbody/tr/td[1][text()='$financialType[name]']/../td[7]/span/a[text()='Accounts']");
    $this->waitForElementPresent('newfinancialTypeAccount');
    $this->click("xpath=id('ltype')/div/table/tbody/tr/td[2][text()='Banking Fees']/../td[7]/span/a[text()='Edit']");
    $this->waitForElementPresent('_qf_FinancialTypeAccount_next');
    $this->select('account_relationship', "value=select");
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    sleep(1);
    $this->select('account_relationship', "label=Premiums Inventory Account is");
    $this->select('financial_account_id', "label=Premiums inventory");
    $this->click('_qf_FinancialTypeAccount_next');
    $this->waitForElementPresent("xpath=id('ltype')/div/table/tbody/tr/td[2][text()='Premiums inventory']/../td[7]/span/a[text()='Edit']");
    $this->verifyText("xpath=id('ltype')/div/table/tbody/tr/td[2][text()='Premiums inventory']/../td[1]", preg_quote('Premiums Inventory Account is'));
    $this->clickLink("xpath=id('ltype')/div/table/tbody/tr/td[2][text()='Premiums inventory']/../td[7]/span/a[text()='Delete']", '_qf_FinancialTypeAccount_next-botttom');
    $this->click('_qf_FinancialTypeAccount_next-botttom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', 'Selected financial type account has been deleted.');

    //edit financial type
    $financialType['oldname'] = $financialType['name'];
    $financialType['name'] = 'Edited FinancialType '.substr(sha1(rand()), 0, 4);
    $financialType['is_deductible'] = true;
    $financialType['is_reserved'] = false;
    $this->addeditFinancialType($financialType , 'Edit');
    //delete financialtype
    $this->addeditFinancialType($financialType , 'Delete');
  }
}
