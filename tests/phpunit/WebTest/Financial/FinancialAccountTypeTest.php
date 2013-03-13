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

class WebTest_Financial_FinancialAccountTypeTest extends CiviSeleniumTestCase {

  function testFinancialAccount() {
    // To Add Financial Account 
    // class attributes.
    $this->open($this->sboxPath);
    
    // Log in using webtestLogin() method
    $this->webtestLogin();
    
    // Add new Financial Account
    $orgName = 'Alberta '.substr(sha1(rand()), 0, 7);
    $financialAccountTitle = 'Financial Account '.substr(sha1(rand()), 0, 4);
    $financialAccountDescription = "{$financialAccountTitle} Description";
    $accountingCode = 1033;
    $financialAccountType = 'Revenue';
    $taxDeductible = FALSE;
    $isActive = FALSE;
    $isTax = TRUE;
    $taxRate = 5.20;
    $isDefault = FALSE;
        
    //Add new organisation
    if ($orgName) {
      $this->webtestAddOrganization($orgName);
    }
        
    $this->_testAddFinancialAccount( 
      $financialAccountTitle,
      $financialAccountDescription,
      $accountingCode,
      $orgName,
      $financialAccountType,
      $taxDeductible,
      $isActive,
      $isTax,
      $taxRate,
      $isDefault
    );
    
    $this->waitForElementPresent("xpath=//table/tbody//tr/td[1][text()='{$financialAccountTitle}']/../td[9]/span/a[text()='Edit']");
        
    $this->click("xpath=//table/tbody//tr/td[1][text()='{$financialAccountTitle}']/../td[9]/span/a[text()='Edit']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_FinancialAccount_cancel-botttom');
        
    //Varify Data after Adding new Financial Account
    $verifyData = array( 
      'name' => $financialAccountTitle,
      'description' => $financialAccountDescription,
      'accounting_code' => $accountingCode,
      'contact_name' => $orgName,
      'tax_rate' => $taxRate,
      'is_tax' => 'on',
      'is_deductible' => 'off',
      'is_default' => 'off',
    );
    
    $this->_assertFinancialAccount($verifyData);
    $verifySelectFieldData = array(
      'financial_account_type_id' => $financialAccountType,
    );
    $this->_assertSelectVerify($verifySelectFieldData);
    $this->click('_qf_FinancialAccount_cancel-botttom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Add new Financial Type
    $financialType['name'] = 'FinancialType '.substr(sha1(rand()), 0, 4);
    $financialType['is_deductible'] = true;
    $financialType['is_reserved'] = false;
    $this->addeditFinancialType($financialType);
    $accountRelationship = "Income Account is";
    $expected[] = array( 
      'financial_account' => $financialAccountTitle, 
      'account_relationship' => $accountRelationship 
    );
        
    $this->select('account_relationship', "label={$accountRelationship}");
    $this->select('financial_account_id', "label={$financialAccountTitle}");
    $this->click('_qf_FinancialTypeAccount_next_new');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $text = 'The financial type Account has been saved.';
    $this->assertElementContainsText('crm-notification-container', $text, 'Missing text: ' . $text);
    $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);
    $text = 'You can add another Financial Account Type.';
    $this->assertElementContainsText('crm-notification-container', $text, 'Missing text: ' . $text);
    $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);
    $accountRelationship = 'Expense Account is';
    $expected[] = array( 
      'financial_account' => 'Banking Fees', 
      'account_relationship' => $accountRelationship 
    );

    $this->select('account_relationship', "label={$accountRelationship}");
    $this->select('financial_account_id', "label=Banking Fees");
    $this->click('_qf_FinancialTypeAccount_next');
    $this->waitForElementPresent( 'newfinancialTypeAccount');
    $text = 'The financial type Account has been saved.';
    $this->assertElementContainsText('crm-notification-container', $text, 'Missing text: ' . $text);
    $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);
        
    foreach ($expected as  $value => $label) {
      $this->verifyText("xpath=id('ltype')/div/table/tbody/tr/td[1][text()='$label[financial_account]']/../td[2]", preg_quote($label['account_relationship']));
    }
    $this->openCiviPage('admin/financial/financialType', 'reset=1', 'newFinancialType');
    $this->verifyText("xpath=id('ltype')/div/table/tbody/tr/td[1][text()='$financialType[name]']/../td[3]", $financialAccountTitle. ',Banking Fees');
    $this->click("xpath=id('ltype')/div/table/tbody/tr/td[1][text()='$financialType[name]']/../td[7]/span/a[text()='Accounts']");
    $this->waitForElementPresent('newfinancialTypeAccount');
    $this->click("xpath=id('ltype')/div/table/tbody/tr/td[1][text()='Banking Fees']/../td[7]/span/a[text()='Edit']");
    $this->waitForElementPresent('_qf_FinancialTypeAccount_next');
    $this->select('account_relationship', "value=select");
    sleep(1);
    $this->select('account_relationship', "label=Accounts Receivable Account is");
    $this->select('financial_account_id', "label=Accounts Receivable");
    $this->click('_qf_FinancialTypeAccount_next');
    $this->waitForElementPresent("xpath=id('ltype')/div/table/tbody/tr/td[1][text()='Accounts Receivable']/../td[7]/span/a[text()='Edit']");
    $this->verifyText("xpath=id('ltype')/div/table/tbody/tr/td[1][text()='Accounts Receivable']/../td[2]", preg_quote('Accounts Receivable Account is'));
    $this->click("xpath=id('ltype')/div/table/tbody/tr/td[1][text()='Accounts Receivable']/../td[7]/span/a[text()='Delete']"); 
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_FinancialTypeAccount_next-botttom');
    $this->click('_qf_FinancialTypeAccount_next-botttom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-notification-container', 'Selected financial type account has been deleted.', 'Missing text: ' . 'Selected financial type account has been deleted.');
        
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