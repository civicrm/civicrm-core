<?php
require_once 'CiviTest/CiviCaseTestCase.php';

/**
 * Class CRM_Case_XMLProcessorTest
 * @group headless
 */
class CRM_Case_XMLProcessorTest extends CiviCaseTestCase {

  public function setUp() {
    parent::setUp();

    $this->processor = new CRM_Case_XMLProcessor();
  }

  /**
   * Test that allRelationshipTypes() doesn't have name and label mixed up.
   */
  public function testAllRelationshipTypes() {

    // Add a relationship type to test against.
    $params = [
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
      'name_a_b' => 'fpt123a',
      'label_a_b' => 'Food poison tester is',
      'name_b_a' => 'fpt123b',
      'label_b_a' => 'Food poison tester for',
      'description' => 'Food poison tester',
    ];
    $result = $this->callAPISuccess('relationship_type', 'create', $params);
    $relationshipTypeID = $result['id'];

    // For false, all we can test against is label, so just check A and B are right (or wrong, depending on your point of view).  Let's not use the words right and wrong let's just call it one way and the other way.
    $relationshipTypes = $this->processor->allRelationshipTypes(FALSE);
    $this->assertEquals('Food poison tester is', $relationshipTypes["{$relationshipTypeID}_a_b"]);
    $this->assertEquals('Food poison tester for', $relationshipTypes["{$relationshipTypeID}_b_a"]);

    // For true, we can test other things.
    // And yes the B and A are the other way around here.
    $relationshipTypes = $this->processor->allRelationshipTypes(TRUE);
    $this->assertEquals('Food poison tester is', $relationshipTypes["{$relationshipTypeID}_b_a"]);
    $this->assertEquals('Food poison tester for', $relationshipTypes["{$relationshipTypeID}_a_b"]);

    $relationshipType = $relationshipTypes[$relationshipTypeID];
    $this->assertEquals('fpt123a', $relationshipType['machineName_b_a']);
    $this->assertEquals('fpt123b', $relationshipType['machineName_a_b']);
    $this->assertEquals('Food poison tester is', $relationshipType['displayLabel_b_a']);
    $this->assertEquals('Food poison tester for', $relationshipType['displayLabel_a_b']);

    // cleanup
    $this->callAPISuccess('relationship_type', 'delete', ['id' => $relationshipTypeID]);
  }

}
