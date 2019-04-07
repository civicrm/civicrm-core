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
 * Test class for CRM_Contact_BAO_Relationship
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_BAO_RelationshipTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   *
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   *
   * This method is called after a test is executed.
   */
  protected function tearDown() {
    $this->quickCleanup([
      'civicrm_relationship_type',
      'civicrm_relationship',
      'civicrm_contact',
    ]);

    parent::tearDown();
  }

  public function testRelationshipTypeOptionsWillReturnSpecifiedType() {
    $orgToOrgType = 'A_B_relationship';
    $orgToOrgReverseType = 'B_A_relationship';
    civicrm_api3('RelationshipType', 'create', [
      'name_a_b' => $orgToOrgType,
      'name_b_a' => $orgToOrgReverseType,
      'contact_type_a' => 'Organization',
      'contact_type_b' => 'Organization',
    ]);

    $result = CRM_Contact_BAO_Relationship::buildRelationshipTypeOptions(
      ['contact_type' => 'Organization']
    );
    $this->assertContains($orgToOrgType, $result);
    $this->assertContains($orgToOrgReverseType, $result);

    $result = CRM_Contact_BAO_Relationship::buildRelationshipTypeOptions(
      ['contact_type' => 'Individual']
    );

    $this->assertNotContains($orgToOrgType, $result);
    $this->assertNotContains($orgToOrgReverseType, $result);
  }

  public function testContactIdAndRelationshipIdWillBeUsedInFilter() {
    $individual = civicrm_api3('Contact', 'create', [
      'display_name' => 'Individual A',
      'contact_type' => 'Individual',
    ]);
    $organization = civicrm_api3('Contact', 'create', [
      'organization_name' => 'Organization B',
      'contact_type' => 'Organization',
    ]);

    $personToOrgType = 'A_B_relationship';
    $orgToPersonType = 'B_A_relationship';

    $orgToPersonTypeId = civicrm_api3('RelationshipType', 'create', [
      'name_a_b' => $personToOrgType,
      'name_b_a' => $orgToPersonType,
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
    ])['id'];

    $personToPersonType = 'A_B_alt_relationship';
    $personToPersonReverseType = 'B_A_alt_relationship';

    civicrm_api3('RelationshipType', 'create', [
      'name_a_b' => $personToPersonType,
      'name_b_a' => $personToPersonReverseType,
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
    ]);

    // create a relationship individual => organization
    $relationship = civicrm_api3('Relationship', 'create', [
      'contact_id_a' => $individual['id'],
      'contact_id_b' => $organization['id'],
      'relationship_type_id' => $orgToPersonTypeId,
    ]);

    $options = CRM_Contact_BAO_Relationship::buildRelationshipTypeOptions([
      'relationship_id' => (string) $relationship['id'],
      'contact_id' => $individual['id'],
    ]);

    // for this relationship only individual=>organization is possible
    $this->assertContains($personToOrgType, $options);
    $this->assertNotContains($orgToPersonType, $options);

    // by passing relationship ID we know that the "B" side is an organization
    $this->assertNotContains($personToPersonType, $options);
    $this->assertNotContains($personToPersonReverseType, $options);

    $options = CRM_Contact_BAO_Relationship::buildRelationshipTypeOptions([
      'contact_id' => $individual['id'],
    ]);

    // for this result we only know that "A" must be an individual
    $this->assertContains($personToOrgType, $options);
    $this->assertNotContains($orgToPersonType, $options);

    // unlike when we pass relationship type ID there is no filter by "B" type
    $this->assertContains($personToPersonType, $options);
    $this->assertContains($personToPersonReverseType, $options);
  }

  /**
   * Test removeRelationshipTypeDuplicates method.
   *
   * @dataProvider getRelationshipTypeDuplicates
   */
  public function testRemoveRelationshipTypeDuplicates($relationshipTypeList, $suffix = NULL, $expected, $description) {
    $result = CRM_Contact_BAO_Relationship::removeRelationshipTypeDuplicates($relationshipTypeList, $suffix);
    $this->assertEquals($expected, $result, "Failure on set '$description'");
  }

  public function getRelationshipTypeDuplicates() {
    $relationshipTypeList = array(
      '1_a_b' => 'duplicate one',
      '1_b_a' => 'duplicate one',
      '2_a_b' => 'two a',
      '2_b_a' => 'two b',
    );
    $data = array(
      array(
        $relationshipTypeList,
        'a_b',
        array(
          '1_a_b' => 'duplicate one',
          '2_a_b' => 'two a',
          '2_b_a' => 'two b',
        ),
        'With suffix a_b',
      ),
      array(
        $relationshipTypeList,
        'b_a',
        array(
          '1_b_a' => 'duplicate one',
          '2_a_b' => 'two a',
          '2_b_a' => 'two b',
        ),
        'With suffix b_a',
      ),
      array(
        $relationshipTypeList,
        NULL,
        array(
          '1_a_b' => 'duplicate one',
          '2_a_b' => 'two a',
          '2_b_a' => 'two b',
        ),
        'With suffix NULL',
      ),
      array(
        $relationshipTypeList,
        NULL,
        array(
          '1_a_b' => 'duplicate one',
          '2_a_b' => 'two a',
          '2_b_a' => 'two b',
        ),
        'With suffix "" (empty string)',
      ),
    );
    return $data;
  }

}
