<?php

/**
 * @group headless
 */
class CRM_Contact_Page_AjaxTest extends CiviUnitTestCase {

  /**
   * Original $_REQUEST
   *
   * We are messing with globals so fix afterwards.
   *
   * @var array
   */
  protected $originalRequest = [];

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->originalRequest = $_REQUEST;
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
  }

  public function tearDown(): void {
    unset($_SERVER['HTTP_X_REQUESTED_WITH']);
    $_REQUEST = $this->originalRequest;
    $_GET = [];
    $_POST = [];
    parent::tearDown();
  }

  /**
   * Minimal test on the testGetDupes function to make sure it completes without error.
   */
  public function testGetDedupes(): void {
    $_REQUEST['gid'] = 1;
    $_REQUEST['rgid'] = 1;
    $_REQUEST['columns'] = [
      [
        'search' => [
          'value' => 'first_name',
          'regex' => FALSE,
        ],
        'data' => 'src',
      ],
    ];
    $_REQUEST['is_unit_test'] = TRUE;
    $result = CRM_Contact_Page_AJAX::getDedupes();
    $this->assertEquals(['data' => [], 'recordsTotal' => 0, 'recordsFiltered' => 0], $result);
  }

  /**
   * Test flipDupePairs works via route invocation.
   */
  public function testFlipDupePairs(): void {
    $dao = new CRM_Core_BAO_PrevNextCache();
    $dao->entity_id1 = 1;
    $dao->entity_id2 = 2;
    $dao->is_selected = 1;
    $dao->data = serialize([
      'srcID' => 1,
      'srcName' => 'Ms. Melissa Mouse II',
      'dstID' => 2,
      'dstName' => 'Mr. Maurice Mouse II',
      'weight' => 20,
      'canMerge' => TRUE,
    ]);
    $dao->save();

    $_REQUEST['pnid'] = [$dao->id];

    try {
      CRM_Contact_Page_AJAX::flipDupePairs();
      $this->fail('Expected CRM_Core_Exception_PrematureExitException');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      // Expected from civiExit
    }

    $daoReloaded = new CRM_Core_BAO_PrevNextCache();
    $daoReloaded->id = $dao->id;
    $daoReloaded->find(TRUE);
    $data = unserialize($daoReloaded->data);
    $this->assertEquals(2, $data['srcID']);
    $this->assertEquals(1, $data['dstID']);

    // Also test single scalar pnid
    $_REQUEST['pnid'] = $dao->id;
    try {
      CRM_Contact_Page_AJAX::flipDupePairs();
      $this->fail('Expected CRM_Core_Exception_PrematureExitException');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      // Expected from civiExit
    }

    $daoReloaded = new CRM_Core_BAO_PrevNextCache();
    $daoReloaded->id = $dao->id;
    $daoReloaded->find(TRUE);
    $data = unserialize($daoReloaded->data);
    $this->assertEquals(1, $data['srcID']);
    $this->assertEquals(2, $data['dstID']);
  }

  /**
   * Tests the 'guts' of the processDupes function.
   *
   * @throws \Exception
   */
  public function testProcessDupes(): void {
    $contact1 = $this->individualCreate();
    $contact2 = $this->individualCreate();
    $contact3 = $this->individualCreate();
    CRM_Contact_Page_AJAX::markNonDuplicates($contact1, $contact2, 'dupe-nondupe');
    CRM_Contact_Page_AJAX::markNonDuplicates($contact3, $contact1, 'dupe-nondupe');
    $this->callAPISuccessGetSingle('Exception', ['contact_id1' => $contact1, 'contact_id2' => $contact2]);
    // Note that in saving the order is changed to have the lowest ID first.
    $this->callAPISuccessGetSingle('Exception', ['contact_id1' => $contact1, 'contact_id2' => $contact3]);
  }

  public function testGetDedupesPostCode(): void {
    $_REQUEST['gid'] = 1;
    $_REQUEST['rgid'] = 1;
    $_REQUEST['snippet'] = 4;
    $_REQUEST['draw'] = 3;
    $_REQUEST['columns'] = [
      0 => [
        'data' => 'is_selected_input',
        'name' => '',
        'searchable' => TRUE,
        'search' => [
          'value' => '',
          'regex' => FALSE,
        ],
      ],

      1 => [
        'data' => 'src_image',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => FALSE,
        'search' => [
          'value' => '',
          'regex' => FALSE,
        ],
      ],

      2 => [
        'data' => 'src',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => [
          'value' => '',
          'regex' => FALSE,
        ],
      ],

      3 => [
        'data' => 'dst_image',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => FALSE,
        'search' => [
          'value' => '',
          'regex' => FALSE,
        ],
      ],

      4 => [
        'data' => 'dst',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => [
          'value' => '',
          'regex' => FALSE,
        ],
      ],

      5 => [
        'data' => 'src_email',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => [
          'value' => '',
          'regex' => FALSE,
        ],
      ],

      6 => [
        'data' => 'dst_email',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => [
          'value' => '',
          'regex' => FALSE,
        ],
      ],

      7 => [
        'data' => 'src_street',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => [
          'value' => '',
          'regex' => FALSE,
        ],
      ],

      8 => [
        'data' => 'dst_street',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => [
          'value' => '',
          'regex' => FALSE,
        ],
      ],

      9 => [
        'data' => 'src_postcode',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => [
          'value' => 123,
          'regex' => FALSE,
        ],
      ],

      10 => [
        'data' => 'dst_postcode',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => [
          'value' => '',
          'regex' => FALSE,
        ],
      ],

      11 => [
        'data' => 'conflicts',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => [
          'value' => '',
          'regex' => FALSE,
        ],
      ],

      12 => [
        'data' => 'weight',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => [
          'value' => '',
          'regex' => FALSE,
        ],
      ],

      13 => [
        'data' => 'actions',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => FALSE,
        'search' => [
          'value' => '',
          'regex' => FALSE,
        ],
      ],
    ];

    $_REQUEST['start'] = 0;
    $_REQUEST['length'] = 10;
    $_REQUEST['search'] = [
      'value' => '',
      'regex' => FALSE,
    ];

    $_REQUEST['_'] = 1466478641007;
    $_REQUEST['Drupal_toolbar_collapsed'] = 0;
    $_REQUEST['has_js'] = 1;
    $_REQUEST['SESSa06550b3043ecca303761d968e3c846a'] = 'qxSxw0F_UmBITMM0JaVwTRcHV1bQqBSHNmBMY9AA8Wk';

    $_REQUEST['is_unit_test'] = TRUE;

    $result = CRM_Contact_Page_AJAX::getDedupes();
    $this->assertEquals(['data' => [], 'recordsTotal' => 0, 'recordsFiltered' => 0], $result);
  }

  /**
   * CRM-20621 : Test to check usage count of Tag tree
   */
  public function testGetTagTree(): void {
    $contacts = [];
    // create three contacts
    for ($i = 0; $i < 3; $i++) {
      $contacts[] = $this->individualCreate();
    }

    // Create Tag called as 'Parent Tag'
    $parentTag = $this->tagCreate([
      'name' => 'Parent Tag',
      'used_for' => 'civicrm_contact',
    ]);
    //assign first contact to parent tag
    $params = [
      'entity_id' => $contacts[0],
      'entity_table' => 'civicrm_contact',
      'tag_id' => $parentTag['id'],
    ];
    // TODO: EntityTag.create API is not working
    CRM_Core_BAO_EntityTag::add($params);

    // Create child Tag of $parentTag
    $childTag1 = $this->tagCreate([
      'name' => 'Child Tag Level 1',
      'parent_id' => $parentTag['id'],
      'used_for' => 'civicrm_contact',
    ]);
    //assign contact to this level 1 child tag
    $params = [
      'entity_id' => $contacts[1],
      'entity_table' => 'civicrm_contact',
      'tag_id' => $childTag1['id'],
    ];
    CRM_Core_BAO_EntityTag::add($params);

    // Create child Tag of $childTag1
    $childTag2 = $this->tagCreate([
      'name' => 'Child Tag Level 2',
      'parent_id' => $childTag1['id'],
      'used_for' => 'civicrm_contact',
    ]);
    //assign contact to this level 2 child tag
    $params = [
      'entity_id' => $contacts[2],
      'entity_table' => 'civicrm_contact',
      'tag_id' => $childTag2['id'],
    ];
    CRM_Core_BAO_EntityTag::add($params);

    // CASE I : check the usage count of parent tag which need to be 1
    //  as the one contact added
    $_REQUEST['is_unit_test'] = TRUE;
    $parentTagTreeResult = CRM_Admin_Page_AJAX::getTagTree();
    foreach ($parentTagTreeResult as $result) {
      if ($result['id'] == $parentTag['id']) {
        $this->assertEquals(1, $result['data']['usages']);
      }
    }

    // CASE 2 : check the usage count of level 1 child tag, which needs to be 1
    //  as it should include the count of added one contact
    $_GET['parent_id'] = $parentTag['id'];
    $childTagTree = CRM_Admin_Page_AJAX::getTagTree();
    $this->assertEquals(1, $childTagTree[0]['data']['usages']);

    // CASE 2 : check the usage count of child tag at level 2
    //which needs to be 1 as it has no child tag
    $_GET['parent_id'] = $childTag1['id'];
    $childTagTree = CRM_Admin_Page_AJAX::getTagTree();
    $this->assertEquals(1, $childTagTree[0]['data']['usages']);

    // CASE 3 : check the tag IDs returned on searching with 'Level'
    //  which needs to array('parent tag id', 'level 1 child tag id', 'level 2 child tag id')
    unset($_GET['parent_id']);
    $_GET['str'] = 'Level';
    $tagIDs = CRM_Admin_Page_AJAX::getTagTree();
    $expectedTagIDs = [$parentTag['id'], $childTag1['id'], $childTag2['id']];
    $this->checkArrayEquals($tagIDs, $expectedTagIDs);

    // CASE 4 : check the tag IDs returned on searching with 'Level 1'
    //  which needs to array('parent tag id', 'level 1 child tag id')
    $_GET['str'] = 'Level 1';
    $tagIDs = CRM_Admin_Page_AJAX::getTagTree();
    $expectedTagIDs = [$parentTag['id'], $childTag1['id']];
    $this->checkArrayEquals($tagIDs, $expectedTagIDs);

    //cleanup
    foreach ($contacts as $id) {
      $this->callAPISuccess('Contact', 'delete', ['id' => $id]);
    }
    $this->callAPISuccess('Tag', 'delete', ['id' => $childTag2['id']]);
    $this->callAPISuccess('Tag', 'delete', ['id' => $childTag1['id']]);
    $this->callAPISuccess('Tag', 'delete', ['id' => $parentTag['id']]);
  }

  /**
   * Test to check contact reference field
   */
  public function testContactReference(): void {
    //create group
    $groupId1 = $this->groupCreate();
    $groupId2 = $this->groupCreate([
      'name' => 'Test Group 2',
      'domain_id' => 1,
      'title' => 'New Test Group2 Created',
      'description' => 'New Test Group2 Created',
      'is_active' => 1,
      'visibility' => 'User and User Admin Only',
    ]);

    $contactIds = [];
    foreach ([$groupId1, $groupId2] as $groupId) {
      $this->groupContactCreate($groupId);
      $contactIds = array_merge($contactIds, CRM_Contact_BAO_Group::getGroupContacts($groupId));
    }
    $contactIds = CRM_Utils_Array::collect('contact_id', $contactIds);

    // create custom group with contact reference field
    $customGroup = $this->customGroupCreate(['extends' => 'Contact', 'title' => 'select_test_group']);
    $params = [
      'custom_group_id' => $customGroup['id'],
      'name' => 'Worker_Lookup',
      'label' => 'Worker Lookup',
      // limit this field to two groups created above
      'filter' => "action=lookup&group={$groupId1},{$groupId2}",
      'html_type' => 'Autocomplete-Select',
      'data_type' => 'ContactReference',
      'weight' => 4,
      'is_searchable' => 1,
      'is_active' => 1,
    ];
    $customField = $this->callAPISuccess('custom_field', 'create', $params);

    $_GET = [
      'id' => $customField['id'],
      'is_unit_test' => TRUE,
    ];
    $contactList = CRM_Contact_Page_AJAX::contactReference();
    $contactList = CRM_Utils_Array::collect('id', $contactList);

    //assert each returned contact id to be present in group contact
    foreach ($contactList as $contactId) {
      $this->assertTrue(in_array($contactId, $contactIds));
    }
  }

  /**
   * Test toggleDedupeSelect succeeds for a single pair when user has permission.
   */
  public function testToggleDedupeSelectSuccess(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'merge duplicate contacts']);

    $contact1 = $this->individualCreate([], 'c1');
    $contact2 = $this->individualCreate([], 'c2');
    $cacheKey = 'merge_Individual_1_0_0_test123';

    CRM_Core_DAO::executeQuery(
      "INSERT INTO civicrm_prevnext_cache (entity_table, entity_id1, entity_id2, cachekey, is_selected) VALUES ('civicrm_contact', %1, %2, %3, 0)",
      [1 => [$contact1, 'Positive'], 2 => [$contact2, 'Positive'], 3 => [$cacheKey, 'String']]
    );
    $pnid = CRM_Core_DAO::singleValueQuery(
      "SELECT id FROM civicrm_prevnext_cache WHERE cachekey = %1",
      [1 => [$cacheKey, 'String']]
    );

    $_POST = $_REQUEST = [
      'cacheKey' => $cacheKey,
      'pnid' => $pnid,
      'is_selected' => 1,
    ];

    try {
      CRM_Contact_Page_AJAX::toggleDedupeSelect();

    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals('success', $e->errorData['status']);
    }

    $isSelected = CRM_Core_DAO::singleValueQuery(
      "SELECT is_selected FROM civicrm_prevnext_cache WHERE id = %1",
      [1 => [$pnid, 'Integer']]
    );
    $this->assertEquals(1, $isSelected);
  }

  /**
   * Test toggleDedupeSelect succeeds for multiple pairs.
   */
  public function testToggleDedupeSelectMultipleSuccess(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'merge duplicate contacts']);

    $contact1 = $this->individualCreate([], 'c1');
    $contact2 = $this->individualCreate([], 'c2');
    $contact3 = $this->individualCreate([], 'c3');
    $cacheKey = 'merge_Individual_1_0_0_testmulti';

    CRM_Core_DAO::executeQuery(
      "INSERT INTO civicrm_prevnext_cache (entity_table, entity_id1, entity_id2, cachekey, is_selected) VALUES ('civicrm_contact', %1, %2, %3, 0)",
      [1 => [$contact1, 'Positive'], 2 => [$contact2, 'Positive'], 3 => [$cacheKey, 'String']]
    );
    $pnid1 = CRM_Core_DAO::singleValueQuery(
      "SELECT id FROM civicrm_prevnext_cache WHERE entity_id1 = %1 AND entity_id2 = %2 AND cachekey = %3",
      [1 => [$contact1, 'Positive'], 2 => [$contact2, 'Positive'], 3 => [$cacheKey, 'String']]
    );

    CRM_Core_DAO::executeQuery(
      "INSERT INTO civicrm_prevnext_cache (entity_table, entity_id1, entity_id2, cachekey, is_selected) VALUES ('civicrm_contact', %1, %2, %3, 0)",
      [1 => [$contact1, 'Positive'], 2 => [$contact3, 'Positive'], 3 => [$cacheKey, 'String']]
    );
    $pnid2 = CRM_Core_DAO::singleValueQuery(
      "SELECT id FROM civicrm_prevnext_cache WHERE entity_id1 = %1 AND entity_id2 = %2 AND cachekey = %3",
      [1 => [$contact1, 'Positive'], 2 => [$contact3, 'Positive'], 3 => [$cacheKey, 'String']]
    );

    $_POST = $_REQUEST = [
      'cacheKey' => $cacheKey,
      'pnid' => [$pnid1, $pnid2],
      'is_selected' => 1,
    ];

    try {
      CRM_Contact_Page_AJAX::toggleDedupeSelect();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals('success', $e->errorData['status']);
    }

    $selectedCount = CRM_Core_DAO::singleValueQuery(
      "SELECT count(*) FROM civicrm_prevnext_cache WHERE cachekey = %1 AND is_selected = 1",
      [1 => [$cacheKey, 'String']]
    );
    $this->assertEquals(2, $selectedCount);
  }

  /**
   * Test toggleDedupeSelect does not update rows if cacheKey only partially matches.
   */
  public function testToggleDedupeSelectPartialCacheKeyDoesNotMatch(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'merge duplicate contacts']);

    $contact1 = $this->individualCreate([], 'c1');
    $contact2 = $this->individualCreate([], 'c2');
    $cacheKey = 'merge_Individual_1_0_0_test123';

    CRM_Core_DAO::executeQuery(
      "INSERT INTO civicrm_prevnext_cache (entity_table, entity_id1, entity_id2, cachekey, is_selected) VALUES ('civicrm_contact', %1, %2, %3, 0)",
      [1 => [$contact1, 'Positive'], 2 => [$contact2, 'Positive'], 3 => [$cacheKey, 'String']]
    );
    $pnid = CRM_Core_DAO::singleValueQuery(
      "SELECT id FROM civicrm_prevnext_cache WHERE cachekey = %1",
      [1 => [$cacheKey, 'String']]
    );

    $_POST = $_REQUEST = [
      'cacheKey' => 'm',
      'pnid' => $pnid,
      'is_selected' => 1,
    ];

    try {
      CRM_Contact_Page_AJAX::toggleDedupeSelect();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals('success', $e->errorData['status']);
    }

    $isSelected = CRM_Core_DAO::singleValueQuery(
      "SELECT is_selected FROM civicrm_prevnext_cache WHERE id = %1",
      [1 => [$pnid, 'Integer']]
    );
    $this->assertEquals(0, $isSelected, 'Row should not be updated when cacheKey is only a prefix.');
  }

  /**
   * Test toggleDedupeSelect is denied without cacheKey or pnid.
   */
  public function testToggleDedupeSelectDeniedWithoutCacheKey(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'merge duplicate contacts']);

    $_POST = $_REQUEST = [
      'pnid' => 1,
      'is_selected' => 1,
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('Could not find valid value for cacheKey');
    CRM_Contact_Page_AJAX::toggleDedupeSelect();
  }

  /**
   * Test processDupes endpoint succeeds for creating and removing dedupe exceptions.
   */
  public function testProcessDupesEndpointSuccess(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'merge duplicate contacts', 'view all contacts']);

    $contact1 = $this->individualCreate([], 'd1');
    $contact2 = $this->individualCreate([], 'd2');

    // 1. Mark as not duplicate
    $_POST = $_REQUEST = [
      'op' => 'dupe-nondupe',
      'cid' => $contact1,
      'oid' => $contact2,
    ];

    try {
      CRM_Contact_Page_AJAX::processDupes();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals('dupe-nondupe', $e->errorData['status']);
    }

    $c1 = min($contact1, $contact2);
    $c2 = max($contact1, $contact2);
    $exceptionExists = CRM_Core_DAO::singleValueQuery(
      "SELECT count(*) FROM civicrm_dedupe_exception WHERE contact_id1 = %1 AND contact_id2 = %2",
      [1 => [$c1, 'Positive'], 2 => [$c2, 'Positive']]
    );
    $this->assertEquals(1, $exceptionExists);

    // 2. Remove exception
    $_POST = $_REQUEST = [
      'op' => 'nondupe-dupe',
      'cid' => $contact1,
      'oid' => $contact2,
    ];

    try {
      CRM_Contact_Page_AJAX::processDupes();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals('nondupe-dupe', $e->errorData['status']);
    }

    $exceptionExistsAfter = CRM_Core_DAO::singleValueQuery(
      "SELECT count(*) FROM civicrm_dedupe_exception WHERE contact_id1 = %1 AND contact_id2 = %2",
      [1 => [$c1, 'Positive'], 2 => [$c2, 'Positive']]
    );
    $this->assertEquals(0, $exceptionExistsAfter);
  }

  /**
   * Test processDupes endpoint is denied when contacts do not exist.
   */
  public function testProcessDupesEndpointDeniedWithInvalidContacts(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'merge duplicate contacts']);

    $_POST = $_REQUEST = [
      'op' => 'dupe-nondupe',
      'cid' => 999999,
      'oid' => 999998,
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Contact_Page_AJAX::processDupes();
  }

  /**
   * Test getAddressDisplay is denied when contact_id is missing or invalid.
   */
  public function testGetAddressDisplayMissingContactIdDenied(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'view all contacts']);

    $_POST = $_REQUEST = [];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Contact_Page_AJAX::getAddressDisplay();
  }

  /**
   * Test getAddressDisplay is denied when user lacks permission to view contact.
   */
  public function testGetAddressDisplayForbiddenContactDenied(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM']);
    $contactId = $this->individualCreate([], 'target_contact');

    $_POST = $_REQUEST = ['contact_id' => $contactId];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Contact_Page_AJAX::getAddressDisplay();
  }

  /**
   * Test getAddressDisplay succeeds when user has permission to view contact.
   */
  public function testGetAddressDisplayPermittedContactSuccess(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'view all contacts']);
    $contactId = $this->individualCreate([], 'target_contact');
    $this->createTestEntity('Address', [
      'contact_id' => $contactId,
      'street_address' => '123 Fake Street',
      'city' => 'Metropolis',
      'location_type_id:name' => 'Home',
    ], 'addr_1');

    $_POST = $_REQUEST = ['contact_id' => $contactId];

    try {
      CRM_Contact_Page_AJAX::getAddressDisplay();
      $this->fail('Expected CRM_Core_Exception_PrematureExitException');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $data = $e->errorData;
      $this->assertIsArray($data);
      $this->assertNotEmpty($data);
      $firstAddress = reset($data);
      $this->assertStringContainsString('123 Fake Street', $firstAddress['display_text']);
    }
  }

  /**
   * Test getPCPList succeeds with CiviContribute permission.
   */
  public function testGetPCPListPermittedSuccess(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviContribute', 'view all contacts']);
    $contactId = $this->individualCreate(['first_name' => 'Supporter', 'last_name' => 'Jones'], 'pcp_supporter');
    $contribPage = $this->createTestEntity('ContributionPage', [
      'title' => 'PCP Goal Page',
      'financial_type_id:name' => 'Donation',
    ], 'pcp_contrib_page');
    $pcpBlock = CRM_PCP_BAO_PCPBlock::writeRecord([
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $contribPage['id'],
      'target_entity_id' => 1,
      'is_active' => 1,
    ]);
    $pcp = $this->createTestEntity('PCP', [
      'contact_id' => $contactId,
      'title' => 'Save the Whales',
      'page_id' => $contribPage['id'],
      'page_type' => 'contribute',
      'status_id' => 1,
      'pcp_block_id' => $pcpBlock->id,
    ], 'pcp_entity');

    $_GET['term'] = 'Jones';

    try {
      CRM_Contact_Page_AJAX::getPCPList();
      $this->fail('Expected CRM_Core_Exception_PrematureExitException');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $data = $e->errorData;
      $this->assertIsArray($data);
      $this->assertNotEmpty($data['results']);
      $this->assertEquals($pcp['id'], $data['results'][0]['id']);
      $this->assertStringContainsString('Save the Whales', $data['results'][0]['text']);
    }
  }

  /**
   * Test getPCPList respects contact ACLs when user cannot view supporter contact.
   */
  public function testGetPCPListContactAclEnforced(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviContribute']);
    $contactId = $this->individualCreate(['first_name' => 'Hidden', 'last_name' => 'Supporter'], 'pcp_hidden');
    $contribPage = $this->createTestEntity('ContributionPage', [
      'title' => 'PCP Goal Page 2',
      'financial_type_id:name' => 'Donation',
    ], 'pcp_contrib_page2');
    $pcpBlock = CRM_PCP_BAO_PCPBlock::writeRecord([
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $contribPage['id'],
      'target_entity_id' => 1,
      'is_active' => 1,
    ]);
    $this->createTestEntity('PCP', [
      'contact_id' => $contactId,
      'title' => 'Hidden PCP Page',
      'page_id' => $contribPage['id'],
      'page_type' => 'contribute',
      'status_id' => 1,
      'pcp_block_id' => $pcpBlock->id,
    ], 'pcp_entity2');

    $_GET['term'] = 'Hidden';

    try {
      CRM_Contact_Page_AJAX::getPCPList();
      $this->fail('Expected CRM_Core_Exception_PrematureExitException');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $data = $e->errorData;
      $this->assertIsArray($data);
      $this->assertEmpty($data['results']);
    }
  }

  /**
   * Helper to set up a multi-record custom group and field.
   */
  private function createMultiRecordCustomGroup(string $extends = 'Contact'): array {
    $groupName = uniqid('cg_');
    $fieldName = uniqid('cf_');
    $customGroup = $this->createTestEntity('CustomGroup', [
      'title' => $groupName,
      'name' => $groupName,
      'extends' => $extends,
      'is_multiple' => TRUE,
    ], 'custom_group_' . $groupName);

    $this->createTestEntity('CustomField', [
      'label' => $fieldName,
      'name' => $fieldName,
      'custom_group_id' => $customGroup['id'],
      'html_type' => 'Text',
      'data_type' => 'String',
    ], 'custom_field_' . $fieldName);

    return [$customGroup, $groupName, $fieldName];
  }

  /**
   * Test successful deletion of a custom value by authorized user.
   */
  public function testDeleteCustomValueSuccess(): void {
    [$customGroup, $groupName, $fieldName] = $this->createMultiRecordCustomGroup();

    $contactId = $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'edit my contact', 'access all custom data']);

    $customValueId = civicrm_api4("Custom_{$groupName}", 'create', [
      'checkPermissions' => FALSE,
      'values' => [
        'entity_id' => $contactId,
        $fieldName => 'User Data',
      ],
    ])->first()['id'];

    $_REQUEST['valueID'] = $customValueId;
    $_REQUEST['groupID'] = $customGroup['id'];
    $_REQUEST['contactId'] = $contactId;

    ob_start();
    try {
      CRM_Contact_Page_AJAX::deleteCustomValue();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      // Expected exit from AJAX endpoint
    }
    ob_end_clean();

    $remaining = civicrm_api4("Custom_{$groupName}", 'get', [
      'checkPermissions' => FALSE,
      'where' => [['id', '=', $customValueId]],
    ]);
    $this->assertCount(0, $remaining);
  }

  /**
   * Test that an attacker cannot delete another contact's custom value using their own contact ID.
   */
  public function testDeleteCustomValueDeniedForOtherContact(): void {
    [$customGroup, $groupName, $fieldName] = $this->createMultiRecordCustomGroup();

    $userContactId = $this->createLoggedInUser();
    $victimContactId = $this->createTestEntity('Contact', [
      'contact_type' => 'Individual',
      'first_name' => 'Victim',
    ], 'victim')['id'];

    $this->setPermissions(['access CiviCRM', 'edit my contact', 'access all custom data']);

    $victimCustomValueId = civicrm_api4("Custom_{$groupName}", 'create', [
      'checkPermissions' => FALSE,
      'values' => [
        'entity_id' => $victimContactId,
        $fieldName => 'Victim Secret',
      ],
    ])->first()['id'];

    $_REQUEST['valueID'] = $victimCustomValueId;
    $_REQUEST['groupID'] = $customGroup['id'];
    $_REQUEST['contactId'] = $userContactId;

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');

    CRM_Contact_Page_AJAX::deleteCustomValue();
  }

  /**
   * Test that deletion is denied if user lacks edit permission on the contact.
   */
  public function testDeleteCustomValueDeniedWithoutContactEditPermission(): void {
    [$customGroup, $groupName, $fieldName] = $this->createMultiRecordCustomGroup();

    $contactId = $this->createTestEntity('Contact', [
      'contact_type' => 'Individual',
      'first_name' => 'ViewOnly',
    ], 'view_only')['id'];

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'view all contacts', 'access all custom data']);

    $customValueId = civicrm_api4("Custom_{$groupName}", 'create', [
      'checkPermissions' => FALSE,
      'values' => [
        'entity_id' => $contactId,
        $fieldName => 'View Only Data',
      ],
    ])->first()['id'];

    $_REQUEST['valueID'] = $customValueId;
    $_REQUEST['groupID'] = $customGroup['id'];
    $_REQUEST['contactId'] = $contactId;

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');

    CRM_Contact_Page_AJAX::deleteCustomValue();
  }

  /**
   * Test that deletion is denied if custom group does not extend Contact.
   */
  public function testDeleteCustomValueDeniedForNonContactGroup(): void {
    [$customGroup, $groupName, $fieldName] = $this->createMultiRecordCustomGroup('Activity');

    $contactId = $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'edit all contacts', 'access all custom data']);

    $activity = $this->createTestEntity('Activity', [
      'source_contact_id' => $contactId,
      'activity_type_id' => 'Meeting',
      'subject' => 'Meeting Test',
    ], 'activity');

    $customValueId = civicrm_api4("Custom_{$groupName}", 'create', [
      'checkPermissions' => FALSE,
      'values' => [
        'entity_id' => $activity['id'],
        $fieldName => 'Activity Data',
      ],
    ])->first()['id'];

    $_REQUEST['valueID'] = $customValueId;
    $_REQUEST['groupID'] = $customGroup['id'];
    $_REQUEST['contactId'] = $contactId;

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');

    CRM_Contact_Page_AJAX::deleteCustomValue();
  }

}
