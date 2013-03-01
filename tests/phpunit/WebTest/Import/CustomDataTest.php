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


require_once 'WebTest/Import/ImportCiviSeleniumTestCase.php';
class WebTest_Import_CustomDataTest extends ImportCiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testCustomDataImport() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin();

    $firstName1       = 'Ma_' . substr(sha1(rand()), 0, 7);
    $firstName2       = 'An_' . substr(sha1(rand()), 0, 7);
    $customGroupTitle = 'Custom ' . substr(sha1(rand()), 0, 7);

    $firstName3 = 'Ma' . substr(sha1(rand()), 0, 4);
    $this->webtestAddContact($firstName3, "Anderson", TRUE);
    $sortName3 = "$firstName3 Anderson";
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $url1 = explode('&cid=', $this->getLocation());
    $id1 = $url1[1];

    $firstName4 = 'Ma' . substr(sha1(rand()), 0, 4);
    $this->webtestAddContact($firstName4, "Anderson", TRUE);
    $sortName4 = "$firstName4 Anderson";
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $url2 = explode('&cid=', $this->getLocation());
    $id2 = $url2[1];

    // Get sample import data.
    list($headers, $rows, $customDataVerify) = $this->_individualCustomCSVData($customGroupTitle, $firstName1, $firstName2,
      $id1, $id2
    );

    // Import and check Individual contacts in Skip mode.
    $other = array(
      'saveMapping' => TRUE,
      'createGroup' => TRUE,
      'createTag' => TRUE,
    );

    $this->importContacts($headers, $rows, 'Individual', 'Skip', array(), $other);

    // Find the contact
    $this->open($this->sboxPath . "civicrm/contact/search?reset=1");
    $this->waitForElementPresent('_qf_Basic_refresh');
    $this->type('sort_name', $firstName1);
    $this->click('_qf_Basic_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("xpath=//div[@class='crm-search-results']/table/tbody/tr/td[11]/span/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    for ($cnt = 0; $cnt < 2; $cnt++) {
      foreach ($customDataVerify['rows'][$cnt] as $key => $values) {
        $rows[$cnt][$key] = $values;
      }
    }

    $CGTableId = preg_replace('/\s/', '_', trim($customGroupTitle));
    if ($this->isElementPresent("xpath=//table[@id='{$CGTableId}_0']")) {
      $this->click("xpath=//table[@id='{$CGTableId}_0']/tbody/tr[@class='columnheader']/td[@class='grouplabel']/a");
    }
    elseif ($this->isElementPresent("xpath=//table[@id='{$CGTableId}_1']")) {
      $this->click("xpath=//table[@id='{$CGTableId}_1']/tbody/tr[@class='columnheader']/td[@class='grouplabel']/a");
    }

    // Verify if custom data added
    $cnt = 1;
    foreach ($rows[0] as $key => $value) {
      if ($cnt == 4) {
        $value = date('F jS, Y');
      }
      elseif ($cnt == 7) {
        $value = $sortName3;
      }
      $this->assertTrue($this->isTextPresent($value));
      $cnt++;
    }
  }

  /*
     *  Helper function to provide data for custom data import.
     */
  function _individualCustomCSVData($customGroupTitle, $firstName1, $firstName2, $id1, $id2) {
    list($customDataParams, $customDataVerify) = $this->_addCustomData($customGroupTitle, $id1, $id2);

    $headers = array(
      'first_name' => 'First Name',
      'last_name' => 'Last Name',
      'email' => 'Email',
    );

    foreach ($customDataParams['headers'] as $key => $values) {
      $headers[$key] = $values;
    }

    $rows = array(
      array(
        'first_name' => $firstName1,
        'last_name' => 'Anderson',
        'email' => substr(sha1(rand()), 0, 7) . '@example.com',
      ),
      array(
        'first_name' => $firstName2,
        'last_name' => 'Summerson',
        'email' => substr(sha1(rand()), 0, 7) . '@example.com',
      ),
    );

    for ($cnt = 0; $cnt < 2; $cnt++) {
      foreach ($customDataParams['rows'][$cnt] as $key => $values) {
        $rows[$cnt][$key] = $values;
      }
    }

    return array($headers, $rows, $customDataVerify);
  }

  function _addCustomData($customGroupTitle, $id1, $id2) {
    // Go directly to the URL of the screen that you will be testing (New Custom Group).
    $this->open($this->sboxPath . "civicrm/admin/custom/group?reset=1");

    //add new custom data
    $this->click("//a[@id='newCustomDataGroup']/span");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //fill custom group title
    $this->click("title");
    $this->type("title", $customGroupTitle);

    //custom group extends
    $this->click("extends[0]");
    $this->select("extends[0]", "value=Contact");
    $this->click("//option[@value='Contact']");
    $this->click('_qf_Group_next-bottom');
    $this->waitForElementPresent('_qf_Field_cancel-bottom');

    //Is custom group created?
    $this->assertTrue($this->isTextPresent("Your custom field set '{$customGroupTitle}' has been added. You can add custom fields now."));
    $url = explode('gid=', $this->getLocation());
    $gid = $url[1];

    // create another custom field - Date
    $dateFieldLabel = 'custom_field_date_' . substr(sha1(rand()), 0, 4);
    $this->type('label', $dateFieldLabel);
    $this->click('data_type[0]');
    $this->select('data_type[0]', "label=Date");
    $this->waitForElementPresent('start_date_years');

    // enter years prior to current date
    $this->type('start_date_years', 3);

    // enter years upto the end year
    $this->type('end_date_years', 3);

    // select the date and time format
    $this->select('date_format', "value=yy-mm-dd");
    $this->select('time_format', "value=2");

    //enter pre help message
    $this->type("help_pre", "this is field pre help");

    //enter post help message
    $this->type("help_post", "this field post help");

    //Is searchable?
    $this->click("is_searchable");

    // clicking save
    $this->click('_qf_Field_next-bottom');
    $this->waitForElementPresent('newCustomField');

    $this->assertTrue($this->isTextPresent("Your custom field '{$dateFieldLabel}' has been saved."));
    
    $dateFieldId = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr/td/span[text()='$dateFieldLabel']/../../td[8]/span/a@href"));
    $dateFieldId = $dateFieldId[1];

    // create another custom field - Integer Radio
    $this->click("//a[@id='newCustomField']/span");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("data_type[0]");
    $this->select("data_type[0]", "value=1");
    $this->click("//option[@value='1']");
    $this->click("data_type[1]");
    $this->select("data_type[1]", "value=Radio");
    $this->click("//option[@value='Radio']");

    $radioFieldLabel = 'custom_field_radio' . substr(sha1(rand()), 0, 4);
    $this->type("label", $radioFieldLabel);
    $radioOptionLabel1 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type("option_label_1", $radioOptionLabel1);
    $this->type("option_value_1", "1");
    $radioOptionLabel2 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type("option_label_2", $radioOptionLabel2);
    $this->type("option_value_2", "2");

    //select options per line
    $this->type("options_per_line", "3");

    //enter pre help msg
    $this->type("help_pre", "this is field pre help");

    //enter post help msg
    $this->type("help_post", "this is field post help");

    //Is searchable?
    $this->click("is_searchable");

    //clicking save
    $this->click("_qf_Field_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Is custom field created
    $this->assertTrue($this->isTextPresent("Your custom field '$radioFieldLabel' has been saved."));
    $radioFieldId = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr/td/span[text()='$radioFieldLabel']/../../td[8]/span/a@href"));
    $radioFieldId = $radioFieldId[1];

    // create another custom field - multiselect
    $this->click("//a[@id='newCustomField']/span");
    $this->waitForElementPresent('_qf_Field_cancel-bottom');
    $multiSelectLabel = 'custom_field_multiSelect_' . substr(sha1(rand()), 0, 4);
    $this->type('label', $multiSelectLabel);
    $this->click('data_type[1]');
    $this->select('data_type[1]', "label=Multi-Select");
    $this->waitForElementPresent('option_label_1');

    // enter multiple choice options
    $multiSelectOptionLabel1 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_1', $multiSelectOptionLabel1);
    $this->type('option_value_1', 1);
    $multiSelectOptionLabel2 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_2', $multiSelectOptionLabel2);
    $this->type('option_value_2', 2);
    $this->click("link=another choice");
    $multiSelectOptionLabel3 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_3', $multiSelectOptionLabel3);
    $this->type('option_value_3', 3);
    $this->click("link=another choice");

    //enter pre help msg
    $this->type("help_pre", "this is field pre help");

    //enter post help msg
    $this->type("help_post", "this is field post help");

    //Is searchable?
    $this->click("is_searchable");

    // clicking save
    $this->click('_qf_Field_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("Your custom field '{$multiSelectLabel}' has been saved."));
    $multiSelectFieldId = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr/td/span[text()='$multiSelectLabel']/../../td[8]/span/a@href"));
    $multiSelectFieldId = $multiSelectFieldId[1];

    // create another custom field - contact reference
    $this->click("//a[@id='newCustomField']/span");
    $this->waitForElementPresent('_qf_Field_cancel-bottom');
    $contactReferenceLabel = 'custom_field_contactReference_' . substr(sha1(rand()), 0, 4);
    $this->type('label', $contactReferenceLabel);
    $this->click('data_type[0]');
    $this->select('data_type[0]', "label=Contact Reference");

    //enter pre help msg
    $this->type("help_pre", "this is field pre help");

    //enter post help msg
    $this->type("help_post", "this is field post help");

    //Is searchable?
    $this->click("is_searchable");

    // clicking save
    $this->click('_qf_Field_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent("Your custom field '{$contactReferenceLabel}' has been saved."));
    $contactReferenceFieldId = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr/td/span[text()='$contactReferenceLabel']/../../td[8]/span/a@href"));
    $contactReferenceFieldId = $contactReferenceFieldId[1];

    $customDataParams = array(
      'headers' =>
      array(
        "custom_{$dateFieldId}" => "$dateFieldLabel :: $customGroupTitle",
        "custom_{$radioFieldId}" => "$radioFieldLabel :: $customGroupTitle",
        "custom_{$multiSelectFieldId}" => "$multiSelectLabel :: $customGroupTitle",
        "custom_{$contactReferenceFieldId}" => "$contactReferenceLabel :: $customGroupTitle",
      ),
      'rows' =>
      array(0 => array("custom_{$dateFieldId}" => date('Y-m-d'),
          "custom_{$radioFieldId}" => '2',
          "custom_{$multiSelectFieldId}" => '3',
          "custom_{$contactReferenceFieldId}" => $id1,
        ),
        1 => array("custom_{$dateFieldId}" => date('Y-m-d', mktime(0, 0, 0, 4, 5, date('Y'))),
          "custom_{$radioFieldId}" => '1',
          "custom_{$multiSelectFieldId}" => '2',
          "custom_{$contactReferenceFieldId}" => $id2,
        ),
      ),
    );

    $customDataVerify = $customDataParams;
    $customDataVerify['rows'][0]["custom_{$radioFieldId}"] = $radioOptionLabel2;
    $customDataVerify['rows'][1]["custom_{$radioFieldId}"] = $radioOptionLabel1;
    $customDataVerify['rows'][0]["custom_{$multiSelectFieldId}"] = $multiSelectOptionLabel3;
    $customDataVerify['rows'][1]["custom_{$multiSelectFieldId}"] = $multiSelectOptionLabel2;

    return array($customDataParams, $customDataVerify);
  }
}

