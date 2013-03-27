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
require_once 'CRM/Utils/Array.php';
class ImportCiviSeleniumTestCase extends CiviSeleniumTestCase {

  /*
     * Function to test csv import for each component.
     *
     * @params string $component   component name ( Event, Contribution, Membership, Activity etc)
     * @params array  $headers     csv data headers
     * @params array  $rows        csv data rows
     * @params string $contactType contact type
     * @params string $mode        import mode
     * @params array  $fieldMapper select mapper fields while import
     * @params array  $other       other parameters
     *                             useMappingName     : to reuse mapping

     *                             dateFormat         : date format of data
     *                             checkMapperHeaders : to override default check mapper headers
     *                             saveMapping        : save current mapping?
     *                             saveMappingName    : to override mapping name
     *
     */
  function importCSVComponent($component,
    $headers,
    $rows,
    $contactType = 'Individual',
    $mode        = 'Skip',
    $fieldMapper = array(),
    $other       = array()
  ) {

    // Go to contact import page.
    $this->open($this->sboxPath . $this->_getImportComponentUrl($component));

    $this->waitForPageToLoad($this->getTimeoutMsec());

    // check for upload field.
    $this->waitForElementPresent("uploadFile");

    // Create csv file of sample data.
    $csvFile = $this->webtestCreateCSV($headers, $rows);

    // Attach csv file.
    $this->webtestAttachFile('uploadFile', $csvFile);

    // First row is header.
    $this->click('skipColumnHeader');

    // select mode, default is 'Skip'.
    if ($mode == 'Update') {
      $this->click("CIVICRM_QFID_4_4");
    }
    elseif ($mode == 'No Duplicate Checking') {
      $this->click("CIVICRM_QFID_16_6");
    }

    // select contact type, default is 'Individual'.
    if ($component != 'Activity') {
      $contactTypeOption = $this->_getImportComponentContactType($component, $contactType);
      $this->click($contactTypeOption);
    }

    // Date format, default: yyyy-mm-dd OR yyyymmdd
    if (isset($other['dateFormat'])) {
      // default
      $dateFormatMapper = array(
        'yyyy-mm-dd OR yyyymmdd' => "CIVICRM_QFID_1_14",
        'mm/dd/yy OR mm-dd-yy' => "CIVICRM_QFID_2_16",
        'mm/dd/yyyy OR mm-dd-yyyy' => "CIVICRM_QFID_4_18",
        'Month dd, yyyy' => "CIVICRM_QFID_8_20",
        'dd-mon-yy OR dd/mm/yy' => "CIVICRM_QFID_16_22",
        'dd/mm/yyyy' => "CIVICRM_QFID_32_24",
      );
      $this->click($dateFormatMapper[$other['dateFormat']]);
    }

    // Use already created mapping
    $existingMapping = NULL;
    if (isset($other['useMappingName'])) {
      $this->select('savedMapping', "label=" . $other['useMappingName']);
      $existingMapping = $other['useMappingName'];
    }

    // Submit form.
    $this->click('_qf_UploadFile_upload');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Select matching field for cvs data.
    if (!empty($fieldMapper)) {
      foreach ($fieldMapper as $field => $value) {
        $this->select($field, "value={$value}");
      }
    }

    // Check mapping data.
    $this->_checkImportMapperData($headers,
      $rows,
      $existingMapping,
      isset($other['checkMapperHeaders']) ? $other['checkMapperHeaders'] : array()
    );

    // Save mapping
    if (isset($other['saveMapping'])) {
      $mappingName = isset($other['saveMappingName']) ? $other['saveMappingName'] : "{$component}Import_" . substr(sha1(rand()), 0, 7);

      $this->click('saveMapping');
      $this->type('saveMappingName', $mappingName);
      $this->type('saveMappingDesc', "Mapping for {$contactType}");
    }

    // Submit form.
    $this->click('_qf_MapField_next');
    $this->waitForElementPresent('_qf_Preview_next-bottom');

    // Check mapping data.
    $this->_checkImportMapperData($headers, $rows, $existingMapping, isset($other['checkMapperHeaders']) ? $other['checkMapperHeaders'] : array());

    // Submit form.
    $this->clickLink('_qf_Preview_next-bottom', "_qf_Summary_next");

    // Check success message.
    $this->assertTrue($this->isTextPresent("Import has completed successfully. The information below summarizes the results."));

    // Check summary Details.
    $importedRecords = count($rows);
    $checkSummary = array(
      'Total Rows' => $importedRecords,
      'Records Imported' => $importedRecords,
    );

    foreach ($checkSummary as $label => $value) {
      $this->verifyText("xpath=//table[@id='summary-counts']/tbody/tr/td[text()='{$label}']/following-sibling::td", preg_quote($value));
    }
  }

  /*
     * Function to test contact import.
     *
     * @params array  $headers     csv data headers
     * @params array  $rows        csv data rows
     * @params string $contactType contact type
     * @params string $mode        import mode
     * @params array  $fieldMapper select mapper fields while import
     * @params array  $other       other parameters
     *                             contactSubtype     : import for selected Contact Subtype

     *                             useMappingName     : to reuse mapping
     *                             dateFormat         : date format of data
     *                             checkMapperHeaders : to override default check mapper headers
     *                             saveMapping        : save current mapping?
     *                             saveMappingName    : to override mapping name
     *                             createGroup        : create new group?
     *                             createGroupName    : to override new Group name
     *                             createTag          : create new tag?
     *                             createTagName      : to override new Tag name
     *                             selectGroup        : select existing group for contacts
     *                             selectTag          : select existing tag for contacts
     *                             callbackImportSummary : function to override default import summary assertions
     *
     * @params string $type        import type (csv/sql)
     *                             @todo:currently only supports csv, need to work on sql import

     */
  function importContacts($headers, $rows, $contactType = 'Individual', $mode = 'Skip', $fieldMapper = array(
    ), $other = array(), $type = 'csv') {

    // Go to contact import page.
    $this->openCiviPage("import/contact", "reset=1", "uploadFile");

    $originalHeaders = $headers;
    $originalRows = $rows;

    // format headers and row to import contacts with relationship data.
    $this->_formatContactCSVdata($headers, $rows);

    // Create csv file of sample data.
    $csvFile = $this->webtestCreateCSV($headers, $rows);

    // Attach csv file.
    $this->webtestAttachFile('uploadFile', $csvFile);

    // First row is header.
    $this->click('skipColumnHeader');

    // select mode, default is 'Skip'.
    if ($mode == 'Update') {
      $this->click("CIVICRM_QFID_4_4");
    }
    elseif ($mode == 'Fill') {
      $this->click("CIVICRM_QFID_8_6");
    }
    elseif ($mode == 'No Duplicate Checking') {
      $this->click("CIVICRM_QFID_16_8");
    }

    // select contact type, default is 'Individual'.
    if ($contactType == 'Organization') {
      $this->click("CIVICRM_QFID_4_14");
    }
    elseif ($contactType == 'Household') {
      $this->click("CIVICRM_QFID_2_12");
    }

    // Select contact subtype
    if (isset($other['contactSubtype'])) {
      if ($contactType != 'Individual') {
        // Because it tends to cause problems, all uses of sleep() must be justified in comments
        // Sleep should never be used for wait for anything to load from the server
        // FIXME: this is bad, using sleep to wait for AJAX
        // Need to use a better way to wait for contact subtypes to repopulate
        sleep(5);
      }
      $this->waitForElementPresent("subType");
      $this->select('subType', 'label=' . $other['contactSubtype']);
    }

    if (isset($other['dedupe'])) {
      $this->waitForElementPresent("dedupe");
      $this->select('dedupe', 'value=' . $other['dedupe']);
    }

    // Use already created mapping
    $existingMapping = NULL;
    if (isset($other['useMappingName'])) {
      $this->select('savedMapping', "label=" . $other['useMappingName']);
      $existingMapping = $other['useMappingName'];
    }

    // Date format, default: yyyy-mm-dd OR yyyymmdd
    if (isset($other['dateFormat'])) {
      // default
      $dateFormatMapper = array(
        'yyyy-mm-dd OR yyyymmdd' => "CIVICRM_QFID_1_16",
        'mm/dd/yy OR mm-dd-yy' => "CIVICRM_QFID_2_18",
        'mm/dd/yyyy OR mm-dd-yyyy' => "CIVICRM_QFID_4_20",
        'Month dd, yyyy' => "CIVICRM_QFID_8_22",
        'dd-mon-yy OR dd/mm/yy' => "CIVICRM_QFID_16_24",
        'dd/mm/yyyy' => "CIVICRM_QFID_32_26",
      );
      $this->click($dateFormatMapper[$other['dateFormat']]);
    }

    // Submit form.
    $this->click('_qf_DataSource_upload');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    if (isset($other['checkMapperHeaders'])) {
      $checkMapperHeaders = $other['checkMapperHeaders'];
    }
    else {
      $checkMapperHeaders = array(
        1 => 'Column Names',
        2 => 'Import Data (row 1)',
        3 => 'Import Data (row 2)',
        4 => 'Matching CiviCRM Field',
      );
    }

    // Check mapping data.
    $this->_checkImportMapperData($headers, $rows, $existingMapping, $checkMapperHeaders, 'td');

    // Select matching field for cvs data.
    if (!empty($fieldMapper)) {
      foreach ($fieldMapper as $field => $value) {
        $this->select($field, "value={$value}");
      }
    }

    // Save mapping
    if (isset($other['saveMapping'])) {
      $mappingName = isset($other['saveMappingName']) ? $other['saveMappingName'] : 'ContactImport_' . substr(sha1(rand()), 0, 7);
      $this->click('saveMapping');
      $this->type('saveMappingName', $mappingName);
      $this->type('saveMappingDesc', "Mapping for {$contactType}");
    }

    // Submit form.
    $this->click('_qf_MapField_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Check mapping data.
    $this->_checkImportMapperData($headers, $rows, $existingMapping, $checkMapperHeaders, 'td');

    // Add imported contacts in new group.
    $groupName = NULL;
    $existingGroups = array();
    if (isset($other['createGroup'])) {
      $groupName = isset($other['createGroupName']) ? $other['createGroupName'] : 'ContactImport_' . substr(sha1(rand()), 0, 7);

      $this->click("css=#new-group div.crm-accordion-header");
      $this->type('newGroupName', $groupName);
      $this->type('newGroupDesc', "Group For {$contactType}");
    }
    if (isset($other['selectGroup'])) {
      // reuse existing groups.
      if (is_array($other['selectGroup'])) {
        foreach ($other['selectGroup'] as $existingGroup) {
          $this->select('groups[]', 'label=' . $existingGroup);
          $existingGroups[] = $existingGroup;
        }
      }
      else {
        $this->select('groups[]', 'label=' . $other['selectGroup']);
        $existingGroups[] = $other['selectGroup'];
      }
    }

    // Assign new tag to the imported contacts.
    $tagName = NULL;
    $existingTags = array();
    if (isset($other['createTag'])) {
      $tagName = isset($other['createTagName']) ? $other['createTagName'] : "{$contactType}_" . substr(sha1(rand()), 0, 7);

      $this->click("css=#new-tag div.crm-accordion-header");
      $this->type('newTagName', $tagName);
      $this->type('newTagDesc', "Tag for {$contactType}");
    }
    if (isset($other['selectTag'])) {
      $this->click("css=#existing-tags div.crm-accordion-header");
      // reuse existing tags.
      if (is_array($other['selectTag'])) {
        foreach ($other['selectTag'] as $existingTag) {
          $this->click("xpath=//div[@id='existing-tags']//div[@class='crm-accordion-body']//label[text()='{$existingTag}']");
          $existingTags[] = $existingTag;
        }
      }
      else {
        $this->click("xpath=//div[@id='existing-tags']//div[@class='crm-accordion-body']//label[text()='" . $other['selectTag'] . "']");
        $existingTags[] = $other['selectTag'];
      }
    }

    // Submit form.
    $this->click('_qf_Preview_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Check confirmation alert.
    $this->assertTrue((bool)preg_match("/^Are you sure you want to Import now[\s\S]$/", $this->getConfirmation()));
    $this->chooseOkOnNextConfirmation();
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Visit summary page.
    $this->waitForElementPresent("_qf_Summary_next");

    // Check success message.
    $this->assertTrue($this->isTextPresent("Import has completed successfully. The information below summarizes the results."));

    // Check summary Details.
    $importedContacts = $totalRows = count($originalRows);

    // Include relationships contacts ( if exists )
    if (isset($originalHeaders['contact_relationships']) && is_array($originalHeaders['contact_relationships'])) {
      foreach ($originalRows as $row) {
        $importedContacts += count($row['contact_relationships']);
      }
    }

    $importedContactsCount = ($importedContacts == 1) ? 'One contact' : "$importedContacts contacts";
    $taggedContactsCount = ($importedContacts == 1) ? 'One contact is' : "$importedContacts contacts are";
    $checkSummary = array(
      'Total Rows' => $totalRows,
      'Total Contacts' => $importedContacts,
    );

    if ($groupName) {
      $checkSummary['Import to Groups'] = "{$groupName}: {$importedContactsCount} added to this new group.";
    }

    if ($tagName) {
      $checkSummary['Tagged Imported Contacts'] = "{$tagName}: {$taggedContactsCount} tagged with this tag.";
    }

    if ($existingGroups) {
      if (!isset($checkSummary['Import to Groups'])) {
        $checkSummary['Import to Groups'] = '';
      }
      foreach ($existingGroups as $existingGroup) {
        $checkSummary['Import to Groups'] .= "{$existingGroup}: {$importedContactsCount} added to this existing group.";
      }
    }

    if ($existingTags) {
      if (!isset($checkSummary['Tagged Imported Contacts'])) {
        $checkSummary['Tagged Imported Contacts'] = '';
      }
      foreach ($existingTags as $existingTag) {
        $checkSummary['Tagged Imported Contacts'] .= "{$existingTag}: {$taggedContactsCount} tagged with this tag.";
      }
    }

    if (!empty($other['callbackImportSummary']) && is_callable(array(
      $this, $other['callbackImportSummary']))) {
      $callbackImportSummary = $other['callbackImportSummary'];
      $this->$callbackImportSummary($originalHeaders, $originalRows, $checkSummary);
    }
    else {
      foreach ($checkSummary as $label => $value) {
        $this->verifyText("xpath=//table[@id='summary-counts']/tbody/tr/td[text()='{$label}']/following-sibling::td", preg_quote($value));
      }
    }
  }

  /*
     * Helper function to get the import url of the component.
     *
     * @params string $component component name
     *
     * @return string import url

     */
  function _getImportComponentUrl($component) {

    $importComponentUrl = array(
      'Event' => 'civicrm/event/import?reset=1',
      'Contribution' => 'civicrm/contribute/import?reset=1',
      'Membership' => 'civicrm/member/import?reset=1',
      'Activity' => 'civicrm/import/activity?reset=1',
    );

    return $importComponentUrl[$component];
  }

  /*
     * Helper function to get the import url of the component.
     *
     * @params string $component component name
     *
     * @return string import url

     */
  function _getImportComponentContactType($component, $contactType) {
    $importComponentMode = array(
      'Event' => array('Individual' => 'CIVICRM_QFID_1_8',
        'Household' => 'CIVICRM_QFID_2_10',
        'Organization' => 'CIVICRM_QFID_4_12',
      ),
      'Contribution' => array(
        'Individual' => 'CIVICRM_QFID_1_6',
        'Household' => 'CIVICRM_QFID_2_8',
        'Organization' => 'CIVICRM_QFID_4_10',
      ),
      'Membership' => array(
        'Individual' => 'CIVICRM_QFID_1_6',
        'Household' => 'CIVICRM_QFID_2_8',
        'Organization' => 'CIVICRM_QFID_4_10',
      ),
    );

    return $importComponentMode[$component][$contactType];
  }

  /*
     * Helper function to check import mapping fields.
     *
     * @params array  $headers            field headers
     * @params array  $rows               field rows
     * @params array  $checkMapperHeaders override default mapper headers
     */
  function _checkImportMapperData($headers, $rows, $existingMapping = NULL, $checkMapperHeaders = array(
    ), $headerSelector = 'th') {

    if (empty($checkMapperHeaders)) {
      $checkMapperHeaders = array(
        1 => 'Column Headers',
        2 => 'Import Data (row 2)',
        3 => 'Import Data (row 3)',
        4 => 'Matching CiviCRM Field',
      );
    }

    $rowNumber = 1;
    if ($existingMapping) {
      $this->verifyText("xpath=//div[@id='map-field']//table[1]/tbody/tr[{$rowNumber}]/th[1]", preg_quote("Saved Field Mapping: {$existingMapping}"));
      $rowNumber++;
    }

    foreach ($checkMapperHeaders as $rownum => $value) {
      $this->verifyText("xpath=//div[@id='map-field']//table[1]/tbody/tr[{$rowNumber}]/{$headerSelector}[{$rownum}]", preg_quote($value));
    }
    $rowNumber++;

    foreach ($headers as $field => $header) {
      $this->verifyText("xpath=//div[@id='map-field']//table[1]/tbody/tr[{$rowNumber}]/td[1]", preg_quote($header));
      $colnum = 2;
      foreach ($rows as $row) {
        $this->verifyText("xpath=//div[@id='map-field']//table[1]/tbody/tr[{$rowNumber}]/td[{$colnum}]", preg_quote($row[$field]));
        $colnum++;
      }
      $rowNumber++;
    }
  }

  /*
     * Helper function to get imported contact ids.
     *
     * @params array  $rows        fields rows
     * @params string $contactType contact type

     *
     * @return array  $contactIds  imported contact ids
     */
  function _getImportedContactIds($rows, $contactType = 'Individual') {
    $contactIds = array();

    foreach ($rows as $row) {
      $searchName = '';

      // Build search name.
      if ($contactType == 'Individual') {
        $searchName = "{$row['last_name']}, {$row['first_name']}";
      }
      elseif ($contactType == 'Organization') {
        $searchName = $row['organization_name'];
      }
      elseif ($contactType == 'Household') {
        $searchName = $row['household_name'];
      }

      $this->openCiviPage("dashboard", "reset=1");

      // Type search name in autocomplete.
      $this->click("css=input#sort_name_navigation");
      $this->type("css=input#sort_name_navigation", $searchName);
      $this->typeKeys("css=input#sort_name_navigation", $searchName);

      // Wait for result list.
      $this->waitForElementPresent("css=div.ac_results-inner li");

      // Visit contact summary page.
      $this->clickLink("css=div.ac_results-inner li");

      // Get contact id from url.
      $contactIds[] = $this->urlArg('cid');
    }

    return $contactIds;
  }

  /*
     * Helper function to format headers and rows for contact relationship data.
     *
     * @params array  $headers data headers
     * @params string $rows    data rows
     */
  function _formatContactCSVdata(&$headers, &$rows) {
    if (!isset($headers['contact_relationships'])) {
      return;
    }

    $relationshipHeaders = $headers['contact_relationships'];
    unset($headers['contact_relationships']);

    if (empty($relationshipHeaders) || !is_array($relationshipHeaders)) {
      return;
    }

    foreach ($relationshipHeaders as $relationshipHeader) {
      $headers = array_merge($headers, $relationshipHeader);
    }

    foreach ($rows as & $row) {
      $relationshipRows = $row['contact_relationships'];
      unset($row['contact_relationships']);
      foreach ($relationshipRows as $relationshipRow) {
        $row = array_merge($row, $relationshipRow);
      }
    }
  }
}

