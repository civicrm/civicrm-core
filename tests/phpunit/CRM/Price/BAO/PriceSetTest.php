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
 * Test class for CRM_Price_BAO_PriceSet.
 * @group headless
 */
class CRM_Price_BAO_PriceSetTest extends CiviUnitTestCase {

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
   * Test the correct amount level is returned for an event which is not presented as a price set event.
   *
   * (these are denoted as 'quickConfig' in the code - but quickConfig is only supposed to refer to the
   * configuration interface - there should be no different post process.
   */
  public function testGetAmountLevelTextAmount() {
    $priceSetID = $this->eventPriceSetCreate(9);
    $priceSet = CRM_Price_BAO_PriceSet::getCachedPriceSetDetail($priceSetID);
    $field = reset($priceSet['fields']);
    $params = array('priceSetId' => $priceSetID, 'price_' . $field['id'] => 1);
    $amountLevel = CRM_Price_BAO_PriceSet::getAmountLevelText($params);
    $this->assertEquals(CRM_Core_DAO::VALUE_SEPARATOR . 'Price Field - 1' . CRM_Core_DAO::VALUE_SEPARATOR, $amountLevel);
  }

}
