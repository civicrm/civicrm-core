<?php

/**
 * @group headless
 */
class CRM_Activity_Page_AJAXTest extends CiviCaseTestCase {

  public function setUp(): void {
    parent::setUp();
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
  }

  public function tearDown(): void {
    unset($_SERVER['HTTP_X_REQUESTED_WITH']);
    $_REQUEST = [];
    $_GET = [];
    $_POST = [];
    parent::tearDown();
  }

  /**
   * Test the underlying function that implements file-on-case.
   *
   * The UI is a quick form but it's only realized as a popup ajax form that
   * doesn't have its own postProcess. Instead the values are ultimately
   * passed to the function this test is testing. So there's no form or ajax
   * being tested here, just the final act of filing the activity.
   */
  public function testConvertToCaseActivity(): void {
    $activity = $this->callAPISuccess('Activity', 'create', [
      'source_contact_id' => $this->createLoggedInUser(),
      'activity_type_id' => 'Meeting',
      'subject' => 'test file on case',
      'status_id' => 'Completed',
      'target_id' => $this->individualCreate([], 'target'),
    ]);

    $case = $this->callAPISuccess('Case', 'create', [
      'contact_id' => $this->ids['Contact']['target'],
      'case_type_id' => 'housing_support',
      'subject' => 'Needs housing',
    ]);

    $params = [
      'activityID' => $activity['id'],
      'caseID' => $case['id'],
      'mode' => 'file',
      'targetContactIds' => $this->ids['Contact']['target'],
    ];
    $result = CRM_Activity_Page_AJAX::_convertToCaseActivity($params);

    $this->assertEmpty($result['error_msg']);
    $newActivityId = $result['newId'];

    $caseActivities = $this->callAPISuccess('Activity', 'get', [
      'case_id' => $case['id'],
    ])['values'];
    $this->assertEquals('test file on case', $caseActivities[$newActivityId]['subject']);
    // This should be a different physical activity, not the same db record as the original.
    $this->assertNotEquals($activity['id'], $caseActivities[$newActivityId]['id']);
  }

  /**
   * Similar to testConvertToCaseActivity above but for copy-to-case.
   */
  public function testCopyToCase(): void {
    $case1 = $this->createTestEntity('Case', [
      'contact_id' => $this->individualCreate([], 'target'),
      'case_type_id:name' => 'housing_support',
      'subject' => 'Needs housing',
    ]);
    $contact2 = $this->individualCreate([], 1, TRUE);
    $case2 = $this->callAPISuccess('Case', 'create', [
      'contact_id' => $contact2,
      'case_type_id' => 'housing_support',
      'subject' => 'Also needs housing',
    ]);

    $activity = $this->callAPISuccess('Activity', 'create', [
      'source_contact_id' => $this->createLoggedInUser(),
      'activity_type_id' => 'Meeting',
      'subject' => 'To be copied to case',
      'status_id' => 'Completed',
      'target_id' => $this->ids['Contact']['target'],
      'case_id' => $case1['id'],
    ]);

    $params = [
      'activityID' => $activity['id'],
      'caseID' => $case2['id'],
      'mode' => 'copy',
      'targetContactIds' => $contact2,
    ];
    $result = CRM_Activity_Page_AJAX::_convertToCaseActivity($params);

    $this->assertEmpty($result['error_msg']);
    $newActivityId = $result['newId'];

    $caseActivities = $this->callAPISuccess('Activity', 'get', [
      'case_id' => $case2['id'],
      'return' => ['id', 'subject', 'target_contact_id'],
    ])['values'];
    $this->assertEquals('To be copied to case', $caseActivities[$newActivityId]['subject']);
    $this->assertEquals($contact2, $caseActivities[$newActivityId]['target_contact_id'][0]);
    // This should be a different physical activity, not the same db record as the original.
    $this->assertNotEquals($activity['id'], $caseActivities[$newActivityId]['id']);

    // original should still be on old case
    $originalActivity = $this->callAPISuccess('Activity', 'getsingle', [
      'id' => $activity['id'],
      'return' => ['is_deleted', 'case_id'],
    ]);
    $this->assertEquals(0, $originalActivity['is_deleted']);
    $this->assertEquals($case1['id'], $originalActivity['case_id'][0]);
  }

  /**
   * Similar to testCopyToCase above but for move-to-case.
   */
  public function testMoveToCase(): void {
    $case1 = $this->callAPISuccess('Case', 'create', [
      'contact_id' => $this->individualCreate([], 'target'),
      'case_type_id' => 'housing_support',
      'subject' => 'Needs housing',
    ]);
    $contact2 = $this->individualCreate([], 1, TRUE);
    $case2 = $this->callAPISuccess('Case', 'create', [
      'contact_id' => $contact2,
      'case_type_id' => 'housing_support',
      'subject' => 'Also needs housing',
    ]);

    $activity = $this->callAPISuccess('Activity', 'create', [
      'source_contact_id' => $this->createLoggedInUser(),
      'activity_type_id' => 'Meeting',
      'subject' => 'To be moved to case',
      'status_id' => 'Completed',
      'target_id' => $this->ids['Contact']['target'],
      'case_id' => $case1['id'],
    ]);

    $params = [
      'activityID' => $activity['id'],
      'caseID' => $case2['id'],
      'mode' => 'move',
      'targetContactIds' => $contact2,
    ];
    $result = CRM_Activity_Page_AJAX::_convertToCaseActivity($params);

    $this->assertEmpty($result['error_msg']);
    $newActivityId = $result['newId'];

    $caseActivities = $this->callAPISuccess('Activity', 'get', [
      'case_id' => $case2['id'],
      'return' => ['id', 'subject', 'target_contact_id'],
    ])['values'];
    $this->assertEquals('To be moved to case', $caseActivities[$newActivityId]['subject']);
    $this->assertEquals($contact2, $caseActivities[$newActivityId]['target_contact_id'][0]);
    // This should be a different physical activity, not the same db record as the original.
    $this->assertNotEquals($activity['id'], $caseActivities[$newActivityId]['id']);

    // original should be marked deleted
    $originalActivity = $this->callAPISuccess('Activity', 'getsingle', [
      'id' => $activity['id'],
      'return' => ['is_deleted', 'case_id'],
    ]);
    $this->assertEquals(1, $originalActivity['is_deleted']);
    $this->assertEquals($case1['id'], $originalActivity['case_id'][0]);
  }

  /**
   * Check if the selected filters are saved.
   */
  public function testPreserveFilters(): void {
    \Civi::settings()->set('preserve_activity_tab_filter', '1');

    // Simulate visiting activity tab with all the filters set to something
    $_GET = $_REQUEST = [
      'snippet' => '4',
      'context' => 'activity',
      // Need a logged in user since the filter is per-contact, but this
      // cid is the visited contact so could be anything, but might as well
      // use this one.
      'cid' => $this->createLoggedInUser(),
      'draw' => '5',
      'columns' => [
        0 => [
          'data' => 'activity_type',
          'name' => '',
          'searchable' => 'true',
          'orderable' => 'true',
          'search' => [
            'value' => '',
            'regex' => 'false',
          ],
        ],
        1 => [
          'data' => 'subject',
          'name' => '',
          'searchable' => 'true',
          'orderable' => 'true',
          'search' => [
            'value' => '',
            'regex' => 'false',
          ],
        ],
        2 => [
          'data' => 'source_contact_name',
          'name' => '',
          'searchable' => 'true',
          'orderable' => 'true',
          'search' => [
            'value' => '',
            'regex' => 'false',
          ],
        ],
        3 => [
          'data' => 'target_contact_name',
          'name' => '',
          'searchable' => 'true',
          'orderable' => 'false',
          'search' => [
            'value' => '',
            'regex' => 'false',
          ],
        ],
        4 => [
          'data' => 'assignee_contact_name',
          'name' => '',
          'searchable' => 'true',
          'orderable' => 'false',
          'search' => [
            'value' => '',
            'regex' => 'false',
          ],
        ],
        5 => [
          'data' => 'activity_date_time',
          'name' => '',
          'searchable' => 'true',
          'orderable' => 'true',
          'search' => [
            'value' => '',
            'regex' => 'false',
          ],
        ],
        6 => [
          'data' => 'status_id',
          'name' => '',
          'searchable' => 'true',
          'orderable' => 'true',
          'search' => [
            'value' => '',
            'regex' => 'false',
          ],
        ],
        7 => [
          'data' => 'links',
          'name' => '',
          'searchable' => 'true',
          'orderable' => 'false',
          'search' => [
            'value' => '',
            'regex' => 'false',
          ],
        ],
      ],
      'start' => '0',
      'length' => '25',
      'search' => [
        'value' => '',
        'regex' => 'false',
      ],
      // Meeting
      'activity_type_id' => [0 => '1'],
      // Phone call
      'activity_type_exclude_id' => [0 => '2'],
      'activity_date_time_relative' => 'this.month',
      'activity_date_time_low' => '',
      'activity_date_time_high' => '',
      // Completed
      'activity_status_id' => '2',
    ];
    try {
      CRM_Activity_Page_AJAX::getContactActivity();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      // Check that the filter is what we expect
      $this->assertEquals([
        'activity_type_filter_id' => [0 => 1],
        'activity_type_exclude_filter_id' => [0 => 2],
        'activity_status_id' => '2',
        'status_id' => [0 => '2'],
        'activity_date_time_relative' => 'this.month',
      ], \Civi::contactSettings()->get('activity_tab_filter'));
    }

    // clean up
    \Civi::settings()->set('preserve_activity_tab_filter', '0');
    $_GET = $_REQUEST = [];
  }

  /**
   * Test convertToCaseActivity endpoint succeeds when user has required permissions.
   */
  public function testConvertToCaseActivityEndpointSuccess(): void {
    $loggedInUserId = $this->createLoggedInUser();
    $this->setPermissions([
      'access CiviCRM',
      'access all cases and activities',
      'edit all contacts',
      'view all contacts',
    ]);

    $targetContactId = $this->individualCreate([], 'target');
    $activity = $this->callAPISuccess('Activity', 'create', [
      'source_contact_id' => $loggedInUserId,
      'activity_type_id' => 'Meeting',
      'subject' => 'Meeting for case',
      'status_id' => 'Completed',
      'target_id' => $targetContactId,
    ]);

    $case = $this->callAPISuccess('Case', 'create', [
      'contact_id' => $targetContactId,
      'case_type_id' => 'housing_support',
      'subject' => 'Housing assistance',
    ]);

    $_POST = $_REQUEST = [
      'activityID' => $activity['id'],
      'caseID' => $case['id'],
      'mode' => 'file',
      'targetContactIds' => (string) $targetContactId,
    ];

    try {
      CRM_Activity_Page_AJAX::convertToCaseActivity();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertNotEmpty($e->errorData['newId']);
      $newActivityId = $e->errorData['newId'];
    }

    $caseActivities = $this->callAPISuccess('Activity', 'get', [
      'case_id' => $case['id'],
      'id' => $newActivityId,
    ])['values'];
    $this->assertCount(1, $caseActivities);
    $this->assertEquals('Meeting for case', $caseActivities[$newActivityId]['subject']);
  }

  /**
   * Test convertToCaseActivity endpoint is denied when user lacks case permission.
   */
  public function testConvertToCaseActivityEndpointDeniedWithoutCasePermission(): void {
    $ownerId = $this->individualCreate([], 'owner');
    $targetContactId = $this->individualCreate([], 'target');

    $activity = $this->callAPISuccess('Activity', 'create', [
      'source_contact_id' => $ownerId,
      'activity_type_id' => 'Meeting',
      'subject' => 'Meeting to file',
      'status_id' => 'Completed',
      'target_id' => $targetContactId,
    ]);

    $case = $this->callAPISuccess('Case', 'create', [
      'contact_id' => $targetContactId,
      'case_type_id' => 'housing_support',
      'subject' => 'Housing assistance',
    ]);

    $this->createLoggedInUser();
    // User only has basic access CiviCRM, no case permissions
    $this->setPermissions(['access CiviCRM']);

    $_POST = $_REQUEST = [
      'activityID' => $activity['id'],
      'caseID' => $case['id'],
      'mode' => 'file',
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Activity_Page_AJAX::convertToCaseActivity();
  }

  /**
   * Test convertToCaseActivity endpoint is denied when user lacks activity edit permission.
   */
  public function testConvertToCaseActivityEndpointDeniedWithoutActivityPermission(): void {
    $otherUserId = $this->individualCreate([], 'other');
    $targetContactId = $this->individualCreate([], 'target');

    $activity = $this->callAPISuccess('Activity', 'create', [
      'source_contact_id' => $otherUserId,
      'activity_type_id' => 'Meeting',
      'subject' => 'Meeting to move',
      'status_id' => 'Completed',
      'target_id' => $targetContactId,
    ]);

    $case = $this->callAPISuccess('Case', 'create', [
      'contact_id' => $targetContactId,
      'case_type_id' => 'housing_support',
      'subject' => 'Housing assistance',
    ]);

    $this->createLoggedInUser();
    // User has case access, but lacks edit all contacts / permission to edit other contact's activity
    $this->setPermissions([
      'access CiviCRM',
      'access all cases and activities',
    ]);

    $_POST = $_REQUEST = [
      'activityID' => $activity['id'],
      'caseID' => $case['id'],
      'mode' => 'move',
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Activity_Page_AJAX::convertToCaseActivity();
  }

  /**
   * Test convertToCaseActivity endpoint is denied with invalid parameters.
   */
  public function testConvertToCaseActivityEndpointInvalidParamsDenied(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access all cases and activities']);

    $_POST = $_REQUEST = [
      'activityID' => 1,
      'caseID' => 'invalid_case_id',
      'mode' => 'file',
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Activity_Page_AJAX::convertToCaseActivity();
  }

  /**
   * Test getCaseRoles is denied when caseID is missing or invalid.
   */
  public function testGetCaseRolesMissingCaseIdDenied(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access all cases and activities']);

    $_GET = $_REQUEST = [];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Activity_Page_AJAX::getCaseRoles();
  }

  /**
   * Test getCaseRoles is denied when user lacks permission to access the case.
   */
  public function testGetCaseRolesForbiddenCaseDenied(): void {
    $targetContactId = $this->individualCreate([], 'target_contact');
    $case = $this->callAPISuccess('Case', 'create', [
      'contact_id' => $targetContactId,
      'case_type_id' => 'housing_support',
      'subject' => 'Needs housing',
    ]);
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM']);

    $_GET = $_REQUEST = [
      'caseID' => $case['id'],
      'cid' => $targetContactId,
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Activity_Page_AJAX::getCaseRoles();
  }

  /**
   * Test getCaseRoles succeeds when user has permission to access the case.
   */
  public function testGetCaseRolesPermittedSuccess(): void {
    $targetContactId = $this->individualCreate([], 'target_contact');
    $case = $this->callAPISuccess('Case', 'create', [
      'contact_id' => $targetContactId,
      'case_type_id' => 'housing_support',
      'subject' => 'Needs housing',
    ]);
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access all cases and activities', 'view all contacts']);

    $_GET = $_REQUEST = [
      'caseID' => $case['id'],
      'cid' => $targetContactId,
    ];

    try {
      CRM_Activity_Page_AJAX::getCaseRoles();
      $this->fail('Expected CRM_Core_Exception_PrematureExitException');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $data = $e->errorData;
      $this->assertIsArray($data);
      $this->assertArrayHasKey('data', $data);
      $this->assertArrayHasKey('recordsTotal', $data);
    }
  }

  /**
   * Test getCaseClientRelationships is denied when caseID is missing or invalid.
   */
  public function testGetCaseClientRelationshipsMissingCaseIdDenied(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access all cases and activities']);

    $_GET = $_REQUEST = [];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Activity_Page_AJAX::getCaseClientRelationships();
  }

  /**
   * Test getCaseClientRelationships is denied when user lacks permission to access the case.
   */
  public function testGetCaseClientRelationshipsForbiddenCaseDenied(): void {
    $targetContactId = $this->individualCreate([], 'target_contact');
    $case = $this->callAPISuccess('Case', 'create', [
      'contact_id' => $targetContactId,
      'case_type_id' => 'housing_support',
      'subject' => 'Needs housing',
    ]);
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM']);

    $_GET = $_REQUEST = [
      'caseID' => $case['id'],
      'cid' => $targetContactId,
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Activity_Page_AJAX::getCaseClientRelationships();
  }

  /**
   * Test getCaseClientRelationships succeeds when user has permission to access the case.
   */
  public function testGetCaseClientRelationshipsPermittedSuccess(): void {
    $targetContactId = $this->individualCreate([], 'target_contact');
    $case = $this->callAPISuccess('Case', 'create', [
      'contact_id' => $targetContactId,
      'case_type_id' => 'housing_support',
      'subject' => 'Needs housing',
    ]);
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access all cases and activities', 'view all contacts']);

    $_GET = $_REQUEST = [
      'caseID' => $case['id'],
      'cid' => $targetContactId,
    ];

    try {
      CRM_Activity_Page_AJAX::getCaseClientRelationships();
      $this->fail('Expected CRM_Core_Exception_PrematureExitException');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $data = $e->errorData;
      $this->assertIsArray($data);
      $this->assertArrayHasKey('data', $data);
      $this->assertArrayHasKey('recordsTotal', $data);
    }
  }

  /**
   * Test getCaseActivity is denied when caseID is missing or invalid.
   */
  public function testGetCaseActivityMissingCaseIdDenied(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access all cases and activities']);

    $_GET = $_REQUEST = [];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Activity_Page_AJAX::getCaseActivity();
  }

  /**
   * Test getCaseActivity is denied when user lacks permission to access the case.
   */
  public function testGetCaseActivityForbiddenCaseDenied(): void {
    $targetContactId = $this->individualCreate([], 'target_contact');
    $case = $this->callAPISuccess('Case', 'create', [
      'contact_id' => $targetContactId,
      'case_type_id' => 'housing_support',
      'subject' => 'Needs housing',
    ]);
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM']);

    $_GET = $_REQUEST = [
      'caseID' => $case['id'],
      'cid' => $targetContactId,
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Activity_Page_AJAX::getCaseActivity();
  }

  /**
   * Test getCaseActivity succeeds when user has permission to access the case.
   */
  public function testGetCaseActivityPermittedSuccess(): void {
    $targetContactId = $this->individualCreate([], 'target_contact');
    $case = $this->callAPISuccess('Case', 'create', [
      'contact_id' => $targetContactId,
      'case_type_id' => 'housing_support',
      'subject' => 'Needs housing',
    ]);
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access all cases and activities', 'view all contacts']);

    $_GET = $_REQUEST = [
      'caseID' => $case['id'],
      'cid' => $targetContactId,
    ];

    try {
      CRM_Activity_Page_AJAX::getCaseActivity();
      $this->fail('Expected CRM_Core_Exception_PrematureExitException');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $data = $e->errorData;
      $this->assertIsArray($data);
      $this->assertArrayHasKey('data', $data);
      $this->assertArrayHasKey('recordsTotal', $data);
    }
  }

  /**
   * Test getCaseGlobalRelationships is denied without case permissions.
   */
  public function testGetCaseGlobalRelationshipsDeniedWithoutPermission(): void {
    $cid = $this->individualCreate();
    $case = $this->callAPISuccess('Case', 'create', [
      'contact_id' => $cid,
      'case_type_id' => 'housing_support',
      'subject' => 'Global relationships case',
    ]);
    $this->setPermissions(['access CiviCRM']);

    $_GET = $_REQUEST = [
      'caseID' => $case['id'],
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Activity_Page_AJAX::getCaseGlobalRelationships();
  }

  /**
   * Test getCaseGlobalRelationships succeeds when user has case access.
   */
  public function testGetCaseGlobalRelationshipsSuccess(): void {
    $cid = $this->individualCreate();
    $case = $this->callAPISuccess('Case', 'create', [
      'contact_id' => $cid,
      'case_type_id' => 'housing_support',
      'subject' => 'Global relationships case',
    ]);
    $this->setPermissions(['access CiviCRM', 'access all cases and activities', 'view all contacts']);

    $_GET = $_REQUEST = [
      'caseID' => $case['id'],
    ];

    try {
      CRM_Activity_Page_AJAX::getCaseGlobalRelationships();
      $this->fail('Expected CRM_Core_Exception_PrematureExitException');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $data = $e->errorData;
      $this->assertIsArray($data);
      $this->assertArrayHasKey('data', $data);
      $this->assertArrayHasKey('recordsTotal', $data);
    }
  }

  /**
   * Test getContactActivity is denied when user lacks view permission on contact.
   */
  public function testGetContactActivityForbiddenContactDenied(): void {
    $targetContactId = $this->individualCreate();
    $this->setPermissions(['access CiviCRM']);

    $_GET = $_REQUEST = [
      'cid' => $targetContactId,
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Activity_Page_AJAX::getContactActivity();
  }

  /**
   * Test getContactActivity succeeds when user has permission to view contact.
   */
  public function testGetContactActivityPermittedSuccess(): void {
    $targetContactId = $this->individualCreate([], 'target_contact');
    $this->setPermissions(['access CiviCRM', 'view all contacts']);

    $_GET = $_REQUEST = [
      'cid' => $targetContactId,
    ];

    try {
      CRM_Activity_Page_AJAX::getContactActivity();
      $this->fail('Expected CRM_Core_Exception_PrematureExitException');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $data = $e->errorData;
      $this->assertIsArray($data);
      $this->assertArrayHasKey('data', $data);
      $this->assertArrayHasKey('recordsTotal', $data);
    }
  }

}
