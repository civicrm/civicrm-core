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
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

require_once 'CiviTest/CiviSeleniumTestCase.php';

/**
 * Class WebTest_Profile_MultiRecordProfileAddTest
 */
class WebTest_Profile_MultiRecordProfileAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAdminAddNewProfile() {
    $this->webtestLogin();
    list($id, $profileTitle) = $this->_addNewProfile();
    $this->_deleteProfile($id, $profileTitle);
  }

  public function testUserAddNewProfile() {
    //add the required permission
    $permissions = array(
      'edit-2-profile-listings-and-forms',
      'edit-2-access-all-custom-data',
      'edit-2-access-civicrm',
    );
    $this->changePermissions($permissions);
    list($id, $profileTitle) = $this->_addNewProfile(TRUE, FALSE, TRUE);
    $this->_deleteProfile($id, $profileTitle);
  }

  public function testAddNewNonMultiProfile() {
    $this->webtestLogin();
    list($id, $profileTitle) = $this->_addNewProfile(FALSE);
    $this->_deleteProfile($id, $profileTitle);
  }

  public function testNonSearchableMultiProfile() {
    $this->webtestLogin();
    list($id, $profileTitle) = $this->_addNewProfile(TRUE, TRUE);
    $this->_deleteProfile($id, $profileTitle);
  }

  /**
   * @param bool $checkMultiRecord
   * @param bool $checkSearchable
   * @param bool $userCheck
   *
   * @return array
   */
  public function _addNewProfile($checkMultiRecord = TRUE, $checkSearchable = FALSE, $userCheck = FALSE) {
    $params = $this->_testCustomAdd($checkSearchable);

    $this->openCiviPage('admin/uf/group', 'reset=1');

    $this->click('newCiviCRMProfile-top');
    $this->waitForElementPresent('_qf_Group_next-bottom');

    //Name of profile
    $profileTitle = 'profile_' . substr(sha1(rand()), 0, 7);
    $this->type('title', $profileTitle);

    $this->click('uf_group_type_Profile');
    //profile Used for
    $this->click('uf_group_type_User Account');

    //Profile Advance Settings
    $this->click("//form[@id='Group']/div[2]/div[2]/div[1]");

    //Drupal user account registration option
    $this->click('CIVICRM_QFID_0_8');

    //What to do upon duplicate match
    $this->click('CIVICRM_QFID_1_4');

    //Proximity search options
    $this->click('CIVICRM_QFID_0_14');

    // enable maping for contact
    $this->click('is_map');

    // include a link in the listings to Edit profile fields
    $this->click('is_edit_link');

    //to view contacts' Drupal user account information
    $this->click('is_uf_link');

    //click on save
    $this->clickLink('_qf_Group_next', NULL, TRUE);

    //check for  profile create
    $this->waitForText('crm-notification-container', "Your CiviCRM Profile '{$profileTitle}' has been added. You can add fields to this profile now.");

    $gid = $this->urlArg('gid');

    $this->openCiviPage('admin/uf/group/field/add', array(
        'action' => 'add',
        'reset' => 1,
        'gid' => $gid,
      ), 'field_name[0]');

    //Add field to profile
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Contact');
    $this->click("//option[@value='Contact']");
    $this->click('field_name_1');
    $this->select('field_name_1', 'label=' . $params['textFieldLabel'] . ' :: ' . $params['customGroupTitle']);
    if ($checkMultiRecord) {
      $this->click('is_multi_summary');
    }
    if (!$checkSearchable) {
      $this->click('is_searchable');
      $this->select('visibility', 'value=Public Pages and Listings');
    }
    else {
      $this->select('visibility', 'value=User and User Admin Only');
    }
    $this->click('in_selector');
    $this->click('is_required');
    $this->type('help_post', 'This is help for profile field');
    $this->click('_qf_Field_next_new-top');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Contact');
    $this->click("//option[@value='Contact']");
    $this->click('field_name_1');
    $this->select('field_name_1', 'label=' . $params['selectFieldLabel'] . ' :: ' . $params['customGroupTitle']);
    if ($checkMultiRecord) {
      $this->click('is_multi_summary');
    }
    $this->select('visibility', 'value=Public Pages and Listings');
    $this->click('is_searchable');
    $this->click('in_selector');
    $this->type('help_post', 'This is help for profile field');
    $this->click('_qf_Field_next_new-top');

    // Add Contact
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Student');
    $this->click("//option[@value='Student']");

    $this->click('field_name_1');
    $this->select('field_name_1', 'value=first_name');
    $this->click('is_multi_summary');
    $this->select('visibility', 'value=Public Pages and Listings');
    $this->click('is_searchable');
    $this->click('in_selector');
    $this->type('help_post', 'This is help for profile field');
    $this->click('_qf_Field_next_new-top');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Student');
    $this->click("//option[@value='Student']");
    $this->click('field_name_1');
    $this->select('field_name_1', 'value=last_name');
    $this->select('visibility', 'value=Public Pages and Listings');
    $this->click('is_searchable');
    $this->click('in_selector');
    $this->type('help_post', 'This is help for profile field');
    $this->clickLink('_qf_Field_next_new-top');

    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Contact');
    $this->click("//option[@value='Contact']");
    $this->click('field_name_1');
    $this->select('field_name_1', 'value=email');
    $this->select('visibility', 'value=Public Pages and Listings');
    $this->click('is_searchable');
    $this->type('help_post', 'This is help for profile field');
    $this->clickLink('_qf_Field_next');

    $uselink = explode('?', $this->getAttribute("xpath=//*[@id='field_page']/div[1]/a[4]@href"));
    $this->openCiviPage('profile/create', "$uselink[1]", '_qf_Edit_next');
    $recordNew = $this->_addRecords('Create');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $id = $this->urlArg('id');

    if ($userCheck) {
      //add drupal user
      $this->openCiviPage('contact/view/useradd', "reset=1&action=add&cid=$id", 'cms_name');
      $this->type('cms_name', $recordNew['firstname']);
      $this->type('cms_pass', $recordNew['firstname']);
      $this->type('cms_confirm_pass', $recordNew['firstname']);
      $this->click('_qf_Useradd_next-bottom');
      $this->waitForPageToLoad($this->getTimeoutMsec());
      $this->webtestLogout();

      $this->webtestLogin($recordNew['firstname'], $recordNew['firstname']);
    }
    $this->openCiviPage('profile/edit', "reset=1&id=$id&gid=$gid", NULL);
    if (!$checkMultiRecord) {
      $this->assertElementContainsText('crm-container', 'No records');
      return array($gid, $profileTitle);
    }
    $this->waitForElementPresent("//a/span[contains(text(), 'Add New Record')]");
    $this->click("//a/span[contains(text(), 'Add New Record')]");
    $this->waitForElementPresent("_qf_Edit_next");
    $record1 = $this->_addRecords('Edit', TRUE);
    $this->waitForElementPresent("//div[@id='custom--table-wrapper']/div/div/table/tbody/tr[2]/td[1]");
    $alertText = $this->getAlert();
    $this->assertEquals("Thank you. Your information has been saved.", $alertText);
    $this->waitForElementPresent("//a/span[contains(text(), 'Add New Record')]");
    $this->verifyText("//div[@id='custom--table-wrapper']/div/div/table/thead/tr/th[1]", preg_quote($params['textFieldLabel']));
    $this->verifyText("//div[@id='custom--table-wrapper']/div/div/table/tbody/tr[2]/td[1]", preg_quote($record1['text']));
    $this->click("//a/span[contains(text(), 'Add New Record')]");
    $this->waitForElementPresent("_qf_Edit_next");
    $record2 = $this->_addRecords('Edit', TRUE);
    $this->waitForElementPresent("//div[@id='custom--table-wrapper']/div/div/table/tbody/tr[3]/td[1]");
    $alertText = $this->getAlert();
    $this->assertEquals("Thank you. Your information has been saved.", $alertText);
    $this->waitForElementPresent("//div[@id='custom--table-wrapper']/div/div/table/tbody/tr[3]/td[1]");
    $this->verifyText("//div[@id='custom--table-wrapper']/div/div/table/tbody/tr[3]/td[1]", preg_quote($record2['text']));

    // Check Max Record Limit
    $this->verifyElementNotPresent("//a/span[contains(text(), 'Add New Record')]");

    //Check for edit functionality
    $this->click("//div[@id='custom--table-wrapper']/div/div/table/tbody/tr/td[3]/span/a[text()='Edit']");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-content ui-widget-content modal-dialog crm-ajax-container']/form/div[2]//div[@id='crm-profile-block']");
    $this->verifyText("//div[@id='custom--table-wrapper']/div/div/table/thead/tr/th[1]", preg_quote($params['textFieldLabel']));
    $this->type("//div[@id='crm-profile-block']/div/div[2]/input[@class='crm-form-text required']", $recordNew['text'] . 'edit');
    $this->click("css=.ui-dialog-buttonset button[data-identifier=_qf_Edit_next]");
    $this->waitForText("//div[@id='custom--table-wrapper']/div/div/table/tbody/tr[1]/td[1]", $recordNew['text'] . 'edit');
    $editalertText = $this->getAlert();
    $this->assertEquals("Thank you. Your information has been saved.", $editalertText);
    $this->verifyText("//div[@id='custom--table-wrapper']/div/div/table/tbody/tr[1]/td[1]", preg_quote($recordNew['text'] . 'edit'));

    // Check the delete functionality
    $this->click("//div[@id='custom--table-wrapper']/div/div/table/tbody/tr/td[3]/span/a[text()='Delete']");
    $this->waitForText("css=.ui-dialog-content.crm-ajax-container", 'Are you sure you want to delete this record?');
    $this->click('_qf_Edit_upload_delete');

    $this->waitForElementPresent("//a/span[contains(text(), 'Add New Record')]");
    $delText = $this->getAlert();
    $this->assertEquals("Deleted Your record has been deleted.", $delText);

    $this->click("//div[@id='custom--table-wrapper']/div/div/table/tbody/tr/td[3]/span/a[text()='View']");
    $this->waitForText("css=.ui-dialog-title", 'View ' . $params['customGroupTitle'] . ' Record');
    $this->assertElementContainsText("css=.ui-dialog-content.crm-ajax-container", $params['textFieldLabel']);
    if ($checkSearchable) {
      $this->verifyElementNotPresent("//div[@id='profile-dialog']/div/div/div/div/div[1]/div[2]/a");
      return array($gid, $profileTitle);
    }

    // Check Search Functionality
    if (!$userCheck) {
      $this->click("//div[@class='ui-dialog-content ui-widget-content modal-dialog crm-ajax-container']/div/div/div/div/div[2]/a");
      $this->waitForElementPresent("//form[@id='Search']");
      $this->verifyText("//form[@id='Search']/div[2]/div[2]/div[2]/table/tbody/tr[2]/td[2]", preg_quote($recordNew['firstname']));
      $this->openCiviPage('profile/view', "reset=1&id=$id&gid=$gid", "//div[@id='row-first_name']/div[2]/a");
      $this->click("//div[@id='row-first_name']/div[2]/a");
      $this->waitForElementPresent("//form[@id='Search']");
      // Check that Email column is not present in selector results
      $this->verifyElementNotPresent("//form[@id='Search']/div[2]/div[2]/div[2]/table/tbody/tr/th[7]/a[@label='Email (Primary)']");
      $this->verifyText("//form[@id='Search']/div[2]/div[2]/div[2]/table/tbody/tr/th[3]/a", preg_quote($params['textFieldLabel']));
      $this->verifyText("//form[@id='Search']/div[2]/div[2]/div[2]/table/tbody/tr[2]/td[3]", preg_quote($record1['text']));
      $this->verifyText("//form[@id='Search']/div[2]/div[2]/div[2]/table/tbody/tr[3]/td[3]", preg_quote($record2['text']));
    }
    return array($gid, $profileTitle);
  }

  /**
   * @param int $gid
   * @param $profileTitle
   */
  public function _deleteProfile($gid, $profileTitle) {
    $this->webtestLogin();
    $this->openCiviPage("admin/uf/group", "action=delete&id={$gid}", '_qf_Group_next-bottom');
    $this->click('_qf_Group_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-container', "Your CiviCRM Profile '{$profileTitle}' has been deleted.");
  }

  /**
   * @param $checkSearchable
   *
   * @return mixed
   */
  public function _testCustomAdd($checkSearchable) {

    $this->openCiviPage('admin/custom/group', 'action=add&reset=1');

    //fill custom group title
    $params['customGroupTitle'] = 'custom_group' . substr(sha1(rand()), 0, 3);
    $this->click("title");
    $this->type("title", $params['customGroupTitle']);

    //custom group extends
    $this->click("extends[0]");
    $this->select("extends[0]", "label=Contacts");
    $this->click("//option[@value='Contact']");
    $this->waitForElementPresent("//input[@id='is_multiple']");
    $this->click("//input[@id='is_multiple']");
    $this->type("max_multiple", 3);
    $this->clickLink("//form[@id='Group']/div[2]/div[3]/span[1]/input");

    //Is custom group created?
    $this->assertElementContainsText('crm-container', $params['customGroupTitle']);

    $gid = $this->urlArg('gid');
    $this->openCiviPage('admin/custom/group/field/add', 'reset=1&action=add&gid=' . $gid);

    //add custom field - alphanumeric text
    $params['textFieldLabel'] = 'test_text_field' . substr(sha1(rand()), 0, 3);
    $this->click("header");
    $this->type("label", $params['textFieldLabel']);
    //Is searchable?
    if (!$checkSearchable) {
      $this->click("is_searchable");
    }
    $this->click("_qf_Field_next_new-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("data_type[0]");
    $this->select("data_type[0]", "value=0");
    $this->click("//option[@value='0']");
    $this->click("data_type[1]");
    $this->select("data_type[1]", "label=Select");
    $this->click("//option[@value='Select']");

    $params['selectFieldLabel'] = 'test_select' . substr(sha1(rand()), 0, 5);
    $this->type("label", $params['selectFieldLabel']);
    $selectOptionLabel1 = 'option1' . substr(sha1(rand()), 0, 3);
    $this->type("option_label_1", $selectOptionLabel1);
    $this->type("option_value_1", "1");
    $selectOptionLabel2 = 'option2' . substr(sha1(rand()), 0, 3);
    $this->type("option_label_2", $selectOptionLabel2);
    $this->type("option_value_2", "2");
    $this->click("link=another choice");

    //enter pre help message
    $this->type("help_pre", "this is field pre help");

    //enter post help message
    $this->type("help_post", "this field post help");

    //Is searchable?
    $this->click("is_searchable");

    //clicking save
    $this->click("_qf_Field_done-bottom");

    //Is custom field created?
    $this->waitForText('crm-notification-container', $params['selectFieldLabel']);
    return $params;
  }

  /**
   * @param string $context
   * @param bool $dialog
   * @return mixed
   *
   */
  public function _addRecords($context = 'Edit', $dialog = FALSE) {
    $params['text'] = 'text' . substr(sha1(rand()), 0, 3);
    $this->waitForElementPresent("//div[@id='crm-profile-block']/div/div[2]/input[@class='crm-form-text required']");
    $this->type("//div[@id='crm-profile-block']/div/div[2]/input[@class='crm-form-text required']", $params['text']);
    if ($context == 'Create') {
      $params['firstname'] = 'John' . substr(sha1(rand()), 0, 3);
      $this->type('first_name', $params['firstname']);
      $params['lastname'] = 'Anderson' . substr(sha1(rand()), 0, 3);
      $this->type('last_name', $params['lastname']);
      $params['email'] = $params['firstname'] . $params['lastname'] . '@exa.com';
      $this->type('email-Primary', $params['email']);
      $this->waitForElementPresent("//div[@id='crm-profile-block']//div/div[2]/select");
      $this->select("//div[@id='crm-profile-block']//div/div[2]/select", 'value=1');
    }
    else {
      $this->waitForElementPresent("//div[@id='crm-profile-block']//div/div[2]/select");
      $this->select("//div[@id='crm-profile-block']//div/div[2]/select", 'value=1');

    }
    if ($dialog) {
      $this->click("css=.ui-dialog-buttonset button[data-identifier=_qf_Edit_next]");
    }
    else {
      $this->click("_qf_Edit_next");
    }
    return $params;
  }

}
