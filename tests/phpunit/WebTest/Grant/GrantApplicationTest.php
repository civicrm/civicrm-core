<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
class WebTest_Grant_GrantApplicationTest extends CiviSeleniumTestCase {

  protected $captureScreenshotOnFailure = TRUE;
  protected $screenshotPath = '/tmp/';
  protected $screenshotUrl = 'http://api.dev.civicrm.org/sc/';

  protected function setUp() {
    parent::setUp();
  }

  function _testGrantApplicationPageAdd($gid1 = NULL, $grantType = NULL) {
    // Log in as admin first to verify permissions for CiviGrant
    $this->webtestLogin('admin');

    $this->openCiviPage('admin/grant/apply', 'reset=1&action=add', '_qf_Settings_next');

    $gtitle = 'Grant Title '.substr(sha1(rand()), 0, 7);

    // page title
    $this->type("title", $gtitle);

    // select grant type
    if ($grantType) {
      $this->select("grant_type_id", "label=$grantType");
    }
    else {
      $this->select("grant_type_id", "value=1");
    }

    $this->fillRichTextField('intro_text', 'This is Test Introductory Message', 'CKEditor');

    $this->fillRichTextField('footer_text', 'This is Test Footer Message', 'CKEditor');


    // Clicking next.
    $this->click("_qf_Settings_next");

    $this->waitForElementPresent("_qf_ThankYou_next");

    $elements = $this->parseURL();
    $id = $elements['queryString']['id'];

    $title = 'Thank You '.substr(sha1(rand()), 0, 7);

    $this->type("thankyou_title", $title);

    $this->fillRichTextField('thankyou_text', 'This is Test Introductory Message', 'CKEditor');

    $this->fillRichTextField('thankyou_footer', 'This is Test Footer Message', 'CKEditor');
    
    $this->click("_qf_ThankYou_submit_savenext");

    $this->waitForElementPresent("_qf_Custom_next");

    if ($gid1) {
      $this->select('custom_pre_id',"value=$gid1");
    }
    else {
      $this->select('custom_pre_id',"label=Name and Address");
    }

    $this->select('custom_post_id',"label=New Individual");
    
    $this->click("_qf_Custom_next");

    sleep(2);

    $this->openCiviPage('grant', 'reset=1');

    sleep(2);

    //click through to the Grant view screen
    $this->openCiviPage('grant/transact', 'reset=1&id='.$id, '_qf_Main_upload');

    $texts = array(
      $gtitle,
      'This is Test Footer Message',
      'This is Test Introductory Message',
    );
    foreach ($texts as $text) {
      $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);
    }

    return $id;
  }

   function testAddNewGrantProfile() {
    $this->webtestLogin();

    // Add new profile.
    $this->openCiviPage('admin/uf/group', 'reset=1');

    $this->click('newCiviCRMProfile-top');

    $this->waitForElementPresent('_qf_Group_next-bottom');

    //Name of profile
    $profileTitle = 'profile_' . substr(sha1(rand()), 0, 7);
    $this->type('title', $profileTitle);

    // include a link in the listings to Edit profile fields
    $this->click('is_edit_link');

    //to view contacts' Drupal user account information
    $this->click('is_uf_link');

    //click on save
    $this->click('_qf_Group_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //check for  profile create
    $this->waitForText('crm-notification-container', "Your CiviCRM Profile '{$profileTitle}' has been added. You can add fields to this profile now.");

    $elements = $this->parseURL();
    $gid = $elements['queryString']['gid'];

    //Add field to profile
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Grant');
    $this->click("//option[@value='Grant']");
    $this->type('help_post', 'This is help for profile field');

    //click on save
    $this->click('_qf_Field_next_new');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Add field to profile
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Grant');
    $this->click("//option[@value='Grant']");
    $this->click('field_name[1]');
    $this->select('field_name[1]', 'value=amount_total');
    $this->click("//option[@value='amount_total']");

    //click on save
    $this->click('_qf_Field_next_new');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->openCiviPage('admin/uf/group', 'action=preview&field=0&context=group&id='.$gid);
     $texts = array(
      $profileTitle,
      'Total Amount',
    );
    foreach ($texts as $text) {
      $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);
    }

    return $gid;
  }

   function _testCustomFieldsetTest() {
     // Log in as admin first to verify permissions for CiviGrant
     $this->webtestLogin('admin');

     // Enable CiviGrant module if necessary
     $this->enableComponents("CiviGrant");

     // let's give full CiviGrant permissions to demo user (registered user).
     $permission = array('edit-2-access-civigrant', 'edit-2-edit-grants', 'edit-2-delete-in-civigrant');
     $this->changePermissions($permission);

     // Log in as normal user
     $this->webtestLogin();

     // Create unique identifier for names
     $rand = substr(sha1(rand()), 0, 7);

     // Add new Grant Type
     $this->openCiviPage('admin/options/grant_type', 'group=grant_type&reset=1');
     $this->click("css=#grant_type > div.action-link > #new > span");
     $this->waitForPageToLoad($this->getTimeoutMsec());
     $grantType = 'GrantType' . $rand;
     $this->type('id=label', $grantType);
     $this->click('id=_qf_Options_next-top');
     $this->waitForPageToLoad($this->getTimeoutMsec());
     $this->waitForText('crm-notification-container', "The Grant Type '$grantType' has been saved.");

     // Create new Custom Field Set that extends the grant type
     $this->openCiviPage('admin/custom/group', 'reset=1');
     $this->click("css=#newCustomDataGroup > span");
     $this->waitForElementPresent('_qf_Group_next-bottom');
     $grantFieldSet = 'Fieldset' . $rand;
     $this->type('id=title', $grantFieldSet);
     $this->select('id=extends_0', 'label=Grants');
     $this->addSelection('id=extends_1', "label=$grantType");
     $this->click('id=collapse_display');
     $this->click('id=_qf_Group_next-bottom');
     $this->waitForElementPresent('_qf_Field_next-bottom');
     $this->waitForText('crm-notification-container', "Your custom field set '$grantFieldSet' has been added.");

     // Add field to fieldset
     $grantField = 'GrantField' . $rand;
     $this->type('id=label', $grantField);
     $this->select('id=data_type_0', 'label=Money');
     $this->click('id=_qf_Field_next-bottom');
     $this->waitForPageToLoad($this->getTimeoutMsec());
     $this->waitForText('crm-notification-container', "Your custom field '$grantField' has been saved.");


     // Add new profile.
    $this->openCiviPage('admin/uf/group', 'reset=1');

    $this->click('newCiviCRMProfile-top');

    $this->waitForElementPresent('_qf_Group_next-bottom');

    //Name of profile
    $profileTitle = 'profile_' . substr(sha1(rand()), 0, 7);
    $this->type('title', $profileTitle);

    // include a link in the listings to Edit profile fields
    $this->click('is_edit_link');

    //to view contacts' Drupal user account information
    $this->click('is_uf_link');

    //click on save
    $this->click('_qf_Group_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //check for  profile create
    $this->waitForText('crm-notification-container', "Your CiviCRM Profile '{$profileTitle}' has been added. You can add fields to this profile now.");

    $elements = $this->parseURL();
    $gid = $elements['queryString']['gid'];

     //Add field to profile
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Grant');
    $this->click("//option[@value='Grant']");
    $this->click('field_name[1]');
    $this->select('field_name[1]', 'label='.$grantField.' :: '.$grantFieldSet); 
    //click on save
    $this->click('_qf_Field_next_new');
    $this->waitForPageToLoad($this->getTimeoutMsec());


    //Add field to profile
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Grant');
    $this->click("//option[@value='Grant']");
    $this->click('field_name[1]');
    $this->select('field_name[1]', 'value=amount_total');
    $this->click("//option[@value='amount_total']");

    //click on save
    $this->click('_qf_Field_next_new');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Add field to profile
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Individual');
    $this->click("//option[@value='Individual']");
    $this->click('field_name[1]');
    $this->select('field_name[1]', 'value=first_name');

    //click on save
    $this->click('_qf_Field_next_new');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->openCiviPage('admin/uf/group', 'action=preview&field=0&context=group&id='.$gid, NULL);
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent($grantField), 'Missing text: ' . $grantField);

    return array($gid, $grantType);
   }

   function testgrantApplications() {
     list($gid1, $grantType) = $this->_testCustomFieldsetTest();

     $id = $this->_testGrantApplicationPageAdd($gid1, $grantType);
     $fname = 'First Name '.substr(sha1(rand()), 0, 7);

     $this->type('first_name', $fname);
     $this->type('last_name', 'Last Name '.substr(sha1(rand()), 0, 7));
     $this->type('amount_total', 100);
     
     $this->click('_qf_Main_upload'); 
     $this->waitForPageToLoad($this->getTimeoutMsec());
     $this->click('_qf_Confirm_next');
     $this->waitForPageToLoad($this->getTimeoutMsec());

     $this->openCiviPage('grant/search', 'reset=1', NULL);
     $this->waitForPageToLoad($this->getTimeoutMsec());
     $this->type('sort_name', $fname);
     $this->click('_qf_Search_refresh');
     $this->waitForPageToLoad($this->getTimeoutMsec());
     $this->click("//div[@id='crm-recently-viewed']/ul/li/a");
     $this->waitForPageToLoad($this->getTimeoutMsec());
     sleep(3);
   }
}

