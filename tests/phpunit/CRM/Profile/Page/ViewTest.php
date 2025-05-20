<?php

/**
 * @group headless
 */
class CRM_Profile_Page_ViewTest extends CiviUnitTestCase {

  use CRMTraits_Page_PageTestTrait;

  /**
   * @var int
   */
  protected $contactID;

  /**
   * @var int
   */
  protected $groupID1;

  /**
   * @var int
   */
  protected $groupID2;

  public function setUp(): void {
    parent::setUp();

    $this->contactID = $this->individualCreate();
    $this->groupID1 = $this->groupCreate(['name' => 'g1name', 'title' => 'g1', 'frontend_title' => 'g1front', 'visibility' => 'User and User Admin Only']);
    $this->groupID2 = $this->groupCreate(['name' => 'g2name', 'title' => 'g2', 'frontend_title' => 'g2front', 'visibility' => 'Public Pages']);
    $this->callAPISuccess('GroupContact', 'create', ['group_id' => $this->groupID1, 'contact_id' => $this->contactID, 'status' => 'Added']);
    $this->callAPISuccess('GroupContact', 'create', ['group_id' => $this->groupID2, 'contact_id' => $this->contactID, 'status' => 'Added']);

    $this->listenForPageContent();
  }

  public function tearDown(): void {
    $_GET = $_REQUEST = [];
    $this->callAPISuccess('Contact', 'delete', ['id' => $this->contactID]);
    $this->callAPISuccess('Group', 'delete', ['id' => $this->groupID1]);
    $this->callAPISuccess('Group', 'delete', ['id' => $this->groupID2]);
    parent::tearDown();
  }

  /**
   * The contact overlay is only seen by backend users, and generally they
   * have permission to see both public and admin groups, so we expect the
   * overlay to include both.
   */
  public function testContactOverlayContainsAllGroups(): void {
    $gid = $this->callAPISuccessGetSingle('UFGroup', ['name' => 'summary_overlay', 'return' => ['id']])['id'];
    $_GET = $_REQUEST = ['reset' => 1, 'id' => $this->contactID, 'gid' => $gid, 'is_show_email_task' => 1, 'snippet' => 4];
    $page = new CRM_Profile_Page_View();

    $expectedExit = FALSE;
    try {
      $page->run();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $expectedExit = TRUE;
      $this->assertStringContainsString("Groups\n        </div>\n        <div class=\"content\">\n          g1, g2\n        </div>\n", $this->pageContent);
    }
    if (!$expectedExit) {
      $this->fail('We were expecting an early exit since snippets bypass the theme');
    }
  }

}
