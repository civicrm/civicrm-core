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
 * Test class for CRM_Price_BAO_PriceSet.
 * @group headless
 */
class CRM_Price_BAO_PriceFieldValueTest extends CiviUnitTestCase {

  /**
   * Sets up the fixtures.
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tears down the fixture.
   */
  protected function tearDown() {
  }

  /**
   * Verifies visibility field exists and is configured as a pseudoconstant
   * referencing the 'visibility' option group.
   */
  public function testVisibilityFieldExists() {
    $fields = CRM_Price_DAO_PriceFieldValue::fields();

    $this->assertArrayKeyExists('visibility_id', $fields);
    $this->assertEquals('visibility', $fields['visibility_id']['pseudoconstant']['optionGroupName']);
  }

}
