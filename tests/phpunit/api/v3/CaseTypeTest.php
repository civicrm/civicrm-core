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
 * Class api_v3_CaseTypeTest
 * @group headless
 */
class api_v3_CaseTypeTest extends CiviCaseTestCase {

  public function setUp() {
    $this->quickCleanup(['civicrm_case_type']);
    parent::setUp();

    $this->fixtures['Application_with_Definition'] = [
      'title' => 'Application with Definition',
      'name' => 'Application_with_Definition',
      'is_active' => 1,
      'weight' => 4,
      'definition' => [
        'activityTypes' => [
          ['name' => 'First act'],
        ],
        'activitySets' => [
          [
            'name' => 'set1',
            'label' => 'Label 1',
            'timeline' => 1,
            'activityTypes' => [
              ['name' => 'Open Case', 'status' => 'Completed'],
            ],
          ],
        ],
        'timelineActivityTypes' => [
          ['name' => 'Open Case', 'status' => 'Completed'],
        ],
        'caseRoles' => [
          ['name' => 'First role', 'creator' => 1, 'manager' => 1],
        ],
      ],
    ];
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   *
   * This method is called after a test is executed.
   */
  public function tearDown() {
    parent::tearDown();
    $this->quickCleanup(['civicrm_case_type', 'civicrm_uf_match']);
  }

  /**
   * Check with empty array.
   */
  public function testCaseTypeCreateEmpty() {
    $this->callAPIFailure('CaseType', 'create', []);
  }

  /**
   * Check if required fields are not passed.
   */
  public function testCaseTypeCreateWithoutRequired() {
    $params = [
      'name' => 'this case should fail',
    ];
    $this->callAPIFailure('CaseType', 'create', $params);

    $params = [
      'name' => 'this case should fail',
      'weight' => 4,
    ];
    $this->callAPIFailure('CaseType', 'create', $params);
  }

  /**
   * Test create methods with valid data.
   *
   * Success expected.
   */
  public function testCaseTypeCreate() {
    // Create Case Type.
    $params = [
      'title' => 'Application',
      'name' => 'Application',
      'is_active' => 1,
      'weight' => 4,
    ];

    $result = $this->callAPISuccess('CaseType', 'create', $params);
    $id = $result['id'];

    // Check result.
    $result = $this->callAPISuccess('CaseType', 'get', ['id' => $id]);
    $this->assertEquals($result['values'][$id]['id'], $id);
    $this->assertEquals($result['values'][$id]['title'], $params['title']);
  }

  /**
   * Create a case with an invalid name.
   */
  public function testCaseTypeCreate_invalidName() {
    // Create Case Type
    $params = [
      'title' => 'Application',
      // spaces are not allowed
      'name' => 'Appl ication',
      'is_active' => 1,
      'weight' => 4,
    ];

    $this->callAPIFailure('CaseType', 'create', $params);
  }

  /**
   * Test update (create with id) function with valid parameters.
   */
  public function testCaseTypeUpdate() {
    // Create Case Type
    $params = [
      'title' => 'Application',
      'name' => 'Application',
      'is_active' => 1,
      'weight' => 4,
    ];
    $result = $this->callAPISuccess('CaseType', 'create', $params);
    $id = $result['id'];
    $result = $this->callAPISuccess('CaseType', 'get', ['id' => $id]);
    $caseType = $result['values'][$id];

    // Update Case Type.
    $params = ['id' => $id];
    $params['title'] = $caseType['title'] = 'Something Else';
    $this->callAPISuccess('CaseType', 'create', $params);

    // Verify that updated case Type is exactly equal to the original with new title.
    $result = $this->callAPISuccess('CaseType', 'get', ['id' => $id]);
    $this->assertEquals($result['values'][$id], $caseType);
  }

  /**
   * Test delete function with valid parameters.
   */
  public function testCaseTypeDelete_New() {
    // Create Case Type.
    $params = [
      'title' => 'Application',
      'name' => 'Application',
      'is_active' => 1,
      'weight' => 4,
    ];
    $result = $this->callAPISuccess('CaseType', 'create', $params);

    $id = $result['id'];
    $this->callAPISuccess('CaseType', 'delete', ['id' => $id]);

    // Check result - case type should no longer exist
    $result = $this->callAPISuccess('CaseType', 'get', ['id' => $id]);
    $this->assertEquals(0, $result['count']);
  }

  /**
   * Test create methods with xml file.
   *
   * Success expected.
   */
  public function testCaseTypeCreateWithDefinition() {
    // Create Case Type
    $params = $this->fixtures['Application_with_Definition'];
    $result = $this->callAPISuccess('CaseType', 'create', $params);
    $id = $result['id'];

    // Check result
    $result = $this->callAPISuccess('CaseType', 'get', ['id' => $id]);
    $this->assertEquals($result['values'][$id]['id'], $id);
    $this->assertEquals($result['values'][$id]['title'], $params['title']);
    $this->assertEquals($result['values'][$id]['definition'], $params['definition']);

    $caseXml = CRM_Case_XMLRepository::singleton()->retrieve('Application_with_Definition');
    $this->assertTrue($caseXml instanceof SimpleXMLElement);
  }

  /**
   * Create a CaseType+case then delete the CaseType.
   */
  public function testCaseTypeDelete_InUse() {
    // Create Case Type
    $params = $this->fixtures['Application_with_Definition'];
    $createCaseType = $this->callAPISuccess('CaseType', 'create', $params);

    $createCase = $this->callAPISuccess('Case', 'create', [
      'case_type_id' => $createCaseType['id'],
      'contact_id' => $this->_loggedInUser,
      'subject' => 'Example',
    ]);

    // Deletion fails while case-type is in-use
    $deleteCaseType = $this->callAPIFailure('CaseType', 'delete', ['id' => $createCaseType['id']]);
    $this->assertEquals("You can not delete this case type -- it is assigned to 1 existing case record(s). If you do not want this case type to be used going forward, consider disabling it instead.", $deleteCaseType['error_message']);
    $getCaseType = $this->callAPISuccess('CaseType', 'get', ['id' => $createCaseType['id']]);
    $this->assertEquals(1, $getCaseType['count']);

    // Deletion succeeds when it's not in-use.
    $this->callAPISuccess('Case', 'delete', ['id' => $createCase['id']]);

    // Check result - case type should no longer exist.
    $this->callAPISuccess('CaseType', 'delete', ['id' => $createCaseType['id']]);
    $getCaseType = $this->callAPISuccess('CaseType', 'get', ['id' => $createCaseType['id']]);
    $this->assertEquals(0, $getCaseType['count']);
  }

  /**
   * Test the api returns case statuses filtered by case type.
   *
   * Api getoptions should respect the case statuses declared in the case type definition.
   *
   * @throws \Exception
   */
  public function testCaseStatusByCaseType() {
    $statusName = md5(mt_rand());
    $template = $this->callAPISuccess('CaseType', 'getsingle', ['id' => $this->caseTypeId]);
    unset($template['id']);
    $template['name'] = $template['title'] = 'test_case_type';
    $template['definition']['statuses'] = ['Closed', $statusName];
    $this->callAPISuccess('CaseType', 'create', $template);
    $this->callAPISuccess('OptionValue', 'create', [
      'option_group_id' => 'case_status',
      'name' => $statusName,
      'label' => $statusName,
      'weight' => 99,
    ]);
    $result = $this->callAPISuccess('Case', 'getoptions', ['field' => 'status_id', 'case_type_id' => 'test_case_type', 'context' => 'validate']);
    $this->assertEquals($template['definition']['statuses'], array_values($result['values']));
  }

  public function testDefinitionGroups() {
    $gid1 = $this->groupCreate(['name' => 'testDefinitionGroups1', 'title' => 'testDefinitionGroups1']);
    $gid2 = $this->groupCreate(['name' => 'testDefinitionGroups2', 'title' => 'testDefinitionGroups2']);
    $def = $this->fixtures['Application_with_Definition'];
    $def['definition']['caseRoles'][] = [
      'name' => 'Second role',
      'groups' => ['testDefinitionGroups1', 'testDefinitionGroups2'],
    ];
    $def['definition']['caseRoles'][] = [
      'name' => 'Third role',
      'groups' => 'testDefinitionGroups2',
    ];
    $def['definition']['activityAsgmtGrps'] = $gid1;
    $createCaseType = $this->callAPISuccess('CaseType', 'create', $def);
    $caseType = $this->callAPISuccess('CaseType', 'getsingle', ['id' => $createCaseType['id']]);

    // Assert the group id got converted to array with name not id
    $this->assertEquals(['testDefinitionGroups1'], $caseType['definition']['activityAsgmtGrps']);

    // Assert multiple groups are stored
    $this->assertEquals(['testDefinitionGroups1', 'testDefinitionGroups2'], $caseType['definition']['caseRoles'][1]['groups']);

    // Assert single group got converted to array
    $this->assertEquals(['testDefinitionGroups2'], $caseType['definition']['caseRoles'][2]['groups']);

  }

}
