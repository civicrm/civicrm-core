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

}
