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
 * Class WebTest_Generic_GeneralClickAroundTest
 */
class WebTest_Generic_GeneralClickAroundTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function login() {
    $this->webtestLogin();
    $this->openCiviPage('');
  }

  public function testSearchMenu() {
    $this->login();
    // click Search -> Find Contacts
    // Use class names for menu items since li array can change based on which components are enabled
    $this->click("css=ul#civicrm-menu li.crm-Search");
    $this->click("css=ul#civicrm-menu li.crm-Find_Contacts a");
    $this->waitForElementPresent('tag');

    $this->click('contact_type');
    $this->select('contact_type', 'label=Individual');
    $this->select('tag', 'label=Major Donor');
    $this->click('_qf_Basic_refresh');
    $this->waitForElementPresent('search-status');
    $this->assertText('search-status', "Contact Type In Individual");
    $this->assertText('search-status', 'Tagged = Major Donor');

    // Advanced Search by Tag
    $this->click("css=ul#civicrm-menu li.crm-Search");
    $this->click("css=ul#civicrm-menu li.crm-Advanced_Search a");
    $this->waitForElementPresent('contact_tags');
    $this->select('contact_tags', 'label=Major Donor');
    $this->click('_qf_Advanced_refresh');
    $this->waitForElementPresent('search-status');
    $this->assertText('search-status', 'Tagged In Major Donor');
  }

  public function testNewIndividual() {
    $this->login();

    // Create New → Individual
    $this->click("crm-create-new-link");
    $this->click("link=Individual");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertElementPresent("first_name");
    $this->assertElementPresent("email_1_email");
    $this->assertElementPresent("phone_1_phone");
    $this->assertElementPresent("contact_source");
    $this->assertTextPresent("Constituent Information");
    $this->click("//form[@id='Contact']/div[2]/div[4]/div[1]");
    $this->click("//div[@id='customData1']/table/tbody/tr[1]/td[1]/label");
    $this->assertTextPresent("Most Important Issue");
    $this->click("//form[@id='Contact']/div[2]/div[6]/div[1]");
    $this->assertTextPresent("Communication Preferences");
    $this->assertTextPresent("Do not phone");
  }

  public function testManageGroups() {
    $this->login();

    // Contacts → Manage Groups
    $this->click("//ul[@id='civicrm-menu']/li[4]");
    $this->clickLink("xpath=//div[@id='root-menu-div']//div/ul//li/div/a[text()='Manage Groups']");

    $this->assertTextPresent("Find Groups");
    $this->assertElementPresent("title");
    $this->assertTextPresent("Access Control");
    $this->waitForElementPresent('link=Settings');
    $this->assertTextPresent("Administrators");
    $this->assertTextPresent("Add Group");
  }

  public function testContributionDashboard() {
    $this->webtestLogin();
    // Enable CiviContribute module if necessary
    $this->enableComponents("CiviContribute");

    // Contributions → Dashboard
    $this->click("css=ul#civicrm-menu li.crm-Contributions");
    $this->clickLink("css=ul#civicrm-menu li.crm-Contributions li.crm-Dashboard a");

    $this->waitForElementPresent("xpath=//*[@id='chart_view']/a");
    $this->waitForElementPresent("xpath=//*[@id='ContributionCharts']/div[4]/div[3]");
    $this->assertTextPresent("Contribution Summary");
    $this->assertTextPresent("Recent Contributions");
  }

  public function testEventDashboard() {
    $this->webtestLogin();

    // Enable CiviEvent module if necessary
    $this->enableComponents("CiviEvent");

    // Events → Dashboard
    $this->click("css=ul#civicrm-menu li.crm-Events");
    $this->clickLink("css=ul#civicrm-menu li.crm-Events li.crm-Dashboard a");

    $this->assertTextPresent("Event Summary");
    $this->assertTextPresent("Counted:");
    $this->assertTextPresent("Not Counted:");
    $this->assertTextPresent("Not Counted Due To Status:");
    $this->assertTextPresent("Not Counted Due To Role:");
    $this->assertTextPresent("Registered:");
    $this->assertTextPresent("Attended:");
    $this->assertTextPresent("No-show:");
    $this->assertTextPresent("Cancelled:");
    $this->assertTextPresent("Recent Registrations");
    $this->assertTextPresent("Find more event participants...");
    $this->assertTextPresent("Configure");
  }

  public function testMembershipsDashboard() {
    $this->webtestLogin();

    // Enable CiviMember module if necessary
    $this->enableComponents("CiviMember");

    // Memberships → Dashboard
    $this->click("css=ul#civicrm-menu li.crm-Memberships");
    $this->click("css=ul#civicrm-menu li.crm-Memberships li.crm-Dashboard a");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTextPresent("Membership Summary");
    $this->assertTextPresent("Members by Type");
    $this->assertTextPresent("Recent Memberships");
    $this->assertTextPresent("Find more members...");
  }

  public function testFindContributions() {
    $this->webtestLogin();

    // Enable CiviContribute module if necessary
    $this->enableComponents("CiviContribute");

    // Search → Find Contributions
    // Use class names for menu items since li array can change based on which components are enabled
    $this->click("css=ul#civicrm-menu li.crm-Search");
    $this->clickLink("css=ul#civicrm-menu li.crm-Find_Contributions a");

    $this->assertTextPresent("Edit Search Criteria");
    $this->assertElementPresent("sort_name");
    $this->assertElementPresent("contribution_date_low");
    $this->assertElementPresent("contribution_amount_low");
    $this->assertElementPresent("contribution_check_number");
    $this->assertTextPresent("Financial Type");
    $this->assertTextPresent("Contribution Page");
    $this->assertElementPresent("s2id_contribution_or_softcredits");
    $this->assertElementPresent("contribution_source");
    $this->assertTextPresent("Personal Campaign Page");
    $this->assertTextPresent("Personal Campaign Page Honor Roll");
    $this->assertTextPresent("Currency");
  }

  public function testNewMailing() {
    $this->webtestLogin();

    // Enable CiviMail module if necessary
    $this->enableComponents("CiviMail");

    // configure default mail-box
    $this->setupDefaultMailbox();

    // New Mailing Form
    // Use class names for menu items since li array can change based on which components are enabled
    $this->click("css=ul#civicrm-menu li.crm-Mailings");
    $this->clickLink("css=ul#civicrm-menu li.crm-New_Mailing a");

    $this->assertTextPresent("Mailing");
  }

  public function testConstituentReportSummary() {
    $this->login();

    // Constituent Report Summary
    $this->click("css=ul#civicrm-menu li.crm-Reports");
    $this->clickLink("css=ul#civicrm-menu li.crm-Contact_Reports a");

    $this->clickLink("xpath=//div[@id='Contact']/table/tbody/tr/td[1]/a");

    $this->assertTextPresent("Constituent Summary");
    $this->click("xpath=//div[@id='mainTabContainer']/ul/li[3]/a");
    $this->waitForElementPresent("xpath=//div[@id='report-tab-set-filters']");
    $this->assertTextPresent("Contact Name");
    $this->assertTextPresent("Contact Source");
    $this->assertTextPresent("Country");
    $this->assertTextPresent("State/Province");
    $this->assertTextPresent("Group");
    $this->assertTextPresent("Tag");
    $this->clickLink("_qf_Summary_submit");

    $this->assertTextPresent("Row(s) Listed");
    $this->assertTextPresent("Total Row(s)");
  }

  public function testCustomData() {
    $this->login();

    // Use class names for menu items since li array can change based on which components are enabled
    $this->click("css=ul#civicrm-menu li.crm-Administer");
    $this->clickLink("xpath=//div[@id='root-menu-div']//a[text()='Custom Fields']");

    $this->assertTextPresent("Custom Data");
    $this->assertTextPresent("Constituent Information");
    $this->assertTextPresent("Donor Information");
    $this->assertTextPresent("Food Preference");

    // Verify create form
    $this->clickLink("//span[contains(text(), 'Add Set of Custom Fields')]");

    $this->assertElementPresent("title");
    $this->assertElementPresent("extends[0]");
    $this->assertElementPresent("weight");
    $this->assertTextPresent("Pre-form Help");
    $this->assertTextPresent("Post-form Help");
  }

  public function testProfile() {
    $this->login();

    // Use class names for menu items since li array can change based on which components are enabled
    $this->click("css=ul#civicrm-menu li.crm-Administer");
    $this->click("css=ul#civicrm-menu li.crm-Customize_Data_and_Screens");
    $this->clickLink("xpath=//div[@id='root-menu-div']//a[text()='Profiles']");

    $this->assertTextPresent("CiviCRM Profile");
    // Verify Reserved Profiles

    $this->click("xpath=//div[@id='mainTabContainer']/ul/li[2]/a");
    $this->waitForElementPresent("newCiviCRMProfile-bottom");
    $this->assertTextPresent("New Household");
    $this->assertTextPresent("New Individual");
    $this->assertTextPresent("New Organization");
    $this->assertTextPresent("Participant Status");
    $this->assertTextPresent("Shared Address");
    $this->assertTextPresent("Summary Overlay");

    // Verify profiles that are not reserved
    $this->click("xpath=//div[@id='mainTabContainer']/ul/li[1]/a");
    $this->assertTextPresent("Name and Address");
    $this->assertTextPresent("Supporter Profile");

    // Verify create form
    $this->click("//span[contains(text(), 'Add Profile')]");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementPresent("title");
    $this->assertElementPresent("uf_group_type[Profile]");
    $this->assertElementPresent("weight");
    $this->assertTextPresent("Pre-form Help");
    $this->assertTextPresent("Post-form Help");
    $this->click("//form[@id='Group']/div[2]/div[2]/div/div");
    $this->assertElementPresent("group");
    $this->assertElementPresent("post_URL");
    $this->assertTextPresent("Drupal user account registration option?");
    $this->assertTextPresent("What to do upon duplicate match");
    $this->assertTextPresent("Proximity Search");
  }

  public function testTags() {
    $this->login();

    // Use class names for menu items since li array can change based on which components are enabled
    $this->click("css=ul#civicrm-menu li.crm-Administer");
    $this->click("css=ul#civicrm-menu li.crm-Customize_Data_and_Screens");
    $this->clickLink("xpath=//div[@id='root-menu-div']//a[text()='Tags (Categories)']");

    // Verify tags
    $this->assertTextPresent("Non-profit");
    $this->assertTextPresent("Company");
    $this->assertTextPresent("Government Entity");
    $this->assertTextPresent("Major Donor");
    $this->assertTextPresent("Volunteer");
  }

  public function testActivityTypes() {
    $this->login();

    // Use class names for menu items since li array can change based on which components are enabled
    $this->click("css=ul#civicrm-menu li.crm-Administer");
    $this->click("css=ul#civicrm-menu li.crm-Customize_Data_and_Screens");
    $this->clickLink("xpath=//div[@id='root-menu-div']//a[text()='Activity Types']");

    // Verify activity types
    $this->assertTextPresent("Meeting");
    $this->assertTextPresent("Print/Merge Document");
    $this->assertTextPresent("Event Registration");
    $this->assertTextPresent("Contribution");
    $this->assertTextPresent("Membership Signup");
  }

  public function testRelationshipTypes() {
    $this->login();

    // Use class names for menu items since li array can change based on which components are enabled
    $this->click("css=ul#civicrm-menu li.crm-Administer");
    $this->click("css=ul#civicrm-menu li.crm-Customize_Data_and_Screens");

    $this->clickLink("xpath=//div[@id='root-menu-div']//a[text()='Relationship Types']");

    // Verify relationship types
    $this->assertTextPresent("Child of");
    $this->assertTextPresent("Head of Household for");
    $this->assertTextPresent("Sibling of");
    $this->assertTextPresent("Spouse of");
    $this->assertTextPresent("Supervised by");
    $this->assertTextPresent("Volunteer for");
  }

  public function testMessageTemplates() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
    $this->login();

    // Use class names for menu items since li array can change based on which components are enabled
    $this->click("css=ul#civicrm-menu li.crm-Administer");
    $this->click("css=ul#civicrm-menu li.crm-Communications");
    $this->clickLink("xpath=//div[@id='root-menu-div']//a[text()='Message Templates']");

    // Verify message templates
    $this->click("xpath=//div[@id='mainTabContainer']/ul//li/a[contains(text(),'System Workflow Messages')]");
    $this->assertTextPresent("Contributions - Receipt (on-line)");
    $this->assertTextPresent("Events - Registration Confirmation and Receipt (off-line)");
    $this->assertTextPresent("Memberships - Signup and Renewal Receipts (off-line)");
    $this->assertTextPresent("Personal Campaign Pages - Supporter Status Change Notification");
    $this->assertTextPresent("Profiles - Admin Notification");
    $this->assertTextPresent("Tell-a-Friend Email");
  }

}
