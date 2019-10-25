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
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Core_BAO_PrevNextCacheTest
 * @group headless
 */
class CRM_Core_BAO_PrevNextCacheTest extends CiviUnitTestCase {

  public function testFlipData() {
    $dao = new CRM_Core_BAO_PrevNextCache();
    $dao->entity_id1 = 1;
    $dao->entity_id2 = 2;
    $dao->data = serialize([
      'srcID' => 1,
      'srcName' => 'Ms. Meliissa Mouse II',
      'dstID' => 2,
      'dstName' => 'Mr. Maurice Mouse II',
      'weight' => 20,
      'canMerge' => TRUE,
    ]);
    $dao->save();
    $dao = new CRM_Core_BAO_PrevNextCache();
    $dao->id = 1;
    CRM_Core_BAO_PrevNextCache::flipPair([1], 0);
    $dao->find(TRUE);
    $this->assertEquals(1, $dao->entity_id1);
    $this->assertEquals(2, $dao->entity_id2);
    $this->assertEquals(serialize([
      'srcName' => 'Mr. Maurice Mouse II',
      'dstID' => 1,
      'dstName' => 'Ms. Meliissa Mouse II',
      'weight' => 20,
      'canMerge' => TRUE,
      'srcID' => 2,
    ]), $dao->data);

    $this->quickCleanup(['civicrm_prevnext_cache']);
  }

  public function testSetItem() {
    $cacheKeyString = 'TestCacheKeyString';
    $data = '1234afgbghh';
    $values = [];
    $values[] = " ( 'civicrm_contact', 0, 0, '{$cacheKeyString}_stats', '$data' ) ";
    $valueArray = CRM_Core_BAO_PrevNextCache::convertSetItemValues($values[0]);
    // verify as SetItem would do that it converts the original values style into a sensible array format
    $this->assertEquals(['civicrm_contact', 0, 0, 'TestCacheKeyString_stats', '1234afgbghh'], $valueArray);
    CRM_Core_BAO_PrevNextCache::setItem($valueArray[0], $valueArray[1], $valueArray[2], $valueArray[3], $valueArray[4]);
    $dao = new CRM_Core_BAO_PrevNextCache();
    $dao->cacheKey = 'TestCacheKeyString_stats';
    $dao->find(TRUE);
    $this->assertEquals('1234afgbghh', $dao->data);
    $this->assertEquals(0, $dao->entity_id1);
    $this->assertEquals(0, $dao->entity_id2);
    $this->assertEquals('civicrm_contact', $dao->entity_table);
  }

}
