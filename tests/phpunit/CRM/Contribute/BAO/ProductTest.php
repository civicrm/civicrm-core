<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Contribute_BAO_ProductTest
 * @group headless
 */
class CRM_Contribute_BAO_ProductTest extends CiviUnitTestCase {

  /**
   * Check method add()
   */
  public function testAdd(): void {
    $params = [
      'name' => 'Test Product',
      'sku' => 'TP-10',
      'imageOption' => 'noImage',
      'price' => 12,
      'cost' => 5,
      'min_contribution' => 5,
      'is_active' => 1,
    ];

    $product = CRM_Contribute_BAO_Product::create($params);
    $result = $this->assertDBNotNull('CRM_Contribute_BAO_Product', $product->id,
      'sku', 'id',
      'Database check on updated product record.'
    );

    $this->assertEquals($result, 'TP-10', 'Verify products sku.');
  }

  /**
   * Check method retrieve( )
   */
  public function testRetrieve(): void {
    $params = [
      'name' => 'Test Product',
      'sku' => 'TP-10',
      'imageOption' => 'noImage',
      'price' => 12,
      'cost' => 5,
      'min_contribution' => 5,
      'is_active' => 1,
    ];

    $product = CRM_Contribute_BAO_Product::create($params);
    $params = ['id' => $product->id];
    $default = [];
    $result = CRM_Contribute_BAO_Product::retrieve($params, $default);
    $this->assertEquals(empty($result), FALSE, 'Verify products record.');
  }

  /**
   * Check method del( )
   */
  public function testDel(): void {
    $params = [
      'name' => 'Test Product',
      'sku' => 'TP-10',
      'imageOption' => 'noImage',
      'price' => 12,
      'cost' => 5,
      'min_contribution' => 5,
      'is_active' => 1,
    ];

    $product = CRM_Contribute_BAO_Product::create($params);
    CRM_Contribute_BAO_Product::deleteRecord(['id' => $product->id]);

    $params = ['id' => $product->id];
    $defaults = [];
    $retrievedProduct = CRM_Contribute_BAO_Product::retrieve($params, $defaults);

    $this->assertEquals(empty($retrievedProduct), TRUE, 'Verify product record deletion.');
  }

}
