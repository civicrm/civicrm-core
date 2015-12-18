<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Class WebTest_Contact_RelationshipAddTest
 */
class WebTest_Contact_RelationshipAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testRelationshipAddTest() {
    $this->webtestLogin();

    //create a relationship type between different contact types
    $params = array(
      'label_a_b' => 'Owner of ' . rand(),
      'label_b_a' => 'Belongs to ' . rand(),
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Household',
      'description' => 'The company belongs to this individual',
    );

    $this->webtestAddRelationshipType($params);

    //create a New Individual
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Anderson", "$firstName@anderson.name");
    $sortName = "Anderson, $firstName";

    $this->openCiviPage("contact/add", "reset=1&ct=Household");

    //fill in Household name
    $this->click("household_name");
    $name = "Fraddie Grant's home " . substr(sha1(rand()), 0, 7);
    $this->type("household_name", $name);

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForElementPresent("css=.crm-contact-tabs-list");

    // visit relationship tab of the household
    $this->click("css=li#tab_rel a");

    // wait for add Relationship link
    $this->waitForElementPresent('link=Add Relationship');
    $this->click('link=Add Relationship');

    //choose the created relationship type
    $this->waitForElementPresent("relationship_type_id");
    $this->select('relationship_type_id', "label={$params['label_b_a']}");

    //fill in the individual
    $this->select2('related_contact_id', $sortName, TRUE);

    //fill in the relationship start date
    $this->webtestFillDate('start_date', '-2 year');
    $this->webtestFillDate('end_date', '+1 year');

    $description = "Well here is some description !!!!";
    $this->type("description", $description);

    //save the relationship
    //$this->click("_qf_Relationship_upload");
    $this->click('_qf_Relationship_upload-bottom');

    //check the status message
    $this->waitForText('crm-notification-container', 'Relationship created.');
    $this->waitForElementPresent("xpath=//div[@class='crm-contact-relationship-current']/div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[9]//span//a[text()='View']");
    $this->click("xpath=//div[@class='crm-contact-relationship-current']/div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[9]//span//a[text()='View']");

    $this->webtestVerifyTabularData(
      array(
        'Description' => $description,
        'Status' => 'Enabled',
      )
    );

    $this->assertTrue($this->isTextPresent($params['label_b_a']));

    //create a New Individual subtype
    $this->openCiviPage('admin/options/subtype', "action=add&reset=1");
    $label = "IndividualSubtype" . substr(sha1(rand()), 0, 4);
    $this->type("label", $label);
    $this->type("description", "here is individual subtype");
    $this->click("_qf_ContactType_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //create a new contact of individual subtype
    $this->openCiviPage('contact/add', "ct=Individual&cst={$label}&reset=1", '_qf_Contact_upload_view');
    $firstName = substr(sha1(rand()), 0, 7);
    $lastName = 'And' . substr(sha1(rand()), 0, 7);
    $this->click("first_name");
    $this->type("first_name", $firstName);
    $this->click("last_name");
    $this->type("last_name", $lastName);
    $sortName = "$lastName, $firstName";

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForElementPresent("css=.crm-contact-tabs-list");

    //create a New household subtype
    $this->openCiviPage("admin/options/subtype", "action=add&reset=1");

    $label = "HouseholdSubtype" . substr(sha1(rand()), 0, 4);
    $householdSubtypeName = $label;
    $this->click("label");
    $this->type("label", $label);
    $this->select("parent_id", "label=Household");
    $this->type("description", "here is household subtype");
    $this->click("_qf_ContactType_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //create a new contact of household subtype
    $this->openCiviPage('contact/add', "ct=Household&cst={$label}&reset=1", '_qf_Contact_upload_view');

    //fill in Household name
    $householdName = substr(sha1(rand()), 0, 4) . 'home';
    $this->click("household_name");
    $this->type("household_name", $householdName);

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //choose the created relationship type
    $this->click('css=li#tab_rel a');

    // wait for add Relationship link
    $this->waitForElementPresent('link=Add Relationship');
    $this->click('link=Add Relationship');
    $this->waitForElementPresent("relationship_type_id");
    $this->select('relationship_type_id', "label={$params['label_b_a']}");

    //fill in the individual
    $this->waitForElementPresent("related_contact_id");
    $this->select2('related_contact_id', $sortName, TRUE);

    //fill in the relationship start date
    $this->webtestFillDate('start_date', '-2 year');
    $this->webtestFillDate('end_date', '+1 year');

    $description = "Well here is some description !!!!";
    $this->type("description", $description);

    //save the relationship
    $this->click('_qf_Relationship_upload-bottom');

    $this->waitForElementPresent("xpath=//div[@id='contact-summary-relationship-tab']/div[@class='crm-contact-relationship-current']/div/table/tbody/tr/td[9]//span//a[text()='View']");
    $this->click("xpath=//div[@id='contact-summary-relationship-tab']/div[@class='crm-contact-relationship-current']/div/table/tbody/tr/td[9]//span//a[text()='View']");

    $this->webtestVerifyTabularData(
      array(
        'Description' => $description,
        'Status' => 'Enabled',
      )
    );

    $this->assertTrue($this->isTextPresent($params['label_b_a']));

    //test for individual contact and household subtype contact
    //relationship
    $typeb = "Household__" . $householdSubtypeName;

    //create a relationship type between different contact types
    $params = array(
      'label_a_b' => 'Owner of ' . rand(),
      'label_b_a' => 'Belongs to ' . rand(),
      'contact_type_a' => 'Individual',
      'contact_type_b' => $typeb,
      'description' => 'The company belongs to this individual',
    );

    //create relationship type
    $this->openCiviPage('admin/reltype', 'reset=1&action=add');
    $this->type('label_a_b', $params['label_a_b']);
    $this->type('label_b_a', $params['label_b_a']);
    $this->select('contact_types_a', "value={$params['contact_type_a']}");
    $this->select('contact_types_b', "value={$params['contact_type_b']}");
    $this->type('description', $params['description']);

    $params['contact_type_b'] = preg_replace('/__/', ' - ', $params['contact_type_b']);

    //save the data.
    $this->click('_qf_RelationshipType_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //does data saved.
    $this->assertTrue($this->isTextPresent('The Relationship Type has been saved.'),
      "Status message didn't show up after saving!"
    );

    $this->openCiviPage("admin/reltype", "reset=1");

    //validate data on selector.
    $data = $params;
    if (isset($data['description'])) {
      unset($data['description']);
    }
    $this->assertStringsPresent($data);

    //create a New Individual
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Anderson", "$firstName@anderson.name");
    $sortName = "Anderson, $firstName";

    //create a new contact of household subtype
    $this->openCiviPage('contact/add', "ct=Household&cst={$householdSubtypeName}&reset=1", '_qf_Contact_upload_view');

    //fill in Household name
    $householdName = substr(sha1(rand()), 0, 4) . 'home';
    $this->click("household_name");
    $this->type("household_name", $householdName);

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //choose the created relationship type
    $this->click('css=li#tab_rel a');

    // wait for add Relationship link
    $this->waitForElementPresent('link=Add Relationship');
    $this->click('link=Add Relationship');
    $this->waitForElementPresent("relationship_type_id");
    $this->select('relationship_type_id', "label={$params['label_b_a']}");

    //fill in the individual

    $this->select2('related_contact_id', $sortName, TRUE);

    //fill in the relationship start date
    $this->webtestFillDate('start_date', '-2 year');
    $this->webtestFillDate('end_date', '+1 year');
    $description = "Well here is some description !!!!";
    $this->type("description", $description);

    //save the relationship
    $this->click('_qf_Relationship_upload-bottom');

    $this->waitForElementPresent("xpath=//div[@id='contact-summary-relationship-tab']/div[@class='crm-contact-relationship-current']/div/table/tbody/tr/td[9]//span//a[text()='View']");
    $this->click("xpath=//div[@id='contact-summary-relationship-tab']/div[@class='crm-contact-relationship-current']/div/table/tbody/tr/td[9]//span//a[text()='View']");

    $this->webtestVerifyTabularData(
      array(
        'Description' => $description,
        'Status' => 'Enabled',
      )
    );

    $this->assertTrue($this->isTextPresent($params['label_b_a']));
  }

  public function testRelationshipAddNewIndividualTest() {
    $this->webtestLogin();

    //create a relationship type between different contact types
    $params = array(
      'label_a_b' => 'Board Member of ' . rand(),
      'label_b_a' => 'Board Member is' . rand(),
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'description' => 'Board members of organizations.',
    );

    $this->webtestAddRelationshipType($params);

    //create a New Individual
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Anderson", "$firstName@anderson.name");

    // visit relationship tab of the Individual
    $this->click("css=li#tab_rel a");

    // wait for add Relationship link
    $this->waitForElementPresent('link=Add Relationship');
    $this->click('link=Add Relationship');

    //choose the created relationship type
    $this->waitForElementPresent("relationship_type_id");
    $this->select('relationship_type_id', "label={$params['label_a_b']}");
    $this->waitForAjaxContent();

    // create a new organization
    $orgName = 'WestsideCoop' . substr(sha1(rand()), 0, 7);

    $this->click("//*[@id='related_contact_id']/../div/ul/li/input");
    $this->click("xpath=//li[@class='select2-no-results']//a[contains(text(),' New Organization')]");
    $this->waitForElementPresent('_qf_Edit_next');
    $this->type('organization_name', $orgName);
    $this->type('email-Primary', "info@" . $orgName . ".com");
    $this->click('_qf_Edit_next');
    $this->waitForText("xpath=//div[@id='s2id_related_contact_id']", "$orgName");

    //fill in the relationship start date
    $this->webtestFillDate('start_date', '-2 year');
    $this->webtestFillDate('end_date', '+1 year');

    $description = "Long-standing board member.";
    $this->type("description", $description);

    //save the relationship
    //$this->click("_qf_Relationship_upload");
    $this->click("_qf_Relationship_upload-bottom");

    //check the status message
    $this->waitForText('crm-notification-container', 'Relationship created.');
    $this->waitForElementPresent("xpath=//div[@id='contact-summary-relationship-tab']/div[2]/div[1]/table/tbody/tr/td[9]/span[1]//a[text()='View']");
    $this->click("xpath=//div[@id='contact-summary-relationship-tab']/div[2]/div[1]/table/tbody/tr/td[9]/span[1]//a[text()='View']");

    $this->webtestVerifyTabularData(
      array(
        'Description' => $description,
        'Status' => 'Enabled',
      )
    );
    $this->assertTrue($this->isTextPresent($params['label_a_b']));
  }

  public function testAjaxCustomGroupLoad() {
    $this->webtestLogin();

    //create a New Individual
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Anderson", "$firstName@anderson.name");
    $contactId = explode('cid=', $this->getLocation());

    $triggerElement = array('name' => 'relationship_type_id', 'type' => 'select');
    $customSets = array(
      array('entity' => 'Relationship', 'subEntity' => 'Partner of', 'triggerElement' => $triggerElement),
      array('entity' => 'Relationship', 'subEntity' => 'Spouse of', 'triggerElement' => $triggerElement),
    );

    $pageUrl = array('url' => 'contact/view/rel', 'args' => "cid={$contactId[1]}&action=add&reset=1");
    $this->customFieldSetLoadOnTheFlyCheck($customSets, $pageUrl);
  }

  public function testRelationshipAddCurrentEmployerTest() {
    $this->webtestLogin();

    //create a New Individual
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Anderson", "$firstName@anderson.name");

    // visit relationship tab of the Individual
    $this->click("css=li#tab_rel a");

    // wait for add Relationship link
    $this->waitForElementPresent('link=Add Relationship');
    $this->click('link=Add Relationship');

    //choose the created relationship type
    $this->waitForElementPresent("relationship_type_id");
    $this->select('relationship_type_id', "label=Employee of");
    $this->waitForAjaxContent();

    // create a new organization

    $orgName = 'WestsideCoop' . substr(sha1(rand()), 0, 7);
    $this->click("//*[@id='related_contact_id']/../div/ul/li/input");
    $this->click("xpath=//li[@class='select2-no-results']//a[contains(text(),' New Organization')]");
    $this->waitForElementPresent('_qf_Edit_next');
    $this->type('organization_name', $orgName);
    $this->type('email-Primary', "info@" . $orgName . ".com");
    $this->click('_qf_Edit_next');
    $this->waitForText("xpath=//div[@id='s2id_related_contact_id']", "$orgName");

    //fill in the relationship start date
    $this->webtestFillDate('start_date', '-2 year');
    $this->webtestFillDate('end_date', '+1 year');

    $description = "Current employee test.";
    $this->type("description", $description);

    //save the relationship
    //$this->click("_qf_Relationship_upload");
    $this->click('_qf_Relationship_upload-bottom');

    //check the status message
    $this->waitForText('crm-notification-container', 'Relationship created.');

    $this->waitForElementPresent("xpath=//div[@id='contact-summary-relationship-tab']/div[@class='crm-contact-relationship-current']/div/table/tbody/tr/td[9]//span//a[text()='View']");
    $this->click("xpath=//div[@id='contact-summary-relationship-tab']/div[@class='crm-contact-relationship-current']/div/table/tbody/tr/td[9]//span//a[text()='View']");

    $this->webtestVerifyTabularData(
      array(
        'Description' => $description,
        'Current Employee?' => 'Yes',
        'Status' => 'Enabled',
      )
    );
    $this->assertTrue($this->isTextPresent("Employee of"), "Employee of relationship type not visible on View Relationship page.");
  }

}
