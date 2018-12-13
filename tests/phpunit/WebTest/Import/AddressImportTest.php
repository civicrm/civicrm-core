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

require_once 'WebTest/Import/ImportCiviSeleniumTestCase.php';

/**
 * Class WebTest_Import_AddressImportTest
 */
class WebTest_Import_AddressImportTest extends ImportCiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testCustomAddressDataImport() {
    $this->webtestLogin();

    $firstName1 = 'Ma_' . substr(sha1(rand()), 0, 7);
    // Add a custom group and custom field
    $customDataParams = $this->_addCustomData();

    // Get sample import data.
    list($headers, $rows) = $this->_individualCustomCSVData($customDataParams, $firstName1);

    $this->importContacts($headers, $rows, 'Individual', 'Skip', array());

    // Type search name in autocomplete.
    $this->click('sort_name_navigation');
    $this->type('css=input#sort_name_navigation', $firstName1);
    $this->typeKeys('css=input#sort_name_navigation', $firstName1);

    // Wait for result list.
    $this->waitForElementPresent("css=ul.ui-autocomplete li");

    // Visit contact summary page.
    $this->click("css=ul.ui-autocomplete li");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    foreach ($customDataParams['customFields'] as $key => $value) {
      $this->assertTrue($this->isElementPresent("xpath=//div[@class='crm-summary-row']/div[@class='crm-label'][contains(text(), '$key')]"));
      $this->assertElementContainsText('address-block-1', "$value");
    }
  }

  /**
   * Helper function to provide data for custom data import.
   *
   * @param array $customDataParams
   * @param string $firstName1
   *
   * @return array
   */
  public function _individualCustomCSVData($customDataParams, $firstName1) {

    $headers = array(
      'first_name' => 'First Name',
      'last_name' => 'Last Name',
      'address_1' => 'Additional Address 1',
      'address_2' => 'Additional Address 2',
      'city' => 'City',
      'state' => 'State',
      'country' => 'Country',
    );
    foreach ($customDataParams['headers'] as $key => $value) {
      $headers[$key] = $value;
    }

    $rows = array(
      0 => array(
        'first_name' => $firstName1,
        'last_name' => 'Anderson',
        'address_1' => 'Add 1',
        'address_2' => 'Add 2',
        'city' => 'Watson',
        'state' => 'NY',
        'country' => 'UNITED STATES',
      ),
    );
    foreach ($customDataParams['rows'][0] as $key => $values) {
      $rows[0][$key] = $values;
    }
    return array($headers, $rows);
  }

  /**
   * @return array
   */
  public function _addCustomData() {

    $this->openCiviPage('admin/custom/group', 'reset=1');

    //add new custom data
    $this->click("//a[@id='newCustomDataGroup']/span");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //fill custom group title
    $customGroupTitle = 'Custom ' . substr(sha1(rand()), 0, 7);
    $this->click('title');
    $this->type('title', $customGroupTitle);

    //custom group extends
    $this->click('extends[0]');
    $this->select('extends[0]', "value=Address");
    $this->click("//option[@value='Address']");
    $this->clickLink('_qf_Group_next-bottom');

    //Is custom group created?
    $this->waitForText('crm-notification-container', "Your custom field set '{$customGroupTitle}' has been added. You can add custom fields now.");
    $gid = $this->urlArg('gid');
    $this->waitForElementPresent('_qf_Field_cancel-bottom');

    // create custom field "alphanumeric text"
    $customField = 'Custom field ' . substr(sha1(rand()), 0, 4);
    $this->type('label', $customField);

    // clicking save
    $this->click('_qf_Field_done-bottom');

    $this->waitForText('crm-notification-container', "Custom field '{$customField}' has been saved.");
    $this->assertTrue($this->isTextPresent($customField), 'Missing text: ' . $customField);
    $this->waitForAjaxContent();
    $customFieldId = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr/td/div[text()='$customField']/../../td[8]/span/a@href"));
    $customFieldId = $customFieldId[1];

    // create custom field - Integer
    $this->click("newCustomField");
    $this->waitForElementPresent('_qf_Field_done-bottom');
    $customField1 = 'Customfield_int ' . substr(sha1(rand()), 0, 4);
    $this->type('label', $customField1);
    $this->select("data_type[0]", "value=1");

    // clicking save
    $this->click('_qf_Field_done-bottom');
    $this->waitForElementPresent('newCustomField');
    $this->waitForText('crm-notification-container', "Custom field '{$customField1}' has been saved.");
    $this->assertTrue($this->isTextPresent($customField1), 'Missing text: ' . $customField1);
    $this->waitForAjaxContent();
    $customFieldId1 = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr/td/div[text()='$customField1']/../../td[8]/span/a@href"));
    $customFieldId1 = $customFieldId1[1];

    // create custom field - Number
    $this->click("newCustomField");
    $this->waitForElementPresent('_qf_Field_done-bottom');
    $customField2 = 'Customfield_Number ' . substr(sha1(rand()), 0, 4);
    $this->type('label', $customField2);
    $this->select("data_type[0]", "value=2");

    // clicking save
    $this->click('_qf_Field_done-bottom');
    $this->waitForElementPresent('newCustomField');
    $this->waitForText('crm-notification-container', "Custom field '{$customField2}' has been saved.");
    $this->assertTrue($this->isTextPresent($customField2), 'Missing text: ' . $customField2);
    $this->waitForAjaxContent();
    $customFieldId2 = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr/td/div[text()='$customField2']/../../td[8]/span/a@href"));
    $customFieldId2 = $customFieldId2[1];

    // create custom field - "alphanumeric select"
    $this->click("newCustomField");
    $this->waitForElementPresent('_qf_Field_done-bottom');
    $customField3 = 'Customfield_alp_select' . substr(sha1(rand()), 0, 4);
    $customFieldId3 = $this->_createMultipleValueCustomField($customField3, 'Select');

    // create custom field - "alphanumeric radio"
    $this->click("newCustomField");
    $this->waitForElementPresent('_qf_Field_done-bottom');
    $customField4 = 'Customfield_alp_radio' . substr(sha1(rand()), 0, 4);
    $customFieldId4 = $this->_createMultipleValueCustomField($customField4, 'Radio');

    // create custom field - "alphanumeric checkbox"
    $this->click("newCustomField");
    $this->waitForElementPresent('_qf_Field_done-bottom');
    $customField5 = 'Customfield_alp_checkbox' . substr(sha1(rand()), 0, 4);
    $customFieldId5 = $this->_createMultipleValueCustomField($customField5, 'CheckBox');

    // create custom field - "alphanumeric multiselect"
    $this->click("newCustomField");
    $this->waitForElementPresent('_qf_Field_done-bottom');
    $customField6 = 'Customfield_alp_multiselect' . substr(sha1(rand()), 0, 4);
    $customFieldId6 = $this->_createMultipleValueCustomField($customField6, 'Multi-Select');

    // create custom field - "alphanumeric autocompleteselect"
    $this->click("newCustomField");
    $this->waitForElementPresent('_qf_Field_done-bottom');
    $customField8 = 'Customfield_alp_autocompleteselect' . substr(sha1(rand()), 0, 4);
    $customFieldId8 = $this->_createMultipleValueCustomField($customField8, 'Autocomplete-Select');

    // create custom field - Money
    $this->click("newCustomField");
    $this->waitForElementPresent('_qf_Field_done-bottom');
    $customField9 = 'Customfield_Money' . substr(sha1(rand()), 0, 4);
    $this->type('label', $customField9);
    $this->select("data_type[0]", "value=3");

    // clicking save
    $this->click('_qf_Field_done-bottom');
    $this->waitForElementPresent('newCustomField');
    $this->waitForText('crm-notification-container', "Custom field '{$customField9}' has been saved.");
    $this->assertTrue($this->isTextPresent($customField9), 'Missing text: ' . $customField9);
    $this->waitForAjaxContent();
    $customFieldId9 = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr/td/div[text()='$customField9']/../../td[8]/span/a@href"));
    $customFieldId9 = $customFieldId9[1];

    // create custom field - Date
    $this->click("newCustomField");
    $this->waitForElementPresent('_qf_Field_done-bottom');
    $customField10 = 'Customfield_Date' . substr(sha1(rand()), 0, 4);
    $this->type('label', $customField10);
    $this->select("data_type[0]", "value=5");
    $this->select("date_format", "value=yy-mm-dd");

    // clicking save
    $this->click('_qf_Field_done-bottom');
    $this->waitForElementPresent('newCustomField');
    $this->waitForText('crm-notification-container', "Custom field '{$customField10}' has been saved.");
    $this->assertTrue($this->isTextPresent($customField9), 'Missing text: ' . $customField9);
    $this->waitForAjaxContent();
    $customFieldId10 = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr/td/div[text()='$customField10']/../../td[8]/span/a@href"));
    $customFieldId10 = $customFieldId10[1];

    return array(
      'headers' => array(
        "custom_{$customFieldId}" => "$customField :: $customGroupTitle",
        "custom_{$customFieldId3}" => "$customField3 :: $customGroupTitle",
        "custom_{$customFieldId4}" => "$customField4 :: $customGroupTitle",
        "custom_{$customFieldId5}" => "$customField5 :: $customGroupTitle",
        "custom_{$customFieldId6}" => "$customField6 :: $customGroupTitle",
        "custom_{$customFieldId8}" => "$customField8 :: $customGroupTitle",
        "custom_{$customFieldId1}" => "$customField1 :: $customGroupTitle",
        "custom_{$customFieldId2}" => "$customField2 :: $customGroupTitle",
        "custom_{$customFieldId9}" => "$customField9 :: $customGroupTitle",
        "custom_{$customFieldId10}" => "$customField10 :: $customGroupTitle",
      ),
      'rows' => array(
        0 => array(
          "custom_{$customFieldId}" => "This is a test field",
          "custom_{$customFieldId3}" => "label1",
          "custom_{$customFieldId4}" => "label1",
          "custom_{$customFieldId5}" => "label1",
          "custom_{$customFieldId6}" => "label1",
          "custom_{$customFieldId8}" => "label1",
          "custom_{$customFieldId1}" => 1,
          "custom_{$customFieldId2}" => 12345,
          "custom_{$customFieldId9}" => 123456,
          "custom_{$customFieldId10}" => "2009-12-31",
        ),
      ),
      'customFields' => array(
        $customField => 'This is a test field',
        $customField3 => 'label1',
        $customField4 => 'label1',
        $customField5 => 'label1',
        $customField6 => 'label1',
        $customField8 => 'label1',
        $customField1 => '1',
        $customField2 => '12345',
        $customField9 => '123,456.00',
        //CRM-16068 -- changing assertion to match the date format selected during custom field creation.
        $customField10 => '2009-12-31',
      ),
    );
  }

  /**
   * @param string $customFieldName
   * @param $type
   *
   * @return array
   */
  public function _createMultipleValueCustomField($customFieldName, $type) {
    $this->type('label', $customFieldName);
    $this->select("data_type[0]", "value=0");
    $this->select("data_type[1]", "value=" . $type);
    $this->type("option_label_1", "label1");
    $this->type("option_value_1", "label1");
    $this->type("option_label_2", "label2");
    $this->type("option_value_2", "label2");

    // clicking save
    $this->click('_qf_Field_done-bottom');
    $this->waitForElementPresent('newCustomField');
    $this->waitForText('crm-notification-container', "Custom field '{$customFieldName}' has been saved.");
    $this->assertTrue($this->isTextPresent($customFieldName), 'Missing text: ' . $customFieldName);
    $this->waitForAjaxContent();
    $customFieldId = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr/td/div[text()='$customFieldName']/../../td[8]/span/a@href"));
    $customFieldId = $customFieldId[1];
    return $customFieldId;
  }

}
