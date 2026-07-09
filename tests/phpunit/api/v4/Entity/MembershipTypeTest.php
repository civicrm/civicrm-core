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

namespace api\v4\Entity;

use api\v4\Api4TestBase;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class MembershipTypeTest extends Api4TestBase implements TransactionalInterface {

  public function setUp(): void {
    parent::setUp();
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviMember');
  }

  public function testRelationshipTypeLabel(): void {
    $relationshipType1 = $this->createTestRecord('RelationshipType', [
      'name_a_b' => 'Test A to B',
      'name_b_a' => 'Test B to A',
      'label_a_b' => 'Label A to B',
      'label_b_a' => 'Label B to A',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
    ]);

    $relationshipType2 = $this->createTestRecord('RelationshipType', [
      'name_a_b' => 'Test2 A to B',
      'name_b_a' => 'Test2 B to A',
      'label_a_b' => 'Label2 A to B',
      'label_b_a' => 'Label2 B to A',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
    ]);

    // Create contact to use for member_of_contact_id
    $contact = $this->createTestRecord('Contact', [
      'contact_type' => 'Organization',
      'organization_name' => 'Test Org',
    ]);

    // Create a MembershipType with a single relationship type
    $membershipType1 = $this->createTestRecord('MembershipType', [
      'name' => 'Single Relation Member Type',
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'member_of_contact_id' => $contact['id'],
      'financial_type_id' => 2,
      'relationship_type_id' => $relationshipType1['id'],
      'relationship_direction' => 'a_b',
    ]);

    // Query 1: Retrieve only the relationship_type_label (without id, relationship_type_id, relationship_direction)
    $result1 = \Civi\Api4\MembershipType::get(FALSE)
      ->addSelect('relationship_type_label')
      ->addWhere('id', '=', $membershipType1['id'])
      ->execute()
      ->first();

    $this->assertEquals(['Label A to B'], $result1['relationship_type_label']);

    // Query 2: Retrieve relationship_type_label with explicit dependency fields
    $result2 = \Civi\Api4\MembershipType::get(FALSE)
      ->addSelect('relationship_type_label', 'relationship_type_id', 'relationship_direction')
      ->addWhere('id', '=', $membershipType1['id'])
      ->execute()
      ->first();

    $this->assertEquals(['Label A to B'], $result2['relationship_type_label']);
    $this->assertEquals([$relationshipType1['id']], $result2['relationship_type_id']);
    $this->assertEquals(['a_b'], $result2['relationship_direction']);

    // Create a MembershipType with multiple relationship types (serialized values/arrays)
    $membershipType2 = $this->createTestRecord('MembershipType', [
      'name' => 'Multi Relation Member Type',
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'member_of_contact_id' => $contact['id'],
      'financial_type_id' => 2,
      'relationship_type_id' => [$relationshipType1['id'], $relationshipType2['id']],
      'relationship_direction' => ['a_b', 'b_a'],
    ]);

    // Query 3: Retrieve only relationship_type_label for multiple relationships
    $result3 = \Civi\Api4\MembershipType::get(FALSE)
      ->addSelect('relationship_type_label')
      ->addWhere('id', '=', $membershipType2['id'])
      ->execute()
      ->first();

    $this->assertEquals(['Label A to B', 'Label2 B to A'], $result3['relationship_type_label']);

    // Query 4: Retrieve relationship_type_label with explicit dependency field for multiple relationships
    $result4 = \Civi\Api4\MembershipType::get(FALSE)
      ->addSelect('relationship_type_label', 'relationship_type_id')
      ->addWhere('id', '=', $membershipType2['id'])
      ->execute()
      ->first();

    $this->assertEquals(['Label A to B', 'Label2 B to A'], $result4['relationship_type_label']);
  }

}
