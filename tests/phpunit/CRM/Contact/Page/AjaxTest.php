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

  public function setUp() {
    $this->useTransaction(TRUE);
    parent::setUp();
    $this->originalRequest = $_REQUEST;
  }

  public function tearDown() {
    $_REQUEST = $this->originalRequest;
    parent::tearDown();
  }

  /**
   * Minimal test on the testGetDupes function to make sure it completes without error.
   */
  public function testGetDedupes() {
    $_REQUEST['gid'] = 1;
    $_REQUEST['rgid'] = 1;
    $_REQUEST['columns'] = array(
      array(
        'search' => array(
          'value' => array(
            'src' => 'first_name',
          ),
        ),
        'data' => 'src',
      ),
    );
    $_REQUEST['is_unit_test'] = TRUE;
    $result = CRM_Contact_Page_AJAX::getDedupes();
    $this->assertEquals(array('data' => array(), 'recordsTotal' => 0, 'recordsFiltered' => 0), $result);
  }

  /**
   * Tests the 'guts' of the processDupes function.
   *
   * @throws \Exception
   */
  public function testProcessDupes() {
    $contact1 = $this->individualCreate();
    $contact2 = $this->individualCreate();
    $contact3 = $this->individualCreate();
    CRM_Contact_Page_AJAX::markNonDuplicates($contact1, $contact2, 'dupe-nondupe');
    CRM_Contact_Page_AJAX::markNonDuplicates($contact3, $contact1, 'dupe-nondupe');
    $this->callAPISuccessGetSingle('Exception', ['contact_id1' => $contact1, 'contact_id2' => $contact2]);
    // Note that in saving the order is changed to have the lowest ID first.
    $this->callAPISuccessGetSingle('Exception', ['contact_id1' => $contact1, 'contact_id2' => $contact3]);
  }

  public function testGetDedupesPostCode() {
    $_REQUEST['gid'] = 1;
    $_REQUEST['rgid'] = 1;
    $_REQUEST['snippet'] = 4;
    $_REQUEST['draw'] = 3;
    $_REQUEST['columns'] = array(
      0 => array(
        'data' => 'is_selected_input',
        'name' => '',
        'searchable' => TRUE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      1 => array(
        'data' => 'src_image',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => FALSE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      2 => array(
        'data' => 'src',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      3 => array(
        'data' => 'dst_image',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => FALSE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      4 => array(
        'data' => 'dst',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      5 => array(
        'data' => 'src_email',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      6 => array(
        'data' => 'dst_email',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      7 => array(
        'data' => 'src_street',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      8 => array(
        'data' => 'dst_street',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      9 => array(
        'data' => 'src_postcode',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => array(
          'value' => 123,
          'regex' => FALSE,
        ),
      ),

      10 => array(
        'data' => 'dst_postcode',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      11 => array(
        'data' => 'conflicts',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      12 => array(
        'data' => 'weight',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => TRUE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),

      13 => array(
        'data' => 'actions',
        'name' => '',
        'searchable' => TRUE,
        'orderable' => FALSE,
        'search' => array(
          'value' => '',
          'regex' => FALSE,
        ),
      ),
    );

    $_REQUEST['start'] = 0;
    $_REQUEST['length'] = 10;
    $_REQUEST['search'] = array(
      'value' => '',
      'regex' => FALSE,
    );

    $_REQUEST['_'] = 1466478641007;
    $_REQUEST['Drupal_toolbar_collapsed'] = 0;
    $_REQUEST['has_js'] = 1;
    $_REQUEST['SESSa06550b3043ecca303761d968e3c846a'] = 'qxSxw0F_UmBITMM0JaVwTRcHV1bQqBSHNmBMY9AA8Wk';

    $_REQUEST['is_unit_test'] = TRUE;

    $result = CRM_Contact_Page_AJAX::getDedupes();
    $this->assertEquals(array('data' => array(), 'recordsTotal' => 0, 'recordsFiltered' => 0), $result);
  }

  /**
   * CRM-20621 : Test to check usage count of Tag tree
   */
  public function testGetTagTree() {
    $contacts = array();
    // create three contacts
    for ($i = 0; $i < 3; $i++) {
      $contacts[] = $this->individualCreate();
    }

    // Create Tag called as 'Parent Tag'
    $parentTag = $this->tagCreate(array(
      'name' => 'Parent Tag',
      'used_for' => 'civicrm_contact',
    ));
    //assign first contact to parent tag
    $params = array(
      'entity_id' => $contacts[0],
      'entity_table' => 'civicrm_contact',
      'tag_id' => $parentTag['id'],
    );
    // TODO: EntityTag.create API is not working
    CRM_Core_BAO_EntityTag::add($params);

    // Create child Tag of $parentTag
    $childTag1 = $this->tagCreate(array(
      'name' => 'Child Tag Level 1',
      'parent_id' => $parentTag['id'],
      'used_for' => 'civicrm_contact',
    ));
    //assign contact to this level 1 child tag
    $params = array(
      'entity_id' => $contacts[1],
      'entity_table' => 'civicrm_contact',
      'tag_id' => $childTag1['id'],
    );
    CRM_Core_BAO_EntityTag::add($params);

    // Create child Tag of $childTag1
    $childTag2 = $this->tagCreate(array(
      'name' => 'Child Tag Level 2',
      'parent_id' => $childTag1['id'],
      'used_for' => 'civicrm_contact',
    ));
    //assign contact to this level 2 child tag
    $params = array(
      'entity_id' => $contacts[2],
      'entity_table' => 'civicrm_contact',
      'tag_id' => $childTag2['id'],
    );
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
    $expectedTagIDs = array($parentTag['id'], $childTag1['id'], $childTag2['id']);
    $this->checkArrayEquals($tagIDs, $expectedTagIDs);

    // CASE 4 : check the tag IDs returned on searching with 'Level 1'
    //  which needs to array('parent tag id', 'level 1 child tag id')
    $_GET['str'] = 'Level 1';
    $tagIDs = CRM_Admin_Page_AJAX::getTagTree();
    $expectedTagIDs = array($parentTag['id'], $childTag1['id']);
    $this->checkArrayEquals($tagIDs, $expectedTagIDs);

    //cleanup
    foreach ($contacts as $id) {
      $this->callAPISuccess('Contact', 'delete', array('id' => $id));
    }
    $this->callAPISuccess('Tag', 'delete', array('id' => $childTag2['id']));
    $this->callAPISuccess('Tag', 'delete', array('id' => $childTag1['id']));
    $this->callAPISuccess('Tag', 'delete', array('id' => $parentTag['id']));
  }

  /**
   * Test to check contact reference field
   */
  public function testContactReference() {
    //create group
    $groupId1 = $this->groupCreate();
    $groupId2 = $this->groupCreate(array(
      'name' => 'Test Group 2',
      'domain_id' => 1,
      'title' => 'New Test Group2 Created',
      'description' => 'New Test Group2 Created',
      'is_active' => 1,
      'visibility' => 'User and User Admin Only',
    ));

    $contactIds = array();
    foreach (array($groupId1, $groupId2) as $groupId) {
      $this->groupContactCreate($groupId);
      $contactIds = array_merge($contactIds, CRM_Contact_BAO_Group::getGroupContacts($groupId));
    }
    $contactIds = CRM_Utils_Array::collect('contact_id', $contactIds);

    // create custom group with contact reference field
    $customGroup = $this->customGroupCreate(array('extends' => 'Contact', 'title' => 'select_test_group'));
    $params = array(
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
    );
    $customField = $this->callAPISuccess('custom_field', 'create', $params);

    $_GET = array(
      'id' => $customField['id'],
      'is_unit_test' => TRUE,
    );
    $contactList = CRM_Contact_Page_AJAX::contactReference();
    $contactList = CRM_Utils_Array::collect('id', $contactList);

    //assert each returned contact id to be present in group contact
    foreach ($contactList as $contactId) {
      $this->assertTrue(in_array($contactId, $contactIds));
    }
  }

}
