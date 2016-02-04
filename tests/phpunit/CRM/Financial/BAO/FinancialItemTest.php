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

/**
 * Class CRM_Financial_BAO_FinancialItemTest
 */
class CRM_Financial_BAO_FinancialItemTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * Check method add()
   */
  public function testAdd() {
    $firstName = 'Shane';
    $lastName = 'Whatson';
    $params = array(
      'first_name' => $firstName,
      'last_name' => $lastName,
      'contact_type' => 'Individual',
    );

    $contact = CRM_Contact_BAO_Contact::add($params);

    $price = 100;
    $cParams = array(
      'contact_id' => $contact->id,
      'total_amount' => $price,
      'financial_type_id' => 1,
      'is_active' => 1,
      'skipLineItem' => 1,
    );

    $defaults = array();
    $contribution = CRM_Contribute_BAO_Contribution::add($cParams, $defaults);
    $lParams = array(
      'entity_id' => $contribution->id,
      'entity_table' => 'civicrm_contribution',
      'price_field_id' => 1,
      'qty' => 1,
      'label' => 'Contribution Amount',
      'unit_price' => $price,
      'line_total' => $price,
      'price_field_value_id' => 1,
      'financial_type_id' => 1,
    );

    $lineItem = CRM_Price_BAO_LineItem::create($lParams);
    CRM_Financial_BAO_FinancialItem::add($lineItem, $contribution);
    $result = $this->assertDBNotNull(
      'CRM_Financial_DAO_FinancialItem',
      $lineItem->id,
      'amount',
      'entity_id',
      'Database check on added financial item record.'
    );
    $this->assertEquals($result, $price, 'Verify Amount for Financial Item');
  }

  /**
   * Check method retrive()
   */
  public function testRetrieve() {
    $firstName = 'Shane';
    $lastName = 'Whatson';
    $params = array(
      'first_name' => $firstName,
      'last_name' => $lastName,
      'contact_type' => 'Individual',
    );

    $contact = CRM_Contact_BAO_Contact::add($params);
    $price = 100.00;
    $cParams = array(
      'contact_id' => $contact->id,
      'total_amount' => $price,
      'financial_type_id' => 1,
      'is_active' => 1,
      'skipLineItem' => 1,
    );

    $defaults = array();
    $contribution = CRM_Contribute_BAO_Contribution::add($cParams, $defaults);
    $lParams = array(
      'entity_id' => $contribution->id,
      'entity_table' => 'civicrm_contribution',
      'price_field_id' => 1,
      'qty' => 1,
      'label' => 'Contribution Amount',
      'unit_price' => $price,
      'line_total' => $price,
      'price_field_value_id' => 1,
      'financial_type_id' => 1,
    );

    $lineItem = CRM_Price_BAO_LineItem::create($lParams);
    CRM_Financial_BAO_FinancialItem::add($lineItem, $contribution);
    $values = array();
    $fParams = array(
      'entity_id' => $lineItem->id,
      'entity_table' => 'civicrm_line_item',
    );
    $financialItem = CRM_Financial_BAO_FinancialItem::retrieve($fParams, $values);
    $this->assertEquals($financialItem->amount, $price, 'Verify financial item amount.');
  }

  /**
   * Check method create()
   */
  public function testCreate() {
    $firstName = 'Shane';
    $lastName = 'Whatson';
    $params = array(
      'first_name' => $firstName,
      'last_name' => $lastName,
      'contact_type' => 'Individual',
    );

    $contact = CRM_Contact_BAO_Contact::add($params);
    $price = 100.00;
    $cParams = array(
      'contact_id' => $contact->id,
      'total_amount' => $price,
      'financial_type_id' => 1,
      'is_active' => 1,
      'skipLineItem' => 1,
    );

    $defaults = array();
    $contribution = CRM_Contribute_BAO_Contribution::add($cParams, $defaults);
    $lParams = array(
      'entity_id' => $contribution->id,
      'entity_table' => 'civicrm_contribution',
      'price_field_id' => 1,
      'qty' => 1,
      'label' => 'Contribution Amount',
      'unit_price' => $price,
      'line_total' => $price,
      'price_field_value_id' => 1,
      'financial_type_id' => 1,
    );

    $lineItem = CRM_Price_BAO_LineItem::create($lParams);
    $fParams = array(
      'contact_id' => $contact->id,
      'description' => 'Contribution Amount',
      'amount' => $price,
      'financial_account_id' => 1,
      'status_id' => 1,
      'transaction_date' => date('YmdHis'),
      'entity_id' => $lineItem->id,
      'entity_table' => 'civicrm_line_item',
    );

    CRM_Financial_BAO_FinancialItem::create($fParams);
    $entityTrxn = new CRM_Financial_DAO_EntityFinancialTrxn();
    $entityTrxn->entity_table = 'civicrm_contribution';
    $entityTrxn->entity_id = $contribution->id;
    $entityTrxn->amount = $price;
    if ($entityTrxn->find(TRUE)) {
      $entityId = $entityTrxn->entity_id;
    }

    $result = $this->assertDBNotNull(
      'CRM_Financial_DAO_FinancialItem',
      $lineItem->id,
      'amount',
      'entity_id',
      'Database check on added financial item record.'
    );

    $this->assertEquals($result, $price, 'Verify Amount for Financial Item');
    $entityResult = $this->assertDBNotNull(
      'CRM_Financial_DAO_EntityFinancialTrxn',
      $entityId,
      'amount',
      'entity_id',
      'Database check on added entity financial trxn record.'
    );
    $this->assertEquals($entityResult, $price, 'Verify Amount for Financial Item');
  }

  /**
   * Check method del()
   */
  public function testCreateEntityTrxn() {
    $fParams = array(
      'name' => 'Donations' . substr(sha1(rand()), 0, 7),
      'is_deductible' => 0,
      'is_active' => 1,
    );

    $amount = 200;
    $ids = array();
    $financialAccount = CRM_Financial_BAO_FinancialAccount::add($fParams, $ids);
    $financialTrxn = new CRM_Financial_DAO_FinancialTrxn();
    $financialTrxn->to_financial_account_id = $financialAccount->id;
    $financialTrxn->total_amount = $amount;
    $financialTrxn->save();
    $params = array(
      'entity_table' => 'civicrm_contribution',
      'entity_id' => 1,
      'financial_trxn_id' => $financialTrxn->id,
      'amount' => $amount,
    );

    $entityTrxn = CRM_Financial_BAO_FinancialItem::createEntityTrxn($params);
    $entityResult = $this->assertDBNotNull(
      'CRM_Financial_DAO_EntityFinancialTrxn',
      $financialTrxn->id,
      'amount',
      'financial_trxn_id',
      'Database check on added entity financial trxn record.'
    );
    $this->assertEquals($entityResult, $amount, 'Verify Amount for Financial Item');
    return $entityTrxn;
  }

  /**
   * Check method retrieveEntityFinancialTrxn()
   */
  public function testRetrieveEntityFinancialTrxn() {
    $entityTrxn = self::testCreateEntityTrxn();
    $params = array(
      'entity_table' => 'civicrm_contribution',
      'entity_id' => 1,
      'financial_trxn_id' => $entityTrxn->financial_trxn_id,
      'amount' => $entityTrxn->amount,
    );

    CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($params);
    $entityResult = $this->assertDBNotNull(
      'CRM_Financial_DAO_EntityFinancialTrxn',
      $entityTrxn->financial_trxn_id,
      'amount',
      'financial_trxn_id',
      'Database check on added entity financial trxn record.'
    );
    $this->assertEquals($entityResult, $entityTrxn->amount, 'Verify Amount for Financial Item');
  }

}
