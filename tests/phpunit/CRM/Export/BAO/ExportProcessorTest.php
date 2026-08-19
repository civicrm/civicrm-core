<?php

/**
 * @group headless
 */
class CRM_Export_BAO_ExportProcessorTest extends CiviUnitTestCase {

  /**
   * @var \CRM_Export_BAO_ExportProcessor
   */
  protected $processor;

  /**
   * @var \League\Csv\Reader
   */
  protected $csv;

  protected $contactID;

  protected $locationTypeID;

  public function setUp(): void {
    parent::setUp();
    $this->locationTypeID = $this->locationTypeCreate([
      'name' => 'Secondary',
      'display_name' => 'Secondary',
    ]);
    $this->contactID = $this->individualCreate();
  }

  public function tearDown(): void {
    $this->quickCleanup([
      'civicrm_contact',
      'civicrm_email',
      'civicrm_phone',
      'civicrm_address',
    ]);
    if ($this->processor && $this->processor->getTemporaryTable()) {
      CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS ' . $this->processor->getTemporaryTable());
    }
    if ($this->locationTypeID) {
      $this->locationTypeDelete($this->locationTypeID);
    }
    parent::tearDown();
  }

  /**
   * Test that top-level address fields (including pseudoconstants like
   * state_province/country) export primary address data even when a
   * location-type WHERE filter disables _primaryLocation on the query.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\Exception
   */
  public function testTopLevelAddressFieldsUsePrimaryWhenLocationFilterPresent(): void {
    $this->callAPISuccess('Address', 'create', [
      'contact_id' => $this->contactID,
      'location_type_id' => 'Home',
      'street_address' => '123 Primary St',
      'city' => 'Primary City',
      'postal_code' => '11111',
      'state_province_id' => 1004,
      'country_id' => 1228,
      'is_primary' => 1,
    ]);

    $this->callAPISuccess('Address', 'create', [
      'contact_id' => $this->contactID,
      'location_type_id' => $this->locationTypeID,
      'street_address' => '456 Secondary Ave',
      'city' => 'Secondary City',
      'postal_code' => '22222',
      'state_province_id' => 1021,
      'country_id' => 1228,
      'is_primary' => 0,
    ]);

    $selectedFields = [
      ['contact_type' => 'Individual', 'name' => 'street_address'],
      ['contact_type' => 'Individual', 'name' => 'city'],
      ['contact_type' => 'Individual', 'name' => 'postal_code'],
      ['contact_type' => 'Individual', 'name' => 'state_province'],
      ['contact_type' => 'Individual', 'name' => 'country'],
      ['contact_type' => 'Individual', 'name' => 'street_address', 'location_type_id' => $this->locationTypeID],
      ['contact_type' => 'Individual', 'name' => 'city', 'location_type_id' => $this->locationTypeID],
      ['contact_type' => 'Individual', 'name' => 'postal_code', 'location_type_id' => $this->locationTypeID],
      ['contact_type' => 'Individual', 'name' => 'state_province', 'location_type_id' => $this->locationTypeID],
    ];

    $this->doExportTest([
      'fields' => $selectedFields,
      'params' => [['location_type', '=', [$this->locationTypeID], 0, 0]],
    ]);

    $row = $this->csv->nth(0);
    $this->assertEquals('123 Primary St', $row['Street Address']);
    $this->assertEquals('Primary City', $row['City']);
    $this->assertEquals('11111', $row['Postal Code']);
    $this->assertEquals('CA', $row['State']);
    $this->assertEquals('United States', $row['Country']);
    $this->assertEquals('456 Secondary Ave', $row['Secondary-Street Address']);
    $this->assertEquals('Secondary City', $row['Secondary-City']);
    $this->assertEquals('22222', $row['Secondary-Postal Code']);
    $this->assertEquals('MI', $row['Secondary-State']);
  }

  /**
   * Test that top-level email fields export primary email data even when
   * a location-type WHERE filter disables _primaryLocation on the query.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\Exception
   */
  public function testTopLevelEmailFieldUsePrimaryWhenLocationFilterPresent(): void {
    // Address at the secondary location type is needed so the
    // location_type WHERE filter matches this contact.
    $this->callAPISuccess('Address', 'create', [
      'contact_id' => $this->contactID,
      'location_type_id' => $this->locationTypeID,
      'street_address' => '1 Test St',
      'is_primary' => 0,
    ]);

    $this->callAPISuccess('Email', 'create', [
      'contact_id' => $this->contactID,
      'location_type_id' => 'Home',
      'email' => 'primary@example.com',
      'is_primary' => 1,
    ]);

    $this->callAPISuccess('Email', 'create', [
      'contact_id' => $this->contactID,
      'location_type_id' => $this->locationTypeID,
      'email' => 'secondary@example.com',
      'is_primary' => 0,
    ]);

    $selectedFields = [
      ['contact_type' => 'Individual', 'name' => 'email'],
      ['contact_type' => 'Individual', 'name' => 'email', 'location_type_id' => $this->locationTypeID],
    ];

    $this->doExportTest([
      'fields' => $selectedFields,
      'params' => [['location_type', '=', [$this->locationTypeID], 0, 0]],
    ]);

    $row = $this->csv->nth(0);
    $this->assertEquals('primary@example.com', $row['Email']);
    $this->assertEquals('secondary@example.com', $row['Secondary-Email']);
  }

  /**
   * Test that the primary phone JOIN includes is_primary = 1 when
   * a location type filter moves phone into addHierarchicalElements.
   *
   * Without this fix, the phone JOIN is unfiltered, producing
   * duplicate rows for contacts with multiple phones.
   *
   * @throws \CRM_Core_Exception
   */
  public function testTopLevelPhoneFieldUsePrimaryWhenLocationFilterPresent(): void {
    $selectedFields = [
      ['contact_type' => 'Individual', 'name' => 'phone'],
    ];
    $processor = $this->createExportProcessor($selectedFields, [['location_type', '=', [$this->locationTypeID], 0, 0]]);
    [, $queryString] = $processor->runQuery([], '');
    $this->assertMatchesRegularExpression(
      '/LEFT JOIN civicrm_phone.*is_primary = 1/',
      $queryString,
      'Phone JOIN should filter on is_primary'
    );
  }

  /**
   * Test that exporting primary phone + primary address doesn't produce
   * duplicate rows when they have different location types.
   *
   * addHierarchicalElements creates a location_type JOIN with OR conditions
   * for both phone and address. When those have different location_type_ids,
   * the JOIN matches multiple location_type rows, duplicating the contact.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\Exception
   */
  public function testNoDuplicateRowsWhenPrimaryPhoneAndAddressHaveDifferentLocationTypes(): void {
    $this->callAPISuccess('Address', 'create', [
      'contact_id' => $this->contactID,
      'location_type_id' => 'Work',
      'street_address' => '100 Work St',
      'is_primary' => 1,
    ]);

    $this->callAPISuccess('Phone', 'create', [
      'contact_id' => $this->contactID,
      'location_type_id' => 'Home',
      'phone' => '555-1234',
      'is_primary' => 1,
    ]);

    $this->callAPISuccess('Address', 'create', [
      'contact_id' => $this->contactID,
      'location_type_id' => $this->locationTypeID,
      'street_address' => '200 Secondary Ave',
      'is_primary' => 0,
    ]);

    $selectedFields = [
      ['contact_type' => 'Individual', 'name' => 'street_address'],
      ['contact_type' => 'Individual', 'name' => 'phone'],
      ['contact_type' => 'Individual', 'name' => 'street_address', 'location_type_id' => $this->locationTypeID],
    ];

    $this->doExportTest([
      'fields' => $selectedFields,
      'params' => [['location_type', '=', [$this->locationTypeID], 0, 0]],
    ]);

    $this->assertCount(1, $this->csv);
    $row = $this->csv->nth(0);
    $this->assertEquals('100 Work St', $row['Street Address']);
    $this->assertEquals('555-1234', $row['Phone']);
    $this->assertEquals('200 Secondary Ave', $row['Secondary-Street Address']);
  }

  /**
   * Test that top-level address fields still work correctly when no
   * location-specific fields are requested (regression check).
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\Exception
   */
  public function testTopLevelAddressFieldsAloneStillWork(): void {
    $this->callAPISuccess('Address', 'create', [
      'contact_id' => $this->contactID,
      'location_type_id' => 'Home',
      'street_address' => '789 Only St',
      'city' => 'Only City',
      'is_primary' => 1,
    ]);

    $selectedFields = [
      ['contact_type' => 'Individual', 'name' => 'street_address'],
      ['contact_type' => 'Individual', 'name' => 'city'],
    ];

    $this->doExportTest(['fields' => $selectedFields]);

    $row = $this->csv->nth(0);
    $this->assertEquals('789 Only St', $row['Street Address']);
    $this->assertEquals('Only City', $row['City']);
  }

  /**
   * Run the export and capture CSV output.
   *
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   */
  protected function doExportTest(array $params): void {
    $fields = $params['fields'] ?? [];
    $fieldDefaults = ['contact_type' => 'Individual', 'phone_type_id' => NULL, 'location_type_id' => NULL];
    foreach ($fields as $key => $field) {
      $fields[$key] = array_merge($fieldDefaults, $field);
    }
    $this->startCapturingOutput();
    try {
      $ids = $params['ids'] ?? [$this->contactID];
      $defaultClause = empty($ids) ? NULL : 'contact_a.id IN (' . implode(',', $ids) . ')';
      CRM_Export_BAO_Export::exportComponents(
        $params['selectAll'] ?? !$fields,
        $ids,
        $params['params'] ?? [],
        $params['order'] ?? NULL,
        $fields,
        NULL,
        CRM_Export_Form_Select::CONTACT_EXPORT,
        $params['componentClause'] ?? $defaultClause,
        NULL,
        FALSE,
        FALSE,
        []
      );
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->processor = $e->errorData['processor'];
      $this->csv = $this->captureOutputToCSV();
      return;
    }
    $this->fail('We expected a premature exit exception');
  }

  /**
   * Create an ExportProcessor with the given fields and params.
   *
   * @param array $selectedFields
   * @param array $params
   *
   * @return \CRM_Export_BAO_ExportProcessor
   */
  protected function createExportProcessor(array $selectedFields, array $params = []): CRM_Export_BAO_ExportProcessor {
    $fieldDefaults = ['contact_type' => 'Individual', 'phone_type_id' => NULL, 'location_type_id' => NULL];
    foreach ($selectedFields as $key => $field) {
      $selectedFields[$key] = array_merge($fieldDefaults, $field);
    }
    $processor = new CRM_Export_BAO_ExportProcessor(
      CRM_Export_Form_Select::CONTACT_EXPORT,
      $selectedFields,
      'AND',
      FALSE,
      FALSE,
      FALSE,
      []
    );
    $ids = [$this->contactID];
    $processor->setComponentClause('contact_a.id IN (' . implode(',', $ids) . ')');
    $processor->setIds($ids);
    return $processor;
  }

}
