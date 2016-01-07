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
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */


require_once 'CiviTest/CiviSeleniumTestCase.php';

/**
 * Class WebTest_Contribute_UpdateContributionTest
 */
class WebTest_Contribute_UpdateContributionTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testChangeContributionAmount() {
    $this->webtestLogin();
    $amount = 100;
    //Offline Pay Later Contribution
    $contact = $this->_testOfflineContribution($amount, "Pending");

    $this->openCiviPage("contribute/search", "reset=1", "contribution_date_low");

    $this->type("sort_name", $contact['sort_name']);
    $this->click("_qf_Search_refresh");

    $this->waitForElementPresent("xpath=//*[@id='Search']//div[@id='contributionSearch']");
    $contriIDOff = explode('&', $this->getAttribute("xpath=//div[@id='contributionSearch']//table[@class='selector row-highlight']/tbody/tr[1]/td[11]/span/a[1]@href"));
    if (!empty($contriIDOff)) {
      $contriIDOff = substr($contriIDOff[1], (strrpos($contriIDOff[1], '=') + 1));
    }

    $this->clickLink("xpath=//tr[@id='rowid{$contriIDOff}']/td[11]/span/a[2]", "total_amount", FALSE);
    $this->waitForAjaxContent();
    $this->type("total_amount", "90");
    $this->clickLink('_qf_Contribution_upload', '', FALSE);
    $this->waitForAjaxContent();
    $this->waitForText('crm-notification-container', "The sum of fee amount and net amount must be equal to total amount");
    $this->type("net_amount", "90");
    $this->clickLink('_qf_Contribution_upload', '', FALSE);

    // Is status message correct?
    $this->waitForText('crm-notification-container', "The contribution record has been saved.");

    //For Contribution
    $searchParams = array('id' => $contriIDOff);
    $compareParams = array('total_amount' => '90.00');
    //For LineItem
    $lineItemSearchParams = array('entity_id' => $contriIDOff);
    $lineItemCompareParams = array('line_total' => '90.00');

    $this->assertDBCompareValues('CRM_Contribute_DAO_Contribution', $searchParams, $compareParams);
    $this->assertDBCompareValues('CRM_Price_DAO_LineItem', $lineItemSearchParams, $lineItemCompareParams);

    $total = $this->_getTotalContributedAmount($contriIDOff);
    $compare = array('total_amount' => $total);
    $this->assertDBCompareValues('CRM_Contribute_DAO_Contribution', $searchParams, $compare);

    $amount = $this->_getFinancialItemAmount($contriIDOff);
    $compare = array('total_amount' => $amount);
    $this->assertDBCompareValues('CRM_Contribute_DAO_Contribution', $searchParams, $compare);

    $financialTrxnAmount = $this->_getFinancialTrxnAmount($contriIDOff);
    $compare = array('total_amount' => $financialTrxnAmount);
    $this->assertDBCompareValues('CRM_Contribute_DAO_Contribution', $searchParams, $compare);
  }

  public function testPayLater() {
    $this->webtestLogin();
    $amount = 100.00;
    //Offline Pay Later Contribution
    $this->_testOfflineContribution($amount, "Pending");
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']");
    $contId = $this->urlArg('id', $this->getAttribute("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']@href"));
    $this->clickAjaxLink("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']", '_qf_Contribution_cancel-bottom');
    $this->select("contribution_status_id", "label=Completed");
    $this->clickAjaxLink("_qf_Contribution_upload", "xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']");
    $this->checkCRMAlert("The contribution record has been saved.");

    //Assertions
    $search = array('id' => $contId);
    $compare = array('contribution_status_id' => 1);
    $this->assertDBCompareValues('CRM_Contribute_DAO_Contribution', $search, $compare);

    $lineItem = key(CRM_Price_BAO_LineItem::getLineItems($contId, 'contribution'));
    $search = array('entity_id' => $lineItem);
    $compare = array('status_id' => 1);
    $this->assertDBCompareValues("CRM_Financial_DAO_FinancialItem", $search, $compare);

    $status = $this->_getPremiumActualCost($contId, 'Accounts Receivable', 'Payment Processor Account', NULL, "'civicrm_contribution'", "ft.status_id as status");
    $this->assertEquals($status, '1', "Verify Completed Status");
  }

  public function testChangePremium() {
    $this->webtestLogin();
    $from = 'Premiums';
    $to = 'Premiums inventory';
    $financialType = array(
      'name' => 'Test Financial' . substr(sha1(rand()), 0, 7),
      'is_reserved' => 1,
      'is_deductible' => 1,
    );
    $this->addeditFinancialType($financialType);
    $this->waitForElementPresent("xpath=//div[@id='ltype']/div/table/tbody//tr/td/div[text()='" . $financialType['name'] . "']/../../td[7]/span/a[text()='Accounts']");
    $this->click("xpath=//div[@id='ltype']/div/table/tbody//tr/td/div[text()='" . $financialType['name'] . "']/../../td[7]/span/a[text()='Accounts']");
    $this->waitForElementPresent("xpath=//a[@id='newfinancialTypeAccount']");
    $this->clickAjaxLink("xpath=//a[@id='newfinancialTypeAccount']", "_qf_FinancialTypeAccount_cancel-botttom");
    $this->select("account_relationship", "label=Premiums Inventory Account is");
    $this->waitForElementPresent("xpath=//select[@id='financial_account_id']/option[3][text()='Deposit Bank Account']");
    $this->select("financial_account_id", "label=$to");
    $this->clickAjaxLink("_qf_FinancialTypeAccount_next-botttom", "xpath=//a[@id='newfinancialTypeAccount']");

    $premiumName = 'Premium' . substr(sha1(rand()), 0, 7);
    $amount = 500;
    $sku = 'SKU';
    $price = 300;
    $cost = 3.00;
    $this->openCiviPage("admin/contribute/managePremiums", "action=add&reset=1");
    // add premium
    $this->addPremium($premiumName, $sku, $amount, $price, $cost, $financialType['name']);

    //add second premium
    $premiumName2 = 'Premium' . substr(sha1(rand()), 0, 7);
    $amount2 = 600;
    $sku2 = 'SKU';
    $price2 = 200;
    $cost2 = 2.00;
    $this->openCiviPage("admin/contribute/managePremiums", "action=add&reset=1");
    $this->addPremium($premiumName2, $sku2, $amount2, $price2, $cost2, $financialType['name']);

    // add contribution with premium
    $this->openCiviPage("contribute/add", "reset=1&action=add&context=standalone");

    // create new contact using dialog
    $this->createDialogContact();
    // select financial type
    $this->select("financial_type_id", "value=1");
    // total amount
    $this->type("total_amount", "100");
    // fill Premium information
    $this->clickAjaxLink("xpath=//div[@id='Premium']", "product_name_0");
    $this->select('product_name_0', "label=$premiumName ( $sku )");
    // Clicking save.
    $this->clickAjaxLink("_qf_Contribution_upload", "xpath=//div[@class='view-content']//table[@class='selector row-highlight']//tbody/tr[1]/td[8]/span/a[text()='View']");
    // Is status message correct?
    $this->checkCRMAlert("The contribution record has been saved.");
    // verify if Contribution is created
    //click through to the Contribution edit screen
    $contId = $this->urlArg('id', $this->getAttribute("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']@href"));
    $this->clickAjaxLink("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']", "product_name_0");

    $this->select('product_name_0', "label=$premiumName2 ( $sku2 )");
    // Clicking save.
    $this->clickAjaxLink("_qf_Contribution_upload", "xpath=//div[@class='view-content']//table[@class='selector row-highlight']//tbody/tr[1]/td[7][text()='$premiumName2']");
    $this->checkCRMAlert("The contribution record has been saved.");

    //Assertions
    $actualAmount = $this->_getPremiumActualCost($contId, $to, $from, $cost2, "'civicrm_contribution'");
    $this->assertEquals($actualAmount, $cost2, "Verify actual cost for changed premium");

    $deletedAmount = $this->_getPremiumActualCost($contId, $from, $to, $cost, "'civicrm_contribution'");
    $this->assertEquals($deletedAmount, $cost, "Verify actual cost for deleted premium");
  }

  public function testDeletePremium() {
    $this->webtestLogin();
    $from = 'Premiums';
    $to = 'Premiums inventory';
    $financialType = array(
      'name' => 'Test Financial' . substr(sha1(rand()), 0, 7),
      'is_reserved' => 1,
      'is_deductible' => 1,
    );
    $this->addeditFinancialType($financialType);
    $this->waitForElementPresent("xpath=//div[@id='ltype']/div/table/tbody//tr/td/div[text()='" . $financialType['name'] . "']/../../td[7]/span/a[text()='Accounts']");
    $this->click("xpath=//div[@id='ltype']/div/table/tbody//tr/td/div[text()='" . $financialType['name'] . "']/../../td[7]/span/a[text()='Accounts']");
    $this->waitForElementPresent("xpath=//a[@id='newfinancialTypeAccount']");
    $this->clickLink("xpath=//a[@id='newfinancialTypeAccount']", "_qf_FinancialTypeAccount_cancel-botttom", FALSE);

    $this->select("account_relationship", "label=Premiums Inventory Account is");
    $this->waitForElementPresent("xpath=//select[@id='financial_account_id']/option[3][text()='Deposit Bank Account']");
    $this->select("financial_account_id", "label=$to");
    $this->clickLink("_qf_FinancialTypeAccount_next-botttom", "xpath=//a[@id='newfinancialTypeAccount']", FALSE);
    $premiumName = 'Premium' . substr(sha1(rand()), 0, 7);
    $amount = 500;
    $sku = 'SKU';
    $price = 300;
    $cost = 3.00;
    $this->openCiviPage("admin/contribute/managePremiums", "action=add&reset=1");
    // add premium
    $this->addPremium($premiumName, $sku, $amount, $price, $cost, $financialType['name']);

    // add contribution with premium
    $this->openCiviPage("contribute/add", "reset=1&action=add&context=standalone");

    // create new contact using dialog
    $this->createDialogContact();
    // select financial type
    $this->select("financial_type_id", "value=1");
    // total amount
    $this->type("total_amount", "100");
    // fill Premium information
    $this->click("xpath=//div[@id='Premium']");
    $this->waitForElementPresent("product_name_0");
    $this->select('product_name_0', "label=$premiumName ( $sku )");
    // Clicking save.
    $this->clickLink("_qf_Contribution_upload");
    // Is status message correct?
    $this->waitForText('crm-notification-container', "The contribution record has been saved.");
    // verify if Contribution is created
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table[@class='selector row-highlight']//tbody/tr[1]/td[8]/span/a[text()='View']");
    //click through to the Contribution edit screen
    $contId = explode('&id=', $this->getAttribute("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']@href"));
    $contId = explode('&', $contId[1]);
    $contId = $contId[0];
    $this->clickLink("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']", "_qf_Contribution_upload-bottom", FALSE);
    $this->waitForElementPresent("product_name_0");
    $this->select('product_name_0', "value=0");
    // Clicking save.
    $this->clickLink("_qf_Contribution_upload", "xpath=//div[@class='view-content']//table[@class='selector row-highlight']//tbody/tr[1]/td[7][not(text())]", FALSE);

    //Assertions
    $actualAmount = $this->_getPremiumActualCost($contId, $from, $to, NULL, "'civicrm_contribution'");
    $this->assertEquals($actualAmount, $cost, "Verify actual cost for deleted premium");
  }

  public function testChangePaymentInstrument() {
    $this->webtestLogin();
    $label = 'TEST' . substr(sha1(rand()), 0, 7);
    $amount = 100.00;
    $financialAccount = CRM_Contribute_PseudoConstant::financialAccount();
    $to = array_search('Accounts Receivable', $financialAccount);
    $from = array_search('Deposit Bank Account', $financialAccount);
    $this->addPaymentInstrument($label, $to);
    $this->_testOfflineContribution($amount);
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']");
    $this->clickLink("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']", '_qf_Contribution_cancel-bottom', FALSE);
    $contId = explode('&id=', $this->getAttribute("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']@href"));
    $contId = explode('&', $contId[1]);
    $contId = $contId[0];
    //change payment processor to newly created value
    $this->select("payment_instrument_id", "label=$label");
    $this->clickLink("_qf_Contribution_upload", "xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']", FALSE);
    $this->waitForAjaxContent();
    //Assertions
    $subtractedTotal = $this->_getPremiumActualCost($contId, NULL, 'Payment Processor Account');
    $this->assertEquals($subtractedTotal, -$amount, "Verify amount deleted from old account");
    $totalAmount = $this->_getPremiumActualCost($contId, NULL, 'Accounts Receivable');
    $this->assertEquals($totalAmount, $amount, "Verify amount for newly inserted values");
  }

  public function testRefundContribution() {
    $this->webtestLogin();
    $amount = 100.00;
    $this->_testOfflineContribution($amount);
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']");
    $this->clickAjaxLink("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']", '_qf_Contribution_cancel-bottom');
    //Contribution status
    $this->select("contribution_status_id", "label=Refunded");
    $contId = explode('&id=', $this->getAttribute("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']@href"));
    $contId = explode('&', $contId[1]);
    $contId = $contId[0];
    $this->clickAjaxLink("_qf_Contribution_upload", "xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[6][text()='Refunded']");

    //Assertions
    $lineItem = key(CRM_Price_BAO_LineItem::getLineItems($contId, 'contribution'));
    $search = array('entity_id' => $lineItem);
    $compare = array(
      'amount' => '100.00',
      'status_id' => 1,
    );
    $this->assertDBCompareValues("CRM_Financial_DAO_FinancialItem", $search, $compare);
    $amount = $this->_getPremiumActualCost($contId, NULL, 'Payment Processor Account', -100.00, "'civicrm_contribution'");
    $this->assertEquals($amount, '-100.00', 'Verify Financial Trxn Amount.');
  }

  public function testCancelPayLater() {
    $this->webtestLogin();
    $amount = 100.00;
    $this->_testOfflineContribution($amount, "Pending");
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']");
    $this->clickLink("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']", '_qf_Contribution_cancel-bottom', FALSE);
    //Contribution status
    $this->select("contribution_status_id", "label=Cancelled");
    $contId = explode('&id=', $this->getAttribute("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']@href"));
    $contId = explode('&', $contId[1]);
    $contId = $contId[0];
    $this->clickLink("_qf_Contribution_upload", "xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']", FALSE);
    $this->waitForText('crm-notification-container', "The contribution record has been saved.");

    //Assertions
    $search = array('id' => $contId);
    $compare = array('contribution_status_id' => 3);
    $this->assertDBCompareValues('CRM_Contribute_DAO_Contribution', $search, $compare);
    $lineItem = key(CRM_Price_BAO_LineItem::getLineItems($contId, 'contribution'));
    $itemParams = array(
      'amount' => '-100.00',
      'entity_id' => $lineItem,
    );
    $defaults = array();
    $items = CRM_Financial_BAO_FinancialItem::retrieve($itemParams, $defaults);
    $this->assertEquals($items->amount, $itemParams['amount'], 'Verify Amount for financial Item');
    $totalAmount = $this->_getPremiumActualCost($items->id, NULL, 'Accounts Receivable', "-100.00", "'civicrm_financial_item'");
    $this->assertEquals($totalAmount, "-$amount", 'Verify Amount for Financial Trxn');
    $totalAmount = $this->_getPremiumActualCost($contId, NULL, 'Accounts Receivable', "-100.00", "'civicrm_contribution'");
    $this->assertEquals($totalAmount, "-$amount", 'Verify Amount for Financial Trxn');
  }

  public function testChangeFinancialType() {
    $this->webtestLogin();
    $amount = 100.00;
    $this->_testOfflineContribution($amount);
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']");
    $this->clickLink("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']", '_qf_Contribution_cancel-bottom', FALSE);
    //Contribution status
    $this->select("financial_type_id", "value=3");
    $contId = explode('&id=', $this->getAttribute("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']@href"));
    $contId = explode('&', $contId[1]);
    $contId = $contId[0];
    $this->clickLink("_qf_Contribution_upload", "xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='Edit']", FALSE);
    $this->waitForText('crm-notification-container', "The contribution record has been saved.");

    //Assertions
    $search = array('id' => $contId);
    $compare = array('financial_type_id' => 3);
    $this->assertDBCompareValues('CRM_Contribute_DAO_Contribution', $search, $compare);

    $lineItem = key(CRM_Price_BAO_LineItem::getLineItems($contId, 'contribution'));
    $itemParams = array(
      'amount' => '-100.00',
      'entity_id' => $lineItem,
    );
    $item1 = $item2 = array();
    CRM_Financial_BAO_FinancialItem::retrieve($itemParams, $item1);
    $this->assertEquals($item1['amount'], "-100.00", "Verify Amount for New Financial Item");
    $itemParams['amount'] = '100.00';
    CRM_Financial_BAO_FinancialItem::retrieve($itemParams, $item2);
    $this->assertEquals($item2['amount'], "100.00", "Verify Amount for New Financial Item");

    $cValue1 = $this->_getPremiumActualCost($contId, NULL, NULL, "-100.00", "'civicrm_contribution'");
    $fValue1 = $this->_getPremiumActualCost($item1['id'], NULL, NULL, "-100.00", "'civicrm_financial_item'");
    $this->assertEquals($cValue1, "-100.00", "Verify Amount");
    $this->assertEquals($fValue1, "-100.00", "Verify Amount");
    $cValue2 = $this->_getPremiumActualCost($contId, NULL, NULL, "100.00", "'civicrm_contribution'");
    $fValue2 = $this->_getPremiumActualCost($item2['id'], NULL, NULL, "100.00", "'civicrm_financial_item'");
    $this->assertEquals($cValue2, "100.00", "Verify Amount");
    $this->assertEquals($fValue2, "100.00", "Verify Amount");
  }

  /**
   * @param int $entityId
   * @param null $from
   * @param null $to
   * @param null $cost
   * @param null $entityTable
   * @param string $select
   *
   * @return null|string
   */
  public function _getPremiumActualCost($entityId, $from = NULL, $to = NULL, $cost = NULL, $entityTable = NULL, $select = "ft.total_amount AS amount") {
    $financialAccount = CRM_Contribute_PseudoConstant::financialAccount();
    $query = "SELECT
     {$select}
     FROM civicrm_financial_trxn ft
     INNER JOIN civicrm_entity_financial_trxn eft ON eft.financial_trxn_id = ft.id AND eft.entity_id = {$entityId}";
    if ($entityTable) {
      $query .= " AND eft.entity_table = {$entityTable}";
    }
    if (!empty($to)) {
      $to = array_search($to, $financialAccount);
      $query .= " AND ft.to_financial_account_id = {$to}";
    }
    if (!empty($from)) {
      $from = array_search($from, $financialAccount);
      $query .= " AND ft.from_financial_account_id = {$from}";
    }
    if (!empty($cost)) {
      $query .= " AND eft.amount = {$cost}";
    }
    $query .= " ORDER BY ft.id DESC LIMIT 1";
    $result = CRM_Core_DAO::singleValueQuery($query);
    return $result;
  }

  /**
   * @param int $contId
   *
   * @return null|string
   */
  public function _getFinancialTrxnAmount($contId) {
    $query = "SELECT
     SUM( ft.total_amount ) AS total
     FROM civicrm_financial_trxn AS ft
     LEFT JOIN civicrm_entity_financial_trxn AS ceft ON ft.id = ceft.financial_trxn_id
     WHERE ceft.entity_table = 'civicrm_contribution'
     AND ceft.entity_id = {$contId}";
    $result = CRM_Core_DAO::singleValueQuery($query);
    return $result;
  }

  /**
   * @param int $contId
   *
   * @return null|string
   */
  public function _getFinancialItemAmount($contId) {
    $lineItem = key(CRM_Price_BAO_LineItem::getLineItems($contId, 'contribution'));
    $query = "SELECT
     SUM(amount)
     FROM civicrm_financial_item
     WHERE entity_table = 'civicrm_line_item'
     AND entity_id = {$lineItem}";
    $result = CRM_Core_DAO::singleValueQuery($query);
    return $result;
  }

  /**
   * @param int $contId
   *
   * @return null|string
   */
  public function _getTotalContributedAmount($contId) {
    $query = "SELECT
     SUM(amount)
     FROM civicrm_entity_financial_trxn
     WHERE entity_table = 'civicrm_contribution'
     AND entity_id = {$contId}";
    $result = CRM_Core_DAO::singleValueQuery($query);
    return $result;
  }

  /**
   * @param $amount
   * @param string $status
   * @return array
   */
  public function _testOfflineContribution($amount, $status = "Completed") {

    $this->openCiviPage("contribute/add", "reset=1&context=standalone", "_qf_Contribution_upload");

    // create new contact using dialog
    $contact = $this->createDialogContact();

    // select financial type
    $this->select("financial_type_id", "value=1");

    //Contribution status
    $this->select("contribution_status_id", "label=$status");

    // total amount
    $this->type("total_amount", $amount);

    // select payment instrument type
    $this->select("payment_instrument_id", "label=Credit Card");

    $this->type("trxn_id", "P20901X1" . rand(100, 10000));

    //Custom Data
    //$this->click('CIVICRM_QFID_3_6');

    // Clicking save.
    $this->clickLink("_qf_Contribution_upload");

    // Is status message correct?
    $this->checkCRMAlert("The contribution record has been saved.");

    $this->waitForElementPresent("xpath=//div[@class='view-content']//table[@class='selector row-highlight']//tbody/tr[1]/td[8]/span/a[text()='View']");
    // verify if Membership is created

    //click through to the Membership view screen
    $this->click("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='View']");

    $expected = array(
      'Financial Type' => 'Donation',
      'Total Amount' => '100.00',
      'Contribution Status' => $status,
    );
    $this->webtestVerifyTabularData($expected);
    $this->click("_qf_ContributionView_cancel-bottom");

    return $contact;
  }

}
