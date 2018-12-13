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
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

require_once 'CiviTest/CiviSeleniumTestCase.php';

/**
 * Class WebTest_Financial_FinancialAccountTest
 */
class WebTest_Financial_FinancialAccountTest extends CiviSeleniumTestCase {

  /**
   * Test To Add Financial Account class attributes.
   */
  public function testFinancialAccount() {
    $this->webtestLogin();

    // Add new Financial Account
    $orgName = 'Alberta ' . substr(sha1(rand()), 0, 7);
    $uniqueName = explode(" ", $orgName);
    $financialAccountTitle = 'Financial Account ' . substr(sha1(rand()), 0, 4);
    $financialAccountDescription = "{$financialAccountTitle} Description";
    $accountingCode = 1033;
    $financialAccountType = 'Liability';
    $taxDeductible = FALSE;
    $isActive = TRUE;
    $isTax = TRUE;
    $taxRate = 9.99999999;
    $isDefault = FALSE;

    //Add new organisation
    if ($orgName) {
      $this->webtestAddOrganization($orgName);
    }

    $this->_testAddFinancialAccount($financialAccountTitle,
      $financialAccountDescription,
      $accountingCode,
      $uniqueName[1],
      $financialAccountType,
      $taxDeductible,
      $isActive,
      $isTax,
      $taxRate,
      $isDefault
    );

    $this->waitForElementPresent("xpath=//table/tbody//tr/td[1]/div[text()='{$financialAccountTitle}']/../../td[9]/span/a[text()='Edit']");

    $this->clickLink("xpath=//table/tbody//tr/td[1]/div[text()='{$financialAccountTitle}']/../../td[9]/span/a[text()='Edit']", '_qf_FinancialAccount_cancel-botttom', FALSE);
    //Varify Data after Adding new Financial Account
    $verifyData = array(
      'name' => $financialAccountTitle,
      'description' => $financialAccountDescription,
      'accounting_code' => $accountingCode,
      'tax_rate' => $taxRate,
      'is_tax' => 'on',
      'is_deductible' => 'off',
      'is_default' => 'off',
    );

    $this->assertEquals($orgName, $this->getText("xpath=//*[@id='s2id_contact_id']/a/span[1]"));

    $this->_assertFinancialAccount($verifyData);
    $verifySelectFieldData = array('financial_account_type_id' => $financialAccountType);
    $this->_assertSelectVerify($verifySelectFieldData);
    $this->click('_qf_FinancialAccount_cancel-botttom');

    //Edit Financial Account
    $editfinancialAccount = $financialAccountTitle;
    $financialAccountTitle .= ' Edited';
    $orgNameEdit = FALSE;
    $financialAccountType = 'Liability';

    if ($orgNameEdit) {
      $orgNameEdit = 'NGO ' . substr(sha1(rand()), 0, 7);
      $this->webtestAddOrganization($orgNameEdit);
      $uniqueName = explode(" ", $orgNameEdit);
    }

    $this->_testEditFinancialAccount($editfinancialAccount,
      $financialAccountTitle,
      $financialAccountDescription,
      $accountingCode,
      $uniqueName[1],
      $financialAccountType,
      $taxDeductible,
      $isActive,
      $isTax,
      $taxRate,
      $isDefault
    );

    if ($orgNameEdit) {
      $orgName = $orgNameEdit;
    }
    $this->waitForElementPresent("xpath=//table/tbody//tr/td[1]/div[text()='{$financialAccountTitle}']/../../td[9]/span/a[text()='Edit']");
    $this->clickLink("xpath=//table/tbody//tr/td[1]/div[text()='{$financialAccountTitle}']/../../td[9]/span/a[text()='Edit']", '_qf_FinancialAccount_cancel-botttom', FALSE);

    $verifyData = array(
      'name' => $financialAccountTitle,
      'description' => $financialAccountDescription,
      'accounting_code' => $accountingCode,
      'tax_rate' => $taxRate,
      'is_tax' => 'on',
      'is_deductible' => 'off',
      'is_default' => 'off',
    );

    $this->assertEquals($orgName, $this->getText("xpath=//*[@id='s2id_contact_id']/a/span[1]"));

    $this->_assertFinancialAccount($verifyData);
    $verifySelectFieldData = array('financial_account_type_id' => $financialAccountType);
    $this->_assertSelectVerify($verifySelectFieldData);
    $this->click('_qf_FinancialAccount_cancel-botttom');
    $this->waitForElementPresent("xpath=//table/tbody//tr/td[1]/div[text()='{$financialAccountTitle}']/../../td[9]/span/a[text()='Delete']");

    //Delete Financial Account
    $this->_testDeleteFinancialAccount($financialAccountTitle);
  }

}
