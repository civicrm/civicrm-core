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
 * Class WebTest_Grant_CustomFieldsetTest
 */
class WebTest_Grant_CustomFieldsetTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testCustomFieldsetTest() {
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
    $this->openCiviPage('admin/options/grant_type', 'reset=1');
    $this->click("xpath=//*[@id='crm-main-content-wrapper']/div[2]/div[1]/a");
    $this->waitForElementPresent('_qf_Options_cancel-bottom');
    $grantType = 'GrantType' . $rand;
    $this->type('id=label', $grantType);
    $this->click('id=_qf_Options_next-top');
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
    $this->clickLink('id=_qf_Group_next-bottom');
    $this->waitForText('crm-notification-container', "Your custom field set '$grantFieldSet' has been added.");
    $this->waitForElementPresent('_qf_Field_done-bottom');

    // Add field to fieldset
    $grantField = 'GrantField' . $rand;
    $this->type('id=label', $grantField);
    $this->select('id=data_type_0', 'label=Money');
    $this->click('id=_qf_Field_done-bottom');
    $this->waitForText('crm-notification-container', "Custom field '$grantField' has been saved.");

    // Create new Grant
    $this->openCiviPage('grant/add', 'reset=1&action=add&context=standalone', '_qf_Grant_upload-bottom');
    $contact = $this->createDialogContact();
    $this->select('id=status_id', 'label=Approved for Payment');
    $this->select('id=grant_type_id', "label=$grantType");
    $this->waitForTextPresent($grantField);
    $this->assertElementContainsText("xpath=//div[@id='customData']/div[@class='custom-group custom-group-$grantFieldSet crm-accordion-wrapper ']", $grantField);
    $this->type('id=amount_total', '100.00');
    $this->type("xpath=//div[@id='customData']/div[@class='custom-group custom-group-$grantFieldSet crm-accordion-wrapper ']/div[@class='crm-accordion-body']/table/tbody/tr/td[2]/input[@class='crm-form-text']", '99.99');
    $this->clickLink('id=_qf_Grant_upload-bottom', "xpath=//div[@class='view-content']//table/tbody/tr[1]/td[8]/span/a[text()='View']");

    // Click through to the Grant view screen
    $this->click("xpath=//div[@class='view-content']//table/tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_GrantView_cancel-bottom');

    // verify tabular data for grant view
    $this->webtestVerifyTabularData(array(
        'Name' => $contact['display_name'],
        'Grant Status' => 'Approved',
        'Grant Type' => $grantType,
        $grantField => '$ 99.99',
      )
    );
  }

  public function testAjaxCustomGroupLoad() {
    $this->webtestLogin();

    // Enable CiviGrant module if necessary
    $this->enableComponents("CiviGrant");

    $triggerElement = array('name' => 'grant_type_id', 'type' => 'select');
    $customSets = array(
      array('entity' => 'Grant', 'subEntity' => 'Emergency', 'triggerElement' => $triggerElement),
      array('entity' => 'Grant', 'subEntity' => 'Family Support', 'triggerElement' => $triggerElement),
    );

    $pageUrl = array('url' => 'grant/add', 'args' => 'reset=1&action=add&context=standalone');
    $this->customFieldSetLoadOnTheFlyCheck($customSets, $pageUrl);
  }

}
