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
 * Test class for CRM_Contact_BAO_GroupContact BAO
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Contact_Page_View_UserDashboard_GroupContactTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
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
    parent::tearDown();
  }

  /**
   * Test that the list of the contact's joined groups, on the Contact Dashboard,
   * contains the correct groups.
   */
  public function testBrowseDisplaysCorrectListOfAddedGroups() {
    // create admin-only non-smart group
    $adminStdGroupTitle = 'The Admin-only Std Group';
    $adminStdGroup = $this->callAPISuccess('Group', 'create', [
      'title' => $adminStdGroupTitle,
      'visibility' => 'User and User Admin Only',
      'is_active' => 1,
    ]);
    // create public non-smart group
    $publicStdGroupTitle = 'The Public Std Group';
    $publicStdGroup = $this->callAPISuccess('Group', 'create', [
      'title' => $publicStdGroupTitle,
      'visibility' => 'Public Pages',
      'is_active' => 1,
    ]);

    // Prepare to create smart groups based on saved criteria Gender = Male.
    // Start by creating the saved search.
    $savedSearch = $this->callAPISuccess('SavedSearch', 'create', [
      'form_values' => 'a:1:{i:0;a:5:{i:0;s:9:"gender_id";i:1;s:1:"=";i:2;i:2;i:3;i:0;i:4;i:0;}}',
    ]);
    // Create contact with Gender - Male
    $savedSearchContact = $this->individualCreate([
      'gender_id' => "Male",
      'first_name' => 'C',
    ], 1);
    // Create admin-only smart group for this saved search.
    $adminSmartGroupTitle = 'The Admin-only Smart Group';
    $adminSmartGroup = $this->callAPISuccess('Group', 'create', [
      'title' => $adminSmartGroupTitle,
      'visibility' => 'User and User Admin Only',
      'saved_search_id' => $savedSearch['id'],
      'is_active' => 1,
    ]);
    // Create public smart group for this saved search.
    $publicSmartGroupTitle = 'The Public Smart Group';
    $publicSmartGroup = $this->callAPISuccess('Group', 'create', [
      'title' => $publicSmartGroupTitle,
      'visibility' => 'Public Pages',
      'saved_search_id' => $savedSearch['id'],
      'is_active' => 1,
    ]);

    // Get logged in user contact ID.
    $user_id = $this->createLoggedInUser();
    $_REQUEST['id'] = $user_id;

    // Add current user to the test groups.
    $publicSmartGroup = $this->callAPISuccess('Contact', 'create', [
      'id' => $user_id,
      'group' => [
        $adminStdGroup['id'] => 1,
        $adminSmartGroup['id'] => 1,
        $publicStdGroup['id'] => 1,
        $publicSmartGroup['id'] => 1,
      ],
    ]);

    // Run the contact dashboard and assert that only the public groups appear
    // in the variables.
    $page = new CRM_Contact_Page_View_UserDashBoard_GroupContact();
    $page->run();

    $groupIn = CRM_Core_Smarty::singleton()->get_template_vars('groupIn');
    $groupInTitles = CRM_Utils_Array::collect('title', $groupIn);
    $this->assertContains($publicSmartGroupTitle, $groupInTitles, "Group '$publicSmartGroupTitle' should be in listed groups, but is not.");
    $this->assertContains($publicStdGroupTitle, $groupInTitles, "Group '$publicStdGroupTitle' should be in listed groups, but is not.");
    $this->assertNotContains($adminSmartGroupTitle, $groupInTitles, "Group '$adminSmartGroupTitle' should not be in listed groups, but is.");
    $this->assertNotContains($adminStdGroupTitle, $groupInTitles, "Group '$adminStdGroupTitle' should not be in listed groups, but is.");
  }

  /**
   * Test that the select list of available groups, on the Contact Dashboard,
   * contains the correct groups.
   */
  public function testBrowseDisplaysCorrectListOfAVailableGroups() {

    // create admin-only non-smart group
    $adminStdGroupTitle = 'The Admin-only Std Group' . uniqid();
    $adminStdGroup = $this->callAPISuccess('Group', 'create', [
      'title' => $adminStdGroupTitle,
      'visibility' => 'User and User Admin Only',
      'is_active' => 1,
    ]);
    // create public non-smart group
    $publicStdGroupTitle = 'The Public Std Group' . uniqid();
    $publicStdGroup = $this->callAPISuccess('Group', 'create', [
      'title' => $publicStdGroupTitle,
      'visibility' => 'Public Pages',
      'is_active' => 1,
    ]);
    // create second public non-smart group
    $publicStdGroupTitle2 = 'The 2nd Public Std Group' . uniqid();
    $publicStdGroup2 = $this->callAPISuccess('Group', 'create', [
      'title' => $publicStdGroupTitle2,
      'visibility' => 'Public Pages',
      'is_active' => 1,
    ]);

    // Prepare to create smart groups based on saved criteria Gender = Male.
    // Start by creating the saved search.
    $savedSearch = $this->callAPISuccess('SavedSearch', 'create', [
      'form_values' => 'a:1:{i:0;a:5:{i:0;s:9:"gender_id";i:1;s:1:"=";i:2;i:2;i:3;i:0;i:4;i:0;}}',
    ]);
    // Create contact with Gender - Male
    $savedSearchContact = $this->individualCreate([
      'gender_id' => "Male",
      'first_name' => 'C',
    ], 1);
    // Create admin-only smart group for this saved search.
    $adminSmartGroupTitle = 'The Admin-only Smart Group' . uniqid();
    $adminSmartGroup = $this->callAPISuccess('Group', 'create', [
      'title' => $adminSmartGroupTitle,
      'visibility' => 'User and User Admin Only',
      'saved_search_id' => $savedSearch['id'],
      'is_active' => 1,
    ]);
    // Create public smart group for this saved search.
    $publicSmartGroupTitle = 'The Public Smart Group' . uniqid();
    $publicSmartGroup = $this->callAPISuccess('Group', 'create', [
      'title' => $publicSmartGroupTitle,
      'visibility' => 'Public Pages',
      'saved_search_id' => $savedSearch['id'],
      'is_active' => 1,
    ]);

    // Get logged in user contact ID.
    $user_id = $this->createLoggedInUser();

    // Run the contact dashboard and assert that only the public groups appear
    // in select list of available groups.
    $_REQUEST['id'] = $user_id;
    $page = new CRM_Contact_Page_View_UserDashBoard_GroupContact();
    $page->run();

    $form = CRM_Core_Smarty::singleton()->get_template_vars('form');
    $group_id_field_html = $form['group_id']['html'];
    $this->assertContains($publicSmartGroupTitle, $group_id_field_html, "Group '$publicSmartGroupTitle' should be in listed available groups, but is not.");
    $this->assertContains($publicStdGroupTitle, $group_id_field_html, "Group '$publicStdGroupTitle' should be in listed available groups, but is not.");
    $this->assertNotContains($adminSmartGroupTitle, $group_id_field_html, "Group '$adminSmartGroupTitle' should not be in listed available groups, but is.");
    $this->assertNotContains($adminStdGroupTitle, $group_id_field_html, "Group '$adminStdGroupTitle' should not be in listed available groups, but is.");

    // Add current user to the test groups.
    $publicSmartGroup = $this->callAPISuccess('Contact', 'create', [
      'id' => $user_id,
      'group' => [
        $adminStdGroup['id'] => 1,
        $adminSmartGroup['id'] => 1,
        $publicStdGroup['id'] => 1,
        $publicSmartGroup['id'] => 1,
      ],
    ]);

    // Run the contact dashboard and assert that none of the groups appear
    // in select list of available groups.
    $_REQUEST['id'] = $user_id;
    $page = new CRM_Contact_Page_View_UserDashBoard_GroupContact();
    $page->run();

    $form = CRM_Core_Smarty::singleton()->get_template_vars('form');
    $group_id_field_html = $form['group_id']['html'];
    $this->assertNotContains($publicSmartGroupTitle, $group_id_field_html, "Group '$publicSmartGroupTitle' should not be in listed available groups, but is.");
    $this->assertNotContains($publicStdGroupTitle, $group_id_field_html, "Group '$publicStdGroupTitle' should not be in listed available groups, but is.");
    $this->assertNotContains($adminSmartGroupTitle, $group_id_field_html, "Group '$adminSmartGroupTitle' should not be in listed available groups, but is.");
    $this->assertNotContains($adminStdGroupTitle, $group_id_field_html, "Group '$adminStdGroupTitle' should not be in listed available groups, but is.");
  }

}
