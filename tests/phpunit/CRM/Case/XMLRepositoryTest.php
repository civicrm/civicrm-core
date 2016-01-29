<?php

/**
 * Class CRM_Case_XMLRepositoryTest
 */
class CRM_Case_XMLRepositoryTest extends CiviUnitTestCase {
  protected $fixtures = array();

  protected function setUp() {
    parent::setUp();
    $this->fixtures['CaseTypeWithSingleActivityType'] = '
<CaseType>
  <name>CaseTypeWithSingleActivityType</name>
  <ActivityTypes>
    <ActivityType>
      <name>Single Activity Type</name>
      <max_instances>1</max_instances>
    </ActivityType>
  </ActivityTypes>
</CaseType>
    ';
    $this->fixtures['CaseTypeWithTwoActivityTypes'] = '
<CaseType>
  <name>CaseTypeWithTwoActivityTypes</name>
  <ActivityTypes>
    <ActivityType>
      <name>First Activity Type</name>
      <max_instances>1</max_instances>
    </ActivityType>
    <ActivityType>
      <name>Second Activity Type</name>
    </ActivityType>
  </ActivityTypes>
</CaseType>
    ';
    $this->fixtures['CaseTypeWithThreeActivityTypes'] = '
<CaseType>
  <name>CaseTypeWithThreeActivityTypes</name>
  <ActivityTypes>
    <ActivityType>
      <name>First Activity Type</name>
      <max_instances>1</max_instances>
    </ActivityType>
    <ActivityType>
      <name>Second Activity Type</name>
    </ActivityType>
    <ActivityType>
      <name>Third Activity Type</name>
    </ActivityType>
  </ActivityTypes>
</CaseType>
    ';
    $this->fixtures['CaseTypeWithSingleRole'] = '
<CaseType>
  <name>CaseTypeWithSingleRole</name>
  <CaseRoles>
    <RelationshipType>
        <name>Single Role</name>
        <creator>1</creator>
    </RelationshipType>
 </CaseRoles>
</CaseType>
    ';
    $this->fixtures['CaseTypeWithTwoRoles'] = '
<CaseType>
  <name>CaseTypeWithTwoRoles</name>
  <CaseRoles>
    <RelationshipType>
        <name>First Role</name>
        <creator>1</creator>
    </RelationshipType>
    <RelationshipType>
        <name>Second Role</name>
    </RelationshipType>
 </CaseRoles>
</CaseType>
    ';
    $this->fixtures['CaseTypeWithThreeRoles'] = '
<CaseType>
  <name>CaseTypeWithThreeRoles</name>
  <CaseRoles>
    <RelationshipType>
        <name>First Role</name>
        <creator>1</creator>
    </RelationshipType>
    <RelationshipType>
        <name>Second Role</name>
    </RelationshipType>
    <RelationshipType>
        <name>Third Role</name>
    </RelationshipType>
 </CaseRoles>
</CaseType>
    ';

  }

  public function testGetAllDeclaredActivityTypes() {
    $repo = new CRM_Case_XMLRepository(
      array('CaseTypeWithTwoActivityTypes', 'CaseTypeWithThreeActivityTypes'),
      array(
        'CaseTypeWithTwoActivityTypes' => new SimpleXMLElement($this->fixtures['CaseTypeWithTwoActivityTypes']),
        'CaseTypeWithThreeActivityTypes' => new SimpleXMLElement($this->fixtures['CaseTypeWithThreeActivityTypes']),
        /* healthful noise: */
        'CaseTypeWithSingleRole' => new SimpleXMLElement($this->fixtures['CaseTypeWithSingleRole']),
      )
    );

    // omitted: 'Single Activity Type'
    $expected = array('First Activity Type', 'Second Activity Type', 'Third Activity Type');
    $actual = $repo->getAllDeclaredActivityTypes();
    $this->assertEquals($expected, $actual);
  }

  public function testGetAllDeclaredRelationshipTypes() {
    $repo = new CRM_Case_XMLRepository(
      array('CaseTypeWithTwoRoles', 'CaseTypeWithThreeRoles', 'CaseTypeWithSingleActivityType'),
      array(
        'CaseTypeWithTwoRoles' => new SimpleXMLElement($this->fixtures['CaseTypeWithTwoRoles']),
        'CaseTypeWithThreeRoles' => new SimpleXMLElement($this->fixtures['CaseTypeWithThreeRoles']),
        /* healthful noise: */
        'CaseTypeWithSingleActivityType' => new SimpleXMLElement($this->fixtures['CaseTypeWithSingleActivityType']),
      )
    );
    // omitted: 'Single Role'
    $expected = array('First Role', 'Second Role', 'Third Role');
    $actual = $repo->getAllDeclaredRelationshipTypes();
    $this->assertEquals($expected, $actual);
  }

  public function testGetActivityReferenceCount_1() {
    $repo = new CRM_Case_XMLRepository(
      array('CaseTypeWithSingleActivityType'),
      array(
        'CaseTypeWithSingleActivityType' => new SimpleXMLElement($this->fixtures['CaseTypeWithSingleActivityType']),
        /* healthful noise: */
        'CaseTypeWithSingleRole' => new SimpleXMLElement($this->fixtures['CaseTypeWithSingleRole']),
      )
    );

    $this->assertEquals(1, $repo->getActivityReferenceCount('Single Activity Type'));
    $this->assertEquals(0, $repo->getActivityReferenceCount('First Activity Type'));
    $this->assertEquals(0, $repo->getActivityReferenceCount('Second Activity Type'));
    $this->assertEquals(0, $repo->getActivityReferenceCount('Third Activity Type'));
  }

  public function testGetActivityReferenceCount_23() {
    $repo = new CRM_Case_XMLRepository(
      array('CaseTypeWithTwoActivityTypes', 'CaseTypeWithThreeActivityTypes'),
      array(
        'CaseTypeWithTwoActivityTypes' => new SimpleXMLElement($this->fixtures['CaseTypeWithTwoActivityTypes']),
        'CaseTypeWithThreeActivityTypes' => new SimpleXMLElement($this->fixtures['CaseTypeWithThreeActivityTypes']),
        /* noise: */
        'CaseTypeWithSingleRole' => new SimpleXMLElement($this->fixtures['CaseTypeWithSingleRole']),
      )
    );

    $this->assertEquals(0, $repo->getActivityReferenceCount('Single Activity Type'));
    $this->assertEquals(2, $repo->getActivityReferenceCount('First Activity Type'));
    $this->assertEquals(2, $repo->getActivityReferenceCount('Second Activity Type'));
    $this->assertEquals(1, $repo->getActivityReferenceCount('Third Activity Type'));
  }

  public function testGetRoleReferenceCount_1() {
    $repo = new CRM_Case_XMLRepository(
      array('CaseTypeWithSingleRole', 'CaseTypeWithSingleActivityType'),
      array(
        'CaseTypeWithSingleRole' => new SimpleXMLElement($this->fixtures['CaseTypeWithSingleRole']),
        /* healthful noise: */
        'CaseTypeWithSingleActivityType' => new SimpleXMLElement($this->fixtures['CaseTypeWithSingleActivityType']),
      )
    );

    $this->assertEquals(1, $repo->getRelationshipReferenceCount('Single Role'));
    $this->assertEquals(0, $repo->getRelationshipReferenceCount('First Role'));
    $this->assertEquals(0, $repo->getRelationshipReferenceCount('Second Role'));
    $this->assertEquals(0, $repo->getRelationshipReferenceCount('Third Role'));
  }

  public function testGetRoleReferenceCount_23() {
    $repo = new CRM_Case_XMLRepository(
      array('CaseTypeWithTwoRoles', 'CaseTypeWithThreeRoles', 'CaseTypeWithSingleActivityType'),
      array(
        'CaseTypeWithTwoRoles' => new SimpleXMLElement($this->fixtures['CaseTypeWithTwoRoles']),
        'CaseTypeWithThreeRoles' => new SimpleXMLElement($this->fixtures['CaseTypeWithThreeRoles']),
        /* healthful noise: */
        'CaseTypeWithSingleActivityType' => new SimpleXMLElement($this->fixtures['CaseTypeWithSingleActivityType']),
      )
    );

    $this->assertEquals(0, $repo->getRelationshipReferenceCount('Single Role'));
    $this->assertEquals(2, $repo->getRelationshipReferenceCount('First Role'));
    $this->assertEquals(2, $repo->getRelationshipReferenceCount('Second Role'));
    $this->assertEquals(1, $repo->getRelationshipReferenceCount('Third Role'));
  }

}
