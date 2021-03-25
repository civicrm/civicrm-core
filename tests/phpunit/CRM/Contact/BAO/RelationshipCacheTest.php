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
 * Test class for CRM_Contact_BAO_RelationshipCache
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_BAO_RelationshipCacheTest extends CiviUnitTestCase {

  protected function setUp(): void {
    $this->useTransaction(TRUE);
    parent::setUp();
  }

  /**
   * Whenever one `Relationship` is created, there should be two corresponding
   * `RelationshipCache` records.
   */
  public function testRelationshipCache() {
    // add a new type
    $relationship_type_id_1 = $this->relationshipTypeCreate([
      'name_a_b' => 'Praegustator is',
      'name_b_a' => 'Praegustator for',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
    ]);

    // add some people
    $contact_id_1 = $this->individualCreate();
    $contact_id_2 = $this->individualCreate([], 1);

    // create new relationship (using BAO)
    $params = [
      'relationship_type_id' => $relationship_type_id_1,
      'contact_id_a' => $contact_id_1,
      'contact_id_b' => $contact_id_2,
    ];
    $relationshipObj = CRM_Contact_BAO_Relationship::add($params);

    // Let's make sure the cache records were created!
    $caches = CRM_Core_DAO::executeQuery('SELECT * FROM civicrm_relationship_cache WHERE relationship_id = %1', [
      1 => [$relationshipObj->id, 'Positive'],
    ])->fetchAll();

    // There should be two records - the a_b record and the b_a record.
    $this->assertCount(2, $caches);
    $idx = CRM_Utils_Array::index(['orientation'], $caches);

    $this->assertEquals($relationship_type_id_1, $idx['a_b']['relationship_type_id']);
    $this->assertEquals($contact_id_1, $idx['a_b']['near_contact_id']);
    $this->assertEquals('Praegustator is', $idx['a_b']['near_relation']);
    $this->assertEquals($contact_id_2, $idx['a_b']['far_contact_id']);
    $this->assertEquals('Praegustator for', $idx['a_b']['far_relation']);

    $this->assertEquals($relationship_type_id_1, $idx['b_a']['relationship_type_id']);
    $this->assertEquals($contact_id_2, $idx['b_a']['near_contact_id']);
    $this->assertEquals('Praegustator for', $idx['b_a']['near_relation']);
    $this->assertEquals($contact_id_1, $idx['b_a']['far_contact_id']);
    $this->assertEquals('Praegustator is', $idx['b_a']['far_relation']);
  }

}
