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
 * Class CRM_Contribute_BAO_ManagePremiumsTest
 * @group headless
 */
class CRM_Contribute_BAO_ManagePremiumsTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * Check method add()
   */
  public function testAdd() {
    $ids = array();
    $params = array(
      'name' => 'Test Product',
      'sku' => 'TP-10',
      'imageOption' => 'noImage',
      'price' => 12,
      'cost' => 5,
      'min_contribution' => 5,
      'is_active' => 1,
    );

    $product = CRM_Contribute_BAO_ManagePremiums::add($params, $ids);

    $result = $this->assertDBNotNull('CRM_Contribute_BAO_ManagePremiums', $product->id,
      'sku', 'id',
      'Database check on updated product record.'
    );

    $this->assertEquals($result, 'TP-10', 'Verify products sku.');
  }

  /**
   * Check method retrieve( )
   */
  public function testRetrieve() {
    $ids = array();
    $params = array(
      'name' => 'Test Product',
      'sku' => 'TP-10',
      'imageOption' => 'noImage',
      'price' => 12,
      'cost' => 5,
      'min_contribution' => 5,
      'is_active' => 1,
    );

    $product = CRM_Contribute_BAO_ManagePremiums::add($params, $ids);
    $params = array('id' => $product->id);
    $default = array();
    $result = CRM_Contribute_BAO_ManagePremiums::retrieve($params, $default);
    $this->assertEquals(empty($result), FALSE, 'Verify products record.');
  }

  /**
   * Check method setIsActive( )
   */
  public function testSetIsActive() {
    $ids = array();
    $params = array(
      'name' => 'Test Product',
      'sku' => 'TP-10',
      'imageOption' => 'noImage',
      'price' => 12,
      'cost' => 5,
      'min_contribution' => 5,
      'is_active' => 1,
    );

    $product = CRM_Contribute_BAO_ManagePremiums::add($params, $ids);
    CRM_Contribute_BAO_ManagePremiums::setIsActive($product->id, 0);

    $isActive = $this->assertDBNotNull('CRM_Contribute_BAO_ManagePremiums', $product->id,
      'is_active', 'id',
      'Database check on updated for product records is_active.'
    );

    $this->assertEquals($isActive, 0, 'Verify product records is_active.');
  }

  /**
   * Check method del( )
   */
  public function testDel() {
    $ids = array();
    $params = array(
      'name' => 'Test Product',
      'sku' => 'TP-10',
      'imageOption' => 'noImage',
      'price' => 12,
      'cost' => 5,
      'min_contribution' => 5,
      'is_active' => 1,
    );

    $product = CRM_Contribute_BAO_ManagePremiums::add($params, $ids);

    CRM_Contribute_BAO_ManagePremiums::del($product->id);

    $params = array('id' => $product->id);
    $default = array();
    $result = CRM_Contribute_BAO_ManagePremiums::retrieve($params, $defaults);

    $this->assertEquals(empty($result), TRUE, 'Verify product record deletion.');
  }

}
