<?php

use Civi\Api4\OptionValue;

/**
 * Class CRM_Core_DAOTest
 *
 * @group headless
 */
class CRM_Export_BAO_ExportTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;
  /**
   * Contact IDs created for testing.
   *
   * @var array
   */
  protected $contactIDs = [];

  /**
   * Contribution IDs created for testing.
   *
   * @var array
   */
  protected $contributionIDs = [];

  /**
   * Contribution IDs created for testing.
   *
   * @var array
   */
  protected $activityIDs = [];

  /**
   * Contribution IDs created for testing.
   *
   * @var array
   */
  protected $membershipIDs = [];

  /**
   * Master Address ID created for testing.
   *
   * @var int
   */
  protected $masterAddressID;

  protected $locationTypes = [];

  /**
   * Should location types be checked to ensure primary addresses are correctly assigned after each test.
   *
   * @var bool
   */
  protected $isLocationTypesOnPostAssert = TRUE;

  /**
   * Processor generated in test.
   *
   * @var \CRM_Export_BAO_ExportProcessor
   */
  protected $processor;

  /**
   * Csv output from export.
   *
   * @var \League\Csv\Reader
   */
  protected $csv;

  /**
   * Cleanup data.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup([
      'civicrm_contact',
      'civicrm_email',
      'civicrm_phone',
      'civicrm_im',
      'civicrm_website',
      'civicrm_address',
      'civicrm_relationship',
      'civicrm_membership',
      'civicrm_case',
      'civicrm_case_contact',
      'civicrm_case_activity',
      'civicrm_campaign',
    ]);
    OptionValue::update(FALSE)->addWhere('name', '=', 'Mobile')->addWhere('option_group_id:name', '=', 'phone_type')->setValues(['label' => 'Mobile'])->execute();

    if (!empty($this->locationTypes)) {
      $this->callAPISuccess('LocationType', 'delete', ['id' => $this->locationTypes['Whare Kai']['id']]);
      $this->callAPISuccess('LocationType', 'create', ['id' => $this->locationTypes['Main']['id'], 'name' => 'Main']);
    }
    if ($this->processor && $this->processor->getTemporaryTable()) {
      // delete the export temp table
      CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS ' . $this->processor->getTemporaryTable());
    }
    parent::tearDown();
  }

  /**
   * Basic test to ensure the exportComponents function completes without error.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\Exception
   */
  public function testExportComponentsNull(): void {
    $this->doExportTest([]);
  }

  /**
   * Basic test to ensure the exportComponents function can export selected fields for contribution.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\UnableToProcessCsv
   */
  public function testExportComponentsContribution(): void {
    $this->setUpContributionExportData();
    $selectedFields = [
      ['contact_type' => 'Individual', 'name' => 'first_name'],
      ['contact_type' => 'Individual', 'name' => 'last_name'],
      ['name' => 'receive_date'],
      ['name' => 'contribution_source'],
      ['contact_type' => 'Individual', 'name' => 'street_address', 1],
      ['contact_type' => 'Individual', 'name' => 'city', 1],
      ['contact_type' => 'Individual', 'name' => 'country', 1],
      ['contact_type' => 'Individual', 'name' => 'email', 1],
      ['name' => 'trxn_id'],
    ];
    $this->hookClass->setHook('civicrm_export', [$this, 'confirmHookWasCalled']);

    $this->doExportTest([
      'ids' => $this->contributionIDs,
      'order' => 'receive_date desc',
      'fields' => $selectedFields,
      'exportMode' => CRM_Export_Form_Select::CONTRIBUTE_EXPORT,
      'componentClause' => 'civicrm_contribution.id IN ( ' . implode(',', $this->contributionIDs) . ')',
    ]);
    $this->assertContains('display', array_values($this->csv->getHeader()));
    $row = $this->csv->fetchOne();
    $this->assertEquals('This is a test', $row['display']);
  }

  /**
   * Implements hook_civicrm_export().
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function confirmHookWasCalled($exportTempTable, &$headerRows, &$sqlColumns, $exportMode, $componentTable, $ids): void {
    $sqlColumns['display'] = 'display varchar(255)';
    $headerRows[] = 'display';
    CRM_Core_DAO::executeQuery("ALTER TABLE $exportTempTable ADD COLUMN display varchar(255)");
    CRM_Core_DAO::executeQuery("UPDATE $exportTempTable SET display = 'This is a test'");
  }

  /**
   * Basic test to ensure the exportComponents function can export with soft credits enabled.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\UnableToProcessCsv
   */
  public function testExportComponentsContributionSoftCredits(): void {
    $this->setUpContributionExportData();
    $this->callAPISuccess('ContributionSoft', 'create', ['contact_id' => $this->contactIDs[1], 'contribution_id' => $this->contributionIDs[0], 'amount' => 5]);
    $params = [
      ['receive_date_low', '=', '20160101000000', 0, 0],
      ['receive_date_high', '=', '20191231235959', 0, 0],
      ['contribution_amount_low', '=', '1', 0, 0],
      ['contribution_amount_high', '=', '10000000', 0, 0],
      ['contribution_test', '=', '0', 0, 0],
      ['contribution_or_softcredits', '=', 'both', 0, 0],
    ];

    $this->doExportTest([
      'selectAll' => FALSE,
      'ids' => $this->contributionIDs,
      'params' => $params,
      'order' => 'receive_date desc',
      'exportMode' => CRM_Export_Form_Select::CONTRIBUTE_EXPORT,
      'componentClause' => 'civicrm_contribution.id IN ( ' . implode(',', $this->contributionIDs) . ')',
    ]);

    $this->assertEquals(array_values(array_merge($this->getBasicHeaderDefinition(FALSE), self::getContributeHeaderDefinition())), $this->csv->getHeader());
    $this->assertCount(3, $this->csv);
    $row = $this->csv->fetchOne();
    $this->assertEquals(95, $row['Net Amount']);
    $this->assertEquals('', $row['Soft Credit Amount']);
    $row = $this->csv->fetchOne(1);
    $this->assertEquals(95, $row['Net Amount']);
    $this->assertEquals(5, $row['Soft Credit Amount']);
    $this->assertEquals('Anderson, Anthony II', $row['Soft Credit For']);
    $this->assertEquals($this->contributionIDs[0], $row['Soft Credit For Contribution ID']);

    // Ideally we would use a randomised temp table name & use generic temp cleanup for cleanup - but
    // for now just make sure we don't leave a mess.
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS contribution_search_scredit_combined');

  }

  /**
   * Basic test to ensure the exportComponents function can export selected fields for contribution.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\UnableToProcessCsv
   */
  public function testExportComponentsMembership(): void {
    $this->setUpMembershipExportData();
    $this->doExportTest([
      'selectAll' => TRUE,
      'ids'  => $this->membershipIDs,
      'exportMode' => CRM_Export_Form_Select::MEMBER_EXPORT,
      'componentClause' => 'civicrm_membership.id IN ( ' . $this->ids['membership'] . ')',
    ]);
    $membership = $this->callAPISuccessGetSingle('Membership', ['id' => $this->ids['membership']]);

    $row = $this->csv->fetchOne();
    $expected = [
      'Contact ID' => $membership['contact_id'],
      'Contact Type' => 'Individual',
      'Contact Subtype' => '',
      'Do Not Email' => '',
      'Do Not Phone' => '',
      'Do Not Mail' => '',
      'Do Not Sms' => '',
      'Do Not Trade' => '',
      'No Bulk Emails (User Opt Out)' => '',
      'Legal Identifier' => '',
      'External Identifier' => '',
      'Sort Name' => 'Anderson, Anthony II',
      'Display Name' => 'Mr. Anthony Anderson II',
      'Nickname' => '',
      'Legal Name' => '',
      'Image Url' => '',
      'Preferred Communication Method' => '',
      'Preferred Language' => 'en_US',
      'Contact Hash' => '059023a02d27d4e7f285a40ee0e30be8',
      'Contact Source' => '',
      'First Name' => 'Anthony',
      'Middle Name' => 'J.',
      'Last Name' => 'Anderson',
      'Individual Prefix' => 'Mr.',
      'Individual Suffix' => 'II',
      'Formal Title' => '',
      'Communication Style' => 'Formal',
      'Email Greeting ID' => '1',
      'Postal Greeting ID' => '1',
      'Addressee ID' => '1',
      'Job Title' => '',
      'Gender' => 'Female',
      'Birth Date' => '',
      'Deceased / Closed' => '',
      'Deceased / Closed Date' => '',
      'Household Name' => '',
      'Organization Name' => '',
      'Sic Code' => '',
      'Unique ID (OpenID)' => '',
      'Current Employer ID' => '',
      'Contact is in Trash' => '',
      'Created Date' => '2019-07-11 09:56:18',
      'Modified Date' => '2019-07-11 09:56:19',
      'Addressee' => 'Mr. Anthony J. Anderson II',
      'Email Greeting' => 'Dear Anthony',
      'Postal Greeting' => 'Dear Anthony',
      'Current Employer' => '',
      'Location Type' => 'Home',
      'Street Address' => 'Ambachtstraat 23',
      'Street Number' => '',
      'Street Number Suffix' => '',
      'Street Name' => '',
      'Street Unit' => '',
      'Supplemental Address 1' => '',
      'Supplemental Address 2' => '',
      'Supplemental Address 3' => '',
      'City' => 'Brummen',
      'Postal Code Suffix' => '',
      'Postal Code' => '6971 BN',
      'Latitude' => '',
      'Longitude' => '',
      'Address Name' => '',
      'Master Address ID' => '',
      'County' => '',
      'State' => '',
      'Country' => 'Netherlands',
      'Phone' => '',
      'Phone Extension' => '',
      'Phone Type ID' => '',
      'Phone Type' => '',
      'Email' => 'home@example.com',
      'On Hold' => 'No',
      'Use for Bulk Mail' => '',
      'Signature Text' => '',
      'Signature Html' => '',
      'IM Provider' => '',
      'IM Screen Name' => '',
      'OpenID' => '',
      'World Region' => 'Europe and Central Asia',
      'Website' => '',
      'Membership Type' => 'General',
      'Test' => '',
      'Is Pay Later' => '',
      'Member Since' => $membership['join_date'],
      'Membership Start Date' => $membership['start_date'],
      'Membership Expiration Date' => $membership['end_date'],
      'Membership Source' => 'Payment',
      'Membership Status' => 'Pending',
      'Membership ID' => '2',
      'Primary Member ID' => '',
      'Max Related' => '',
      'Recurring Contribution ID' => 1,
      'Campaign ID' => '',
      'Status Override' => '',
      'Total Amount' => '200.00',
      'Contribution Status' => 'Pending Label**',
      'Contribution Date' => '2019-07-25 07:34:23',
      'Payment Method' => 'Check',
      'Transaction ID' => '',
    ];
    $this->assertExpectedOutput($expected, $row);
  }

  /**
   * Basic test to ensure the exportComponents function can export selected fields for activity
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\UnableToProcessCsv
   */
  public function testExportComponentsActivity(): void {
    $this->setUpActivityExportData();
    $selectedFields = [
      ['contact_type' => 'Individual', 'name' => 'display_name'],
      ['contact_type' => 'Individual', 'relationship_type_id' => '5', 'relationship_direction' => 'a_b', 'name' => 'display_name'],
    ];

    $this->doExportTest([
      'ids' => $this->activityIDs,
      'order' => '`activity_date_time` desc',
      'fields' => $selectedFields,
      'exportMode' => CRM_Export_Form_Select::ACTIVITY_EXPORT,
      'componentClause' => 'civicrm_activity.id IN ( ' . implode(',', $this->activityIDs) . ')',
    ]);
    $row = $this->csv->fetchOne();
    $this->assertEquals($this->activityIDs[0], $row['Activity ID']);
  }

  /**
   * Test the function that extracts the arrays used to structure the output.
   *
   * The keys in the output fields array should by matched by field aliases in
   * the sql query (with exceptions of course - currently country is one -
   * although maybe a future refactor can change that!).
   *
   * We are trying to move towards simpler processing in the per row iteration
   * as that may be repeated 100,000 times and in general we should simply be
   * able to match the query fields to our expected rows & do a little
   * pseudoconstant mapping.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetExportStructureArrays(): void {
    // This is how return properties are formatted internally within the function for passing to the BAO query.
    $returnProperties = [
      'first_name' => 1,
      'last_name' => 1,
      'receive_date' => 1,
      'contribution_source' => 1,
      'location' => [
        'Home' => [
          'street_address' => 1,
          'city' => 1,
          'country' => 1,
          'email' => 1,
          'im-1' => 1,
          'im_provider' => 1,
          'phone-1' => 1,
        ],
      ],
      'phone' => 1,
      'trxn_id' => 1,
      'contribution_id' => 1,
    ];

    $query = new CRM_Contact_BAO_Query([], $returnProperties, NULL,
      FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CONTRIBUTE,
      FALSE, TRUE, TRUE, NULL, 'AND'
    );

    [$select] = $query->query();
    $pattern = '/as `?([^`,]*)/';
    $queryFieldAliases = [];
    preg_match_all($pattern, $select, $queryFieldAliases);
    $processor = new CRM_Export_BAO_ExportProcessor(CRM_Contact_BAO_Query::MODE_CONTRIBUTE, NULL, 'AND');
    $processor->setQueryFields($query->_fields);
    $processor->setReturnProperties($returnProperties);

    [$outputFields] = $processor->getExportStructureArrays();
    foreach (array_keys($outputFields) as $fieldAlias) {
      if ($fieldAlias === 'Home-country') {
        $this->assertContains($fieldAlias . '_id', $queryFieldAliases[1], 'Country is subject to some funky translate so we make sure country id is present');
      }
      else {
        $this->assertContains($fieldAlias, $queryFieldAliases[1], 'looking for field ' . $fieldAlias . ' in generaly the alias fields need to match the outputfields');
      }
    }

  }

  /**
   * Set up some data for us to do testing on.
   */
  public function setUpContributionExportData(): void {
    $this->setUpContactExportData();
    $this->contributionIDs[] = $this->contributionCreate(['contact_id' => $this->contactIDs[0], 'trxn_id' => 'null', 'invoice_id' => 'null', 'receive_date' => '2019-07-25 07:34:23']);
    $this->contributionIDs[] = $this->contributionCreate(['contact_id' => $this->contactIDs[1], 'trxn_id' => 'null', 'invoice_id' => 'null', 'receive_date' => '2018-12-01 00:00:00']);
  }

  /**
   * Set up some data for us to do testing on.
   */
  public function setUpMembershipExportData(): void {
    $this->setUpContactExportData();
    // Create an extra so we don't get false passes due to 1
    $this->contactMembershipCreate(['contact_id' => $this->contactIDs[0]]);
    $this->paymentProcessorCreate();
    $this->setupMembershipRecurringPaymentProcessorTransaction();

    $membershipID = $this->callAPISuccessGetValue('Membership', ['return' => 'id', 'contact_id' => $this->ids['Contact']['individual_0'], 'options' => ['limit' => 1, 'sort' => 'id DESC']]);

    $this->membershipIDs[] = $membershipID;
  }

  /**
   * Set up data to test case export.
   */
  public function setupCaseExportData(): void {
    $contactID1 = $this->individualCreate();
    $contactID2 = $this->individualCreate([], 1);

    $case = $this->callAPISuccess('case', 'create', [
      'case_type_id' => 1,
      'subject' => 'blah',
      'contact_id' => $contactID1,
    ]);
    $this->callAPISuccess('CaseContact', 'create', [
      'case_id' => $case['id'],
      'contact_id' => $contactID2,
    ]);
  }

  /**
   * Set up some data for us to do testing on.
   *
   * @throws \CRM_Core_Exception
   */
  public function setUpActivityExportData(): void {
    $this->setUpContactExportData();
    $this->activityIDs[] = $this->activityCreate(['contact_id' => $this->contactIDs[0]])['id'];
  }

  /**
   * Set up some data for us to do testing on.
   */
  public function setUpContactExportData(): void {
    $this->contactIDs[] = $contactA = $this->individualCreate(['gender_id' => 'Female']);
    // Create address for contact A.
    $params = [
      'contact_id' => $contactA,
      'location_type_id' => 'Home',
      'street_address' => 'Ambachtstraat 23',
      'postal_code' => '6971 BN',
      'country_id' => '1152',
      'city' => 'Brummen',
      'is_primary' => 1,
    ];
    $result = $this->callAPISuccess('address', 'create', $params);
    $addressId = $result['id'];

    $this->callAPISuccess('email', 'create', [
      'id' => $this->callAPISuccessGetValue('Email', ['contact_id' => $params['contact_id'], 'return' => 'id']),
      'location_type_id' => 'Home',
      'email' => 'home@example.com',
      'is_primary' => 1,
    ]);
    $this->callAPISuccess('email', 'create', ['contact_id' => $params['contact_id'], 'location_type_id' => 'Work', 'email' => 'work@example.com', 'is_primary' => 0]);

    $params['is_primary'] = 0;
    $params['location_type_id'] = 'Work';
    $this->callAPISuccess('address', 'create', $params);
    $this->contactIDs[] = $contactB = $this->individualCreate([], 1);

    $this->callAPISuccess('address', 'create', [
      'contact_id' => $contactB,
      'location_type_id' => 'Home',
      'master_id' => $addressId,
    ]);
    $this->masterAddressID = $addressId;

  }

  /**
   * Test variants of primary address exporting.
   *
   * @param int $isPrimaryOnly
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\Exception
   * @dataProvider getBooleanDataProvider
   */
  public function testExportPrimaryAddress($isPrimaryOnly): void {
    Civi::settings()->set('searchPrimaryDetailsOnly', $isPrimaryOnly);
    $this->setUpContactExportData();

    $selectedFields = [
      ['contact_type' => 'Individual', 'name' => 'email'],
      ['contact_type' => 'Individual', 'name' => 'email', 'location_type_id' => '1'],
      ['contact_type' => 'Individual', 'name' => 'email', 'location_type_id' => '2'],
    ];
    $this->doExportTest([
      'ids' => [],
      'params' => [['email', 'LIKE', 'c', 0, 1]],
      'fields' => $selectedFields,
      'componentClause' => "contact_a.id IN ({$this->contactIDs[0]}, {$this->contactIDs[1]})",
      'selectAll' => TRUE,
    ]);

    $row = $this->csv->fetchOne();
    $this->assertEquals([
      'Email' => 'home@example.com',
      'Home-Email' => 'home@example.com',
      'Work-Email' => 'work@example.com',
    ], $row);
    $this->assertCount(2, $this->csv);
    Civi::settings()->set('searchPrimaryDetailsOnly', FALSE);
  }

  /**
   * Test that when exporting a pseudoField it is reset for NULL entries.
   *
   * ie. we have a contact WITH a gender & one without - make sure the latter one
   * does NOT retain the gender of the former.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\Exception
   */
  public function testExportPseudoField(): void {
    $this->setUpContactExportData();
    $this->callAPISuccess('OptionValue', 'create', ['option_group_id' => 'gender', 'name' => 'Really long string', 'value' => 678, 'label' => 'Really long string']);
    $selectedFields = [['contact_type' => 'Individual', 'name' => 'gender_id']];
    $this->callAPISuccess('Contact', 'create', ['id' => $this->contactIDs[0], 'gender_id' => 678]);
    $this->doExportTest(['fields' => $selectedFields, 'ids' => $this->contactIDs]);
    $row = $this->csv->fetchOne();
    $this->assertEquals('Really long string', $row['Gender']);
  }

  /**
   * Test that when exporting a pseudoField it is reset for NULL entries.
   *
   * This is specific to the example in CRM-14398
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\UnableToProcessCsv
   */
  public function testExportPseudoFieldCampaign(): void {
    $this->setUpContributionExportData();
    $campaign = $this->callAPISuccess('Campaign', 'create', ['title' => 'Big campaign and kinda long too']);
    $this->callAPISuccess('Contribution', 'create', ['campaign_id' => $campaign['id'], 'id' => $this->contributionIDs[0]]);
    $selectedFields = [
      ['contact_type' => 'Individual', 'name' => 'gender_id'],
      ['contact_type' => 'Contribution', 'name' => 'contribution_campaign_title'],
      ['contact_type' => 'Contribution', 'name' => 'contribution_campaign_id'],
    ];
    $this->doExportTest([
      'ids' => [$this->contactIDs[1]],
      'exportMode' => CRM_Export_Form_Select::CONTRIBUTE_EXPORT,
      'fields' => $selectedFields,
      'componentClause' => 'contact_a.id IN (' . implode(',', $this->contactIDs) . ')',
    ]);
    $row = $this->csv->fetchOne();
    $this->assertEquals('Big campaign and kinda long too', $row['Campaign Title']);
    $this->assertEquals($campaign['id'], $row['Campaign ID']);
  }

  /**
   * Test exporting relationships.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\UnableToProcessCsv
   */
  public function testExportRelationships(): void {
    $organization1 = $this->organizationCreate(['organization_name' => 'Org 1', 'legal_name' => 'pretty legal', 'contact_source' => 'friend who took a law paper once']);
    $organization2 = $this->organizationCreate(['organization_name' => 'Org 2', 'legal_name' => 'well dodgey']);
    $contact1 = $this->individualCreate(['employer_id' => $organization1, 'first_name' => 'one']);
    $contact2 = $this->individualCreate(['employer_id' => $organization2, 'first_name' => 'one']);
    $employerRelationshipTypeID = $this->callAPISuccessGetValue('RelationshipType', ['return' => 'id', 'label_a_b' => 'Employee of']);
    $selectedFields = [
      ['contact_type' => 'Individual', 'name' => 'first_name'],
      ['contact_type' => 'Individual', 'relationship_type_id' => $employerRelationshipTypeID, 'relationship_direction' => 'a_b', 'name' => 'organization_name'],
      ['contact_type' => 'Individual', 'relationship_type_id' => $employerRelationshipTypeID, 'relationship_direction' => 'a_b', 'name' => 'legal_name'],
      ['contact_type' => 'Individual', 'relationship_type_id' => $employerRelationshipTypeID, 'relationship_direction' => 'a_b', 'name' => 'contact_source'],
    ];
    $this->doExportTest([
      'ids' => [$contact1, $contact2],
      'componentClause' => "contact_a.id IN ( $contact1, $contact2 )",
      'fields' => $selectedFields,
    ]);

    $row = $this->csv->fetchOne();
    $this->assertEquals('one', $row['First Name']);
    $this->assertEquals('Org 1', $row['Employee of-Organization Name']);
    $this->assertEquals('pretty legal', $row['Employee of-Legal Name']);
    $this->assertEquals('friend who took a law paper once', $row['Employee of-Contact Source']);

    $row = $this->csv->fetchOne(1);
    $this->assertEquals('Org 2', $row['Employee of-Organization Name']);
    $this->assertEquals('well dodgey', $row['Employee of-Legal Name']);
  }

  /**
   * Test exporting relationships.
   *
   * This is to ensure that CRM-13995 remains fixed.
   *
   * @dataProvider getBooleanDataProvider
   *
   * @param bool $includeHouseHold
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\UnableToProcessCsv
   */
  public function testExportRelationshipsMergeToHousehold(bool $includeHouseHold): void {
    [$householdID, $houseHoldTypeID] = $this->setUpHousehold();

    if ($includeHouseHold) {
      $this->contactIDs[] = $householdID;
    }
    $selectedFields = [
      ['contact_type' => 'Individual', 'relationship_type_id' => $houseHoldTypeID, 'relationship_direction' => 'a_b', 'name' => 'state_province', 'location_type_id' => ''],
      ['contact_type' => 'Individual', 'relationship_type_id' => $houseHoldTypeID, 'relationship_direction' => 'a_b', 'name' => 'city', 'location_type_id' => ''],
      ['contact_type' => 'Individual', 'name' => 'city', 'location_type_id' => ''],
      ['contact_type' => 'Individual', 'name' => 'state_province', 'location_type_id' => ''],
      ['contact_type' => 'Individual', 'name' => 'contact_source', 'location_type_id' => ''],
    ];
    $this->doExportTest([
      'ids' => $this->contactIDs,
      'fields' => $selectedFields,
      'mergeSameHousehold' => TRUE,
    ]);
    $row = $this->csv->fetchOne();
    $this->assertCount(1, $this->csv);
    $this->assertEquals('Portland', $row['City']);
    $this->assertEquals('ME', $row['State']);
    $this->assertEquals($householdID, $row['Household ID']);
    $this->assertEquals('household sauce', $row['Contact Source']);
  }

  /**
   * Test exporting contacts merged into households
   *
   * This is to ensure that dev/core#2272 remains fixed.
   *
   * @dataProvider getBooleanDataProvider
   *
   * @param bool $includeHouseHold
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\UnableToProcessCsv
   */
  public function testExportMergeToHousehold(bool $includeHouseHold): void {
    [$householdID] = $this->setUpHousehold();

    if ($includeHouseHold) {
      $this->contactIDs[] = $householdID;
    }
    $selectedFields = [
      ['contact_type' => 'Individual', 'name' => 'contact_source', 'location_type_id' => ''],
    ];
    $this->doExportTest([
      'ids' => $this->contactIDs,
      'fields' => $selectedFields,
      'mergeSameHousehold' => TRUE,
      'componentTable' => 'civicrm_contact',
      'componentClause' => 'contact_a.id IN (' . implode(',', $this->contactIDs) . ')',
    ]);
    $row = $this->csv->fetchOne();
    $this->assertCount(1, $this->csv);
    $this->assertEquals($householdID, $row['Household ID']);

  }

  /**
   * Test exporting relationships.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\UnableToProcessCsv
   */
  public function testExportRelationshipsMergeToHouseholdAllFields(): void {
    [$householdID] = $this->setUpHousehold();
    $this->doExportTest(['ids' => $this->contactIDs, 'mergeSameHousehold' => TRUE]);
    $row = $this->csv->fetchOne();
    $this->assertCount(1, $this->csv);
    $this->assertEquals('Unit Test household', $row['Display Name']);
    $this->assertEquals('Portland', $row['City']);
    $this->assertEquals('ME', $row['State']);
    $this->assertEquals($householdID, $row['Household ID']);
    $this->assertEquals('Unit Test household', $row['Addressee']);
    $this->assertEquals('Dear Unit Test household', $row['Postal Greeting']);
  }

  /**
   * Test custom data exporting.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\UnableToProcessCsv
   */
  public function testExportCustomData(): void {
    $this->setUpContactExportData();
    $this->createCustomGroupWithFieldsOfAllTypes();
    $longString = 'Blah';
    for ($i = 0; $i < 70; $i++) {
      $longString .= 'Blah';
    }
    $this->addOptionToCustomField('select_string', ['label' => $longString, 'name' => 'blah']);
    $longUrl = 'https://stage.example.org/system/files/webform/way_too_long_url_that_still_fits_in_a_link_custom_field_but_would_fail_to_export_with_html.jpg';

    $this->callAPISuccess('Contact', 'create', [
      'id' => $this->contactIDs[1],
      $this->getCustomFieldName('text') => $longString,
      $this->getCustomFieldName('country') => 'LA',
      $this->getCustomFieldName('select_string') => 'blah',
      'api.Address.create' => ['location_type_id' => 'Billing', 'city' => 'Waipu'],
      $this->getCustomFieldName('link') => $longUrl,
    ]);
    $selectedFields = [
      ['name' => 'city', 'location_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Address', 'location_type_id', 'Billing')],
      ['name' => $this->getCustomFieldName('text')],
      ['name' => $this->getCustomFieldName('country')],
      ['name' => $this->getCustomFieldName('select_string')],
      ['name' => $this->getCustomFieldName('link')],
    ];

    $this->doExportTest([
      'fields' => $selectedFields,
      'ids' => [$this->contactIDs[1]],
    ]);
    $row = $this->csv->fetchOne();
    $this->assertEquals($longString, $row['Enter text here']);
    $this->assertEquals('Waipu', $row['Billing-City']);
    $this->assertEquals("Lao People's Democratic Republic", $row['Country']);
    $this->assertEquals($longString, $row['Pick Color']);
    $this->assertEquals($longUrl, $row['test_link']);
  }

  /**
   * Attempt to do a fairly full export of location data.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\Exception
   */
  public function testExportIMData(): void {
    // Use default providers.
    $providers = ['AIM' => 'Aim', 'GTalk' => 'Gtalk', 'Jabber' => 'Jabber', 'MSN' => 'Msn', 'Skype' => 'Skype', 'Yahoo' => 'Yahoo'];
    // Main sure labels are not all anglo chars.
    $this->diversifyLocationTypes();

    $locationTypes = ['Billing' => 'Billing', 'Home' => 'Home', 'Main' => 'Méin', 'Other' => 'Other', 'Whare Kai' => 'Whare Kai'];

    $this->contactIDs[] = $this->individualCreate();
    $this->contactIDs[] = $this->individualCreate();
    $this->contactIDs[] = $this->householdCreate();
    $this->contactIDs[] = $this->organizationCreate();
    foreach ($this->contactIDs as $contactID) {
      foreach ($providers as $provider) {
        foreach ($locationTypes as $locationName => $locationLabel) {
          $this->callAPISuccess('IM', 'create', [
            'contact_id' => $contactID,
            'location_type_id' => $locationName,
            'provider_id' => $provider,
            'name' => $locationName . $provider . $contactID,
          ]);
        }
      }
    }

    $relationships = [
      $this->contactIDs[1] => ['label' => 'Spouse of'],
      $this->contactIDs[2] => ['label' => 'Household Member of'],
      $this->contactIDs[3] => ['label' => 'Employee of'],
    ];

    foreach ($relationships as $contactID => $relationshipType) {
      $relationshipTypeID = $this->callAPISuccess('RelationshipType', 'getvalue', ['label_a_b' => $relationshipType['label'], 'return' => 'id']);
      $result = $this->callAPISuccess('Relationship', 'create', [
        'contact_id_a' => $this->contactIDs[0],
        'relationship_type_id' => $relationshipTypeID,
        'contact_id_b' => $contactID,
      ]);
      $relationships[$contactID]['id'] = $result['id'];
      $relationships[$contactID]['relationship_type_id'] = $relationshipTypeID;
    }

    $fields = [['name' => 'contact_id']];
    // ' ' denotes primary location type.
    foreach (array_keys(array_merge($locationTypes, [' ' => ['Primary']])) as $locationType) {
      $locationTypeID = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_IM', 'location_type_id', $locationType);
      $fields[] = ['name' => 'im_provider', 'location_type_id' => $locationTypeID];
      foreach ($relationships as $relationship) {
        $fields[] = ['name' => 'im_provider', 'relationship_type_id' => $relationship['relationship_type_id'], 'relationship_direction' => 'a_b', 'location_type_id' => $locationTypeID];
      }
      foreach ($providers as $provider) {
        $providerID = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_IM', 'provider_id', $provider);
        $fields[] = ['name' => 'im', 'location_type_id' => $locationTypeID, 'im_provider_id' => $providerID];
        foreach ($relationships as $relationship) {
          $fields[] = ['name' => 'im', 'location_type_id' => $locationTypeID, 'im_provider_id' => $providerID, 'relationship_type_id' => $relationship['relationship_type_id'], 'relationship_direction' => 'a_b'];
        }
      }
    }

    $this->doExportTest(['fields' => $fields, 'ids' => [$this->contactIDs[0]]]);

    foreach ($this->csv->getRecords() as $row) {
      $id = $row['Contact ID'];
      // The provider could be any of them as we created multiple ims for each location
      // type. In earlier mysql versions it gets a somewhat consistent result but there
      // is no 'right' provider so we just check it is a resolved pseudoconstant.
      $this->assertContains($row['Billing-IM Provider'], array_keys($providers));
      $this->assertContains($row['Whare Kai-IM Provider'], array_keys($providers));
      $this->assertEquals('BillingJabber' . $id, $row['Billing-IM Screen Name-Jabber']);
      $this->assertEquals('Whare KaiJabber' . $id, $row['Whare Kai-IM Screen Name-Jabber']);
      $this->assertEquals('BillingSkype' . $id, $row['Billing-IM Screen Name-Skype']);
      foreach ($relationships as $relatedContactID => $relationship) {
        $this->assertEquals('BillingYahoo' . $relatedContactID, $row[$relationship['label'] . '-Billing-IM Screen Name-Yahoo']);
        $this->assertEquals('Whare KaiJabber' . $relatedContactID, $row[$relationship['label'] . '-Whare Kai-IM Screen Name-Jabber']);
      }
    }
  }

  /**
   * Test phone data export.
   *
   * Less over the top complete than the im test.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   * @throws \League\Csv\Exception
   */
  public function testExportPhoneData(): void {
    $this->contactIDs[] = $this->individualCreate();
    $this->contactIDs[] = $this->individualCreate();

    OptionValue::update()->addWhere('name', '=', 'Mobile')->addWhere('option_group_id:name', '=', 'phone_type')->setValues(['label' => 'Much Much longer than just phone'])->execute();
    $locationTypes = ['Billing' => 'Billing', 'Home' => 'Home'];
    $phoneTypes = ['Mobile', 'Phone'];
    foreach ($this->contactIDs as $contactID) {
      $this->callAPISuccess('Phone', 'create', [
        'contact_id' => $contactID,
        'location_type_id' => 'Billing',
        'phone_type_id' => 'Mobile',
        'phone' => 'Billing' . 'Mobile' . $contactID,
        'is_primary' => 1,
      ]);
      $this->callAPISuccess('Phone', 'create', [
        'contact_id' => $contactID,
        'location_type_id' => 'Home',
        'phone_type_id' => 'Phone',
        'phone' => 'Home' . 'Phone' . $contactID,
      ]);
    }

    $relationships = [
      $this->contactIDs[1] => ['label' => 'Spouse of'],
    ];

    foreach ($relationships as $contactID => $relationshipType) {
      $relationshipTypeID = $this->callAPISuccess('RelationshipType', 'getvalue', ['label_a_b' => $relationshipType['label'], 'return' => 'id']);
      $result = $this->callAPISuccess('Relationship', 'create', [
        'contact_id_a' => $this->contactIDs[0],
        'relationship_type_id' => $relationshipTypeID,
        'contact_id_b' => $contactID,
      ]);
      $relationships[$contactID]['id'] = $result['id'];
      $relationships[$contactID]['relationship_type_id'] = $relationshipTypeID;
    }

    $fields = [];
    // ' ' denotes primary location type.
    foreach (array_keys(array_merge($locationTypes, [' ' => ['Primary']])) as $locationType) {
      $locationTypeID = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Phone', 'location_type_id', $locationType);
      $fields[] = ['name' => 'phone', 'location_type_id' => $locationTypeID];
      $fields[] = ['name' => 'phone_type', 'location_type_id' => $locationTypeID];
      $fields[] = ['name' => 'phone_type_id', 'location_type_id' => $locationTypeID];
      foreach ($relationships as $relationship) {
        $fields[] = ['name' => 'phone_type_id', 'relationship_type_id' => $relationship['relationship_type_id'], 'relationship_direction' => 'a_b', 'location_type_id' => $locationTypeID];
        $fields[] = ['name' => 'phone_type', 'relationship_type_id' => $relationship['relationship_type_id'], 'relationship_direction' => 'a_b', 'location_type_id' => $locationTypeID];
      }
      foreach ($phoneTypes as $phoneType) {
        $phoneTypeID = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Phone', 'phone_type_id', $phoneType);
        $fields[] = ['name' => 'phone', 'phone_type_id' => $phoneTypeID, 'location_type_id' => $locationTypeID];
        foreach ($relationships as $relationship) {
          $fields[] = ['name' => 'phone_type_id', 'phone_type_id' => $phoneTypeID, 'relationship_type_id' => $relationship['relationship_type_id'], 'relationship_direction' => 'a_b', 'location_type_id' => $locationTypeID];
          $fields[] = ['name' => 'phone_type', 'phone_type_id' => $phoneTypeID, 'relationship_type_id' => $relationship['relationship_type_id'], 'relationship_direction' => 'a_b', 'location_type_id' => $locationTypeID];
        }
      }
    }

    $this->doExportTest(['fields' => $fields, 'ids' => [$this->contactIDs[0]]]);
    foreach ($this->csv->getRecords() as $row) {
      $this->assertEquals('BillingMobile3', $row['Billing-Phone-Much Much longer than just phone']);
      $this->assertEquals('', $row['Billing-Phone-Phone']);
      $this->assertEquals('Much Much longer than just phone', $row['Spouse of-Phone Type']);
      $this->assertEquals('Much Much longer than just phone', $row['Phone Type']);
      $this->assertEquals('Much Much longer than just phone', $row['Billing-Phone Type']);
    }
  }

  /**
   * Export City against multiple location types.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\Exception
   */
  public function testExportAddressData(): void {
    $this->diversifyLocationTypes();

    $locationTypes = ['Billing' => 'Billing', 'Home' => 'Home', 'Main' => 'Méin', 'Other' => 'Other', 'Whare Kai' => 'Whare Kai'];

    $this->contactIDs[] = $this->individualCreate();
    $this->contactIDs[] = $this->individualCreate();
    $this->contactIDs[] = $this->householdCreate();
    $this->contactIDs[] = $this->organizationCreate();
    $fields = [['name' => 'contact_id']];
    foreach ($this->contactIDs as $contactID) {
      foreach ($locationTypes as $locationName => $locationLabel) {
        $this->callAPISuccess('Address', 'create', [
          'contact_id' => $contactID,
          'location_type_id' => $locationName,
          'street_address' => $locationLabel . $contactID . 'street_address',
          'city' => $locationLabel . $contactID . 'city',
          'postal_code' => $locationLabel . $contactID . 'postal_code',
        ]);
        $fields[] = ['contact_type' => 'Individual', 'name' => 'city', 'location_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Address', 'location_type_id', $locationName)];
        $fields[] = ['contact_type' => 'Individual', 'name' => 'street_address', 'location_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Address', 'location_type_id', $locationName)];
        $fields[] = ['contact_type' => 'Individual', 'name' => 'postal_code', 'location_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Address', 'location_type_id', $locationName)];
      }
    }

    $relationships = [
      $this->contactIDs[1] => ['label' => 'Spouse of'],
      $this->contactIDs[2] => ['label' => 'Household Member of'],
      $this->contactIDs[3] => ['label' => 'Employee of'],
    ];

    foreach ($relationships as $contactID => $relationshipType) {
      $relationshipTypeID = $this->callAPISuccess('RelationshipType', 'getvalue', ['label_a_b' => $relationshipType['label'], 'return' => 'id']);
      $result = $this->callAPISuccess('Relationship', 'create', [
        'contact_id_a' => $this->contactIDs[0],
        'relationship_type_id' => $relationshipTypeID,
        'contact_id_b' => $contactID,
      ]);
      $relationships[$contactID]['id'] = $result['id'];
      $relationships[$contactID]['relationship_type_id'] = $relationshipTypeID;
    }

    // ' ' denotes primary location type.
    foreach (array_keys(array_merge($locationTypes, [' ' => ['Primary']])) as $locationType) {
      foreach ($relationships as $relationship) {
        $fields[] = [
          'contact_type' => 'Individual',
          'relationship_type_id' => $relationship['relationship_type_id'],
          'relationship_direction' => 'a_b',
          'name' => 'city',
          'location_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_IM', 'location_type_id', $locationType),
        ];
      }
    }

    $this->doExportTest(['fields' => $fields]);

    foreach ($this->csv as $row) {
      $contactID = (int) $row['Contact ID'];
      $this->assertEquals('Méin' . $contactID . 'city', $row['Main-City']);
      $this->assertEquals('Billing' . $contactID . 'street_address', $row['Billing-Street Address']);
      $this->assertEquals('Whare Kai' . $contactID . 'postal_code', $row['Whare Kai-Postal Code']);
      foreach ($relationships as $relatedContactID => $relationship) {
        $value = ($contactID === $this->contactIDs[0]) ? 'Méin' . $relatedContactID . 'city' : '';
        $this->assertEquals($value, $row[$relationship['label'] . '-Main-City'], 'checking ' . $relationship['label'] . '-Main-City');
      }
    }

    $this->assertEquals([
      'contact_id' => '`contact_id` varchar(64)',
      'billing_city' => '`billing_city` varchar(64)',
      'billing_street_address' => '`billing_street_address` varchar(96)',
      'billing_postal_code' => '`billing_postal_code` varchar(64)',
      'home_city' => '`home_city` varchar(64)',
      'home_street_address' => '`home_street_address` varchar(96)',
      'home_postal_code' => '`home_postal_code` varchar(64)',
      'main_city' => '`main_city` varchar(64)',
      'main_street_address' => '`main_street_address` varchar(96)',
      'main_postal_code' => '`main_postal_code` varchar(64)',
      'other_city' => '`other_city` varchar(64)',
      'other_street_address' => '`other_street_address` varchar(96)',
      'other_postal_code' => '`other_postal_code` varchar(64)',
      'whare_kai_city' => '`whare_kai_city` varchar(64)',
      'whare_kai_street_address' => '`whare_kai_street_address` varchar(96)',
      'whare_kai_postal_code' => '`whare_kai_postal_code` varchar(64)',
      '2_a_b_billing_city' => '`2_a_b_billing_city` varchar(64)',
      '2_a_b_home_city' => '`2_a_b_home_city` varchar(64)',
      '2_a_b_main_city' => '`2_a_b_main_city` varchar(64)',
      '2_a_b_other_city' => '`2_a_b_other_city` varchar(64)',
      '2_a_b_whare_kai_city' => '`2_a_b_whare_kai_city` varchar(64)',
      '2_a_b_city' => '`2_a_b_city` varchar(64)',
      '8_a_b_billing_city' => '`8_a_b_billing_city` varchar(64)',
      '8_a_b_home_city' => '`8_a_b_home_city` varchar(64)',
      '8_a_b_main_city' => '`8_a_b_main_city` varchar(64)',
      '8_a_b_other_city' => '`8_a_b_other_city` varchar(64)',
      '8_a_b_whare_kai_city' => '`8_a_b_whare_kai_city` varchar(64)',
      '8_a_b_city' => '`8_a_b_city` varchar(64)',
      '5_a_b_billing_city' => '`5_a_b_billing_city` varchar(64)',
      '5_a_b_home_city' => '`5_a_b_home_city` varchar(64)',
      '5_a_b_main_city' => '`5_a_b_main_city` varchar(64)',
      '5_a_b_other_city' => '`5_a_b_other_city` varchar(64)',
      '5_a_b_whare_kai_city' => '`5_a_b_whare_kai_city` varchar(64)',
      '5_a_b_city' => '`5_a_b_city` varchar(64)',
    ], $this->processor->getSQLColumns());
  }

  /**
   * Test master_address_id field when no merge is in play.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\Exception
   */
  public function testExportMasterAddress(): void {
    $this->setUpContactExportData();

    //export the master address for contact B
    $selectedFields = [
      ['contact_type' => 'Individual', 'name' => 'master_id', 'location_type_id' => 1],
    ];
    $this->doExportTest([
      'fields' => $selectedFields,
      'ids' => [$this->contactIDs[1]],
    ]);
    $row = $this->csv->fetchOne();
    $this->assertEquals(CRM_Contact_BAO_Contact::getMasterDisplayName($this->masterAddressID), $row['Home-Master Address ID']);
  }

  /**
   * Test merging same address when specifying fields.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\Exception
   */
  public function testMergeSameAddressSpecifyFields(): void {
    $this->setUpContactSameAddressExportData();
    $this->doExportTest(['mergeSameAddress' => TRUE, 'fields' => [['contact_type' => 'Individual', 'name' => 'master_id', 'location_type_id' => 1]]]);
  }

  /**
   * Test the merge same address option.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\Exception
   */
  public function testMergeSameAddress(): void {
    $this->setUpContactSameAddressExportData();
    $this->doExportTest(['mergeSameAddress' => TRUE]);
    // ie 2 merged, one extra.
    $this->assertCount(2, $this->csv);
    $expected = [
      'Contact ID' => $this->contactIDs[0],
      'Contact Type' => 'Individual',
      'Contact Subtype' => '',
      'Do Not Email' => '',
      'Do Not Phone' => '',
      'Do Not Mail' => '',
      'Do Not Sms' => '',
      'Do Not Trade' => '',
      'No Bulk Emails (User Opt Out)' => '',
      'Legal Identifier' => '',
      'External Identifier' => '',
      'Sort Name' => 'Anderson, Anthony II',
      'Display Name' => 'Mr. Anthony Anderson II',
      'Nickname' => '',
      'Legal Name' => '',
      'Image Url' => '',
      'Preferred Communication Method' => '',
      'Preferred Language' => 'en_US',
      'Contact Hash' => 'e9bd0913cc05cc5aeae69ba04ee3be84',
      'Contact Source' => '',
      'First Name' => 'Anthony',
      'Middle Name' => 'J.',
      'Last Name' => 'Anderson',
      'Individual Prefix' => 'Mr.',
      'Individual Suffix' => 'II',
      'Formal Title' => '',
      'Communication Style' => 'Formal',
      'Email Greeting ID' => '1',
      'Postal Greeting ID' => '1',
      'Addressee ID' => '1',
      'Job Title' => '',
      'Gender' => 'Female',
      'Birth Date' => '',
      'Deceased / Closed' => '',
      'Deceased / Closed Date' => '',
      'Household Name' => '',
      'Organization Name' => '',
      'Sic Code' => '',
      'Unique ID (OpenID)' => '',
      'Current Employer ID' => '',
      'Contact is in Trash' => '',
      'Created Date' => '2019-07-11 10:28:15',
      'Modified Date' => '2019-07-11 10:28:15',
      'Addressee' => 'Mr. Anthony J. Anderson II, Mr. Joe M. Miller II',
      'Email Greeting' => 'Dear Anthony, Joe',
      'Postal Greeting' => 'Dear Anthony, Joe',
      'Current Employer' => '',
      'Location Type' => 'Home',
      'Street Address' => 'Ambachtstraat 23',
      'Street Number' => '',
      'Street Number Suffix' => '',
      'Street Name' => '',
      'Street Unit' => '',
      'Supplemental Address 1' => '',
      'Supplemental Address 2' => '',
      'Supplemental Address 3' => '',
      'City' => 'Brummen',
      'Postal Code Suffix' => '',
      'Postal Code' => '6971 BN',
      'Latitude' => '',
      'Longitude' => '',
      'Address Name' => '',
      'Master Address ID' => '',
      'County' => '',
      'State' => '',
      'Country' => 'Netherlands',
      'Phone' => '',
      'Phone Extension' => '',
      'Phone Type ID' => '',
      'Phone Type' => '',
      'Email' => 'home@example.com',
      'On Hold' => 'No',
      'Use for Bulk Mail' => '',
      'Signature Text' => '',
      'Signature Html' => '',
      'IM Provider' => '',
      'IM Screen Name' => '',
      'OpenID' => '',
      'World Region' => 'Europe and Central Asia',
      'Website' => '',
      'Group(s)' => '',
      'Tag(s)' => '',
      'Note(s)' => '',
    ];
    // Include both possible options as we rely on implicit order here and MySQL 8 in testing is returning a different value for some fields.
    $this->assertExpectedOutput($expected, $this->csv->fetchOne(), [
      'Email' => ['home@example.com', 'work@example.com'],
      'Location Type' => ['Home', 'Work'],
    ]);
  }

  /**
   * Tests the options for greeting templates when choosing to merge same address.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\Exception
   */
  public function testMergeSameAddressGreetingOptions(): void {
    $this->setUpContactSameAddressExportData();
    $this->callAPISuccess('OptionValue', 'create', [
      'option_group_id' => 'postal_greeting',
      'label' => '{contact.individual_suffix} {contact.last_name} and first is {contact.first_name} ',
      // This hard coded number makes it available for export use.
      'filter' => 4,
    ]);
    $this->doExportTest([
      'mergeSameAddress' => TRUE,
      'exportParams' => [
        'postal_greeting' => '2',
        'postal_greeting_other' => '',
        'addressee' => '2',
        'addressee_other' => 'random string {contact.display_name}',
        'mergeOption' => '1',
        'mapping' => '',
      ],
    ]);
    $this->assertExpectedOutput([
      'Addressee' => 'random string Mr. Anthony Anderson II, Mr. Joe Miller II',
      'Email Greeting' => 'II Anderson and first is Anthony , II Miller Joe ',
      'Postal Greeting' => 'II Anderson and first is Anthony , II Miller Joe ',
    ], $this->csv->fetchOne());
    // 3 contacts merged to 2.
    $this->assertCount(2, $this->csv);
  }

  /**
   * Test exporting when no rows are retrieved.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\Exception
   */
  public function testExportNoRows(): void {
    $contactA = $this->callAPISuccess('contact', 'create', [
      'first_name' => 'John',
      'last_name' => 'Doe',
      'contact_type' => 'Individual',
    ]);
    $this->doExportTest([
      'selectAll' => TRUE,
      'ids' => [$contactA['id']],
      'exportParams' => [
        'postal_mailing_export' => [
          'postal_mailing_export' => TRUE,
        ],
        'mergeSameAddress' => TRUE,
      ],
    ]);
    $this->assertEquals('Contact ID', $this->csv->getHeader()[0]);
  }

  /**
   * Test to ensure that 'Merge All Contacts with the Same Address' works on
   * export.
   *
   * 3 contacts are created A, B and C where A and B are individual contacts
   * that share same address via master_id. C is a household contact whose
   * member is contact A.
   *
   * These 3 contacts are selected for export with 'Merge All Contacts with the
   * Same Address' = TRUE And at the end export table contain only 1 contact
   * i.e. is C as A and B got merged into 1 as they share same address but then
   * A is Household member of C. So C take preference over A and thus C is
   * exported as result.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\Exception
   * @throws \League\Csv\UnableToProcessCsv
   */
  public function testMergeSameAddressOnExport(): void {
    $this->individualCreate();
    $householdID = $this->setUpHousehold()[0];
    $contactIDs = array_merge($this->contactIDs, [$householdID]);
    // Empty existing addresses & start fresh.
    $this->callAPISuccess('Address', 'get', ['api.Address.delete' => 1]);
    foreach ($contactIDs as $id) {
      $this->callAPISuccess('Address', 'create', [
        'city' => 'Portland',
        'street_address' => 'Ambachtstraat 23',
        'state_province_id' => 'Maine',
        'location_type_id' => 'Home',
        'contact_id' => $id,
      ]);
    }

    $this->doExportTest([
      'selectAll' => FALSE,
      'ids' => $contactIDs,
      'mergeSameAddress' => TRUE,
    ]);

    $this->assertCount(1, $this->csv);
    $this->csv->fetchOne();
    $this->assertEquals('Household', $this->csv->fetchOne()['Contact Type']);
  }

  /**
   * Test that deceased and do not mail contacts are removed from contacts
   * before
   *
   * @dataProvider getReasonsNotToMail
   *
   * @param array $reason
   * @param array $addressReason
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\UnableToProcessCsv
   */
  public function testExportDeceasedDoNotMail($reason, $addressReason): void {
    $contactA = $this->callAPISuccess('contact', 'create', [
      'first_name' => 'John',
      'last_name' => 'Doe',
      'contact_type' => 'Individual',
    ]);

    $contactB = $this->callAPISuccess('contact', 'create', array_merge([
      'first_name' => 'Jane',
      'last_name' => 'Doe',
      'contact_type' => 'Individual',
    ], $reason));

    // Create another contact not included in the exporrt set.
    $this->callAPISuccess('contact', 'create', array_merge([
      'first_name' => 'Janet',
      'last_name' => 'Doe',
      'contact_type' => 'Individual',
      'api.address.create' => ['supplemental_address_1' => 'An address'],
    ], $reason));

    // Create another contact not included in the exporrt set.
    $this->callAPISuccess('contact', 'create', array_merge([
      'first_name' => 'Janice',
      'last_name' => 'Doe',
      'contact_type' => 'Individual',
    ], $reason));

    //create address for contact A
    $this->callAPISuccess('address', 'create', [
      'contact_id' => $contactA['id'],
      'location_type_id' => 'Home',
      'street_address' => 'ABC 12',
      'postal_code' => '123 AB',
      'country_id' => '1152',
      'city' => 'ABC',
      'is_primary' => 1,
    ]);

    //create address for contact B
    $this->callAPISuccess('address', 'create', array_merge([
      'contact_id' => $contactB['id'],
      'location_type_id' => 'Home',
      'street_address' => 'ABC 12',
      'postal_code' => '123 AB',
      'country_id' => '1152',
      'city' => 'ABC',
      'is_primary' => 1,
    ], $addressReason));

    $this->doExportTest([
      'selectAll' => TRUE,
      'ids' => [$contactA['id'], $contactB['id']],
      'exportParams' => [
        'postal_mailing_export' => [
          'postal_mailing_export' => TRUE,
        ],
        'mergeSameAddress' => TRUE,
      ],
    ]);
    $row = $this->csv->fetchOne();

    $this->assertNotContains('Stage', $this->processor->getHeaderRows());
    $this->assertEquals('Dear John', $row['Email Greeting']);
    $this->assertCount(1, $this->csv);
  }

  /**
   * Get reasons that a contact is not postalable.
   *
   * @return array
   */
  public static function getReasonsNotToMail(): array {
    return [
      [['is_deceased' => 1], []],
      [['do_not_mail' => 1], []],
      [[], ['street_address' => '']],
    ];
  }

  /**
   * Set up household for tests.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function setUpHousehold(): array {
    $this->setUpContactExportData();
    $householdID = $this->householdCreate([
      'source' => 'household sauce',
      'api.Address.create' => [
        'city' => 'Portland',
        'state_province_id' => 'Maine',
        'location_type_id' => 'Home',
      ],
    ]);

    $relationshipTypes = $this->callAPISuccess('RelationshipType', 'get', [])['values'];
    $houseHoldTypeID = NULL;
    foreach ($relationshipTypes as $relationshipType) {
      if ($relationshipType['name_a_b'] === 'Household Member of') {
        $houseHoldTypeID = $relationshipType['id'];
      }
    }
    $this->callAPISuccess('Relationship', 'create', [
      'contact_id_a' => $this->contactIDs[0],
      'contact_id_b' => $householdID,
      'relationship_type_id' => $houseHoldTypeID,
    ]);
    $this->callAPISuccess('Relationship', 'create', [
      'contact_id_a' => $this->contactIDs[1],
      'contact_id_b' => $householdID,
      'relationship_type_id' => $houseHoldTypeID,
    ]);
    return [$householdID, $houseHoldTypeID];
  }

  /**
   * Ensure component is enabled.
   *
   * @param int $exportMode
   */
  public function ensureComponentIsEnabled(int $exportMode): void {
    if ($exportMode === CRM_Export_Form_Select::CASE_EXPORT) {
      CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
    }
  }

  /**
   * Test our export all field metadata retrieval.
   *
   * @dataProvider additionalFieldsDataProvider
   *
   * @param int $exportMode
   * @param array $expected
   */
  public function testAdditionalReturnProperties(int $exportMode, array $expected): void {
    $this->ensureComponentIsEnabled($exportMode);
    $processor = new CRM_Export_BAO_ExportProcessor($exportMode, NULL, 'AND');
    $metadata = $processor->getAdditionalReturnProperties();
    $this->assertEquals($expected, $metadata);
  }

  /**
   * Test our export all field metadata retrieval.
   *
   * @dataProvider allFieldsDataProvider
   *
   * @param int $exportMode
   * @param array $expected
   */
  public function testDefaultReturnProperties(int $exportMode, array $expected): void {
    $this->ensureComponentIsEnabled($exportMode);
    $processor = new CRM_Export_BAO_ExportProcessor($exportMode, NULL, 'AND');
    $metadata = $processor->getDefaultReturnProperties();
    $this->assertEquals($expected, $metadata);
  }

  /**
   * Get fields returned from additionalFields function.
   *
   * @return array
   */
  public static function additionalFieldsDataProvider(): array {
    return [
      [
        // 0 defaults to 'contact'
        0,
        self::getExtraReturnProperties(),
      ],
      [
        CRM_Export_Form_Select::ACTIVITY_EXPORT,
        array_merge(self::getExtraReturnProperties(), self::getActivityReturnProperties()),
      ],
      [
        CRM_Export_Form_Select::CASE_EXPORT,
        array_merge(self::getExtraReturnProperties(), self::getCaseReturnProperties()),
      ],
      [
        CRM_Export_Form_Select::CONTRIBUTE_EXPORT,
        array_merge(self::getExtraReturnProperties(), self::getContributionReturnProperties()),
      ],
      [
        CRM_Export_Form_Select::EVENT_EXPORT,
        array_merge(self::getExtraReturnProperties(), self::getEventReturnProperties()),
      ],
      [
        CRM_Export_Form_Select::MEMBER_EXPORT,
        array_merge(self::getExtraReturnProperties(), self::getMembershipReturnProperties()),
      ],
      [
        CRM_Export_Form_Select::PLEDGE_EXPORT,
        array_merge(self::getExtraReturnProperties(), self::getPledgeReturnProperties()),
      ],

    ];
  }

  /**
   * get data for testing field metadata by query mode.
   */
  public static function allFieldsDataProvider(): array {
    return [
      [
        0,
        self::getBasicReturnProperties(TRUE),
      ],
      [
        CRM_Export_Form_Select::ACTIVITY_EXPORT,
        array_merge(self::getBasicReturnProperties(FALSE), self::getActivityReturnProperties()),
      ],
      [
        CRM_Export_Form_Select::CASE_EXPORT,
        array_merge(self::getBasicReturnProperties(FALSE), self::getCaseReturnProperties()),
      ],
      [
        CRM_Export_Form_Select::CONTRIBUTE_EXPORT,
        array_merge(self::getBasicReturnProperties(FALSE), self::getContributionReturnProperties()),
      ],
      [
        CRM_Export_Form_Select::EVENT_EXPORT,
        array_merge(self::getBasicReturnProperties(FALSE), self::getEventReturnProperties()),
      ],
      [
        CRM_Export_Form_Select::MEMBER_EXPORT,
        array_merge(self::getBasicReturnProperties(FALSE), self::getMembershipReturnProperties()),
      ],
      [
        CRM_Export_Form_Select::PLEDGE_EXPORT,
        array_merge(self::getBasicReturnProperties(FALSE), self::getPledgeReturnProperties()),
      ],
    ];
  }

  /**
   * Get return properties manually added in.
   */
  public static function getExtraReturnProperties(): array {
    return [];
  }

  /**
   * Get basic return properties.
   *
   * @param bool $isContactMode
   *   Are we in contact mode or not
   *
   * @return array
   */
  protected static function getBasicReturnProperties($isContactMode): array {
    $returnProperties = [
      'id' => 1,
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'do_not_email' => 1,
      'do_not_phone' => 1,
      'do_not_mail' => 1,
      'do_not_sms' => 1,
      'do_not_trade' => 1,
      'is_opt_out' => 1,
      'legal_identifier' => 1,
      'external_identifier' => 1,
      'sort_name' => 1,
      'display_name' => 1,
      'nick_name' => 1,
      'legal_name' => 1,
      'image_URL' => 1,
      'preferred_communication_method' => 1,
      'preferred_language' => 1,
      'hash' => 1,
      'contact_source' => 1,
      'first_name' => 1,
      'middle_name' => 1,
      'last_name' => 1,
      'prefix_id' => 1,
      'suffix_id' => 1,
      'formal_title' => 1,
      'communication_style_id' => 1,
      'email_greeting_id' => 1,
      'postal_greeting_id' => 1,
      'addressee_id' => 1,
      'job_title' => 1,
      'gender_id' => 1,
      'birth_date' => 1,
      'is_deceased' => 1,
      'deceased_date' => 1,
      'household_name' => 1,
      'organization_name' => 1,
      'sic_code' => 1,
      'user_unique_id' => 1,
      'current_employer_id' => 1,
      'contact_is_deleted' => 1,
      'created_date' => 1,
      'modified_date' => 1,
      'addressee' => 1,
      'email_greeting' => 1,
      'postal_greeting' => 1,
      'current_employer' => 1,
      'location_type' => 1,
      'address_id' => 1,
      'street_address' => 1,
      'street_number' => 1,
      'street_number_suffix' => 1,
      'street_name' => 1,
      'street_unit' => 1,
      'supplemental_address_1' => 1,
      'supplemental_address_2' => 1,
      'supplemental_address_3' => 1,
      'city' => 1,
      'postal_code_suffix' => 1,
      'postal_code' => 1,
      'geo_code_1' => 1,
      'geo_code_2' => 1,
      'manual_geo_code' => 1,
      'address_name' => 1,
      'master_id' => 1,
      'county' => 1,
      'state_province' => 1,
      'country' => 1,
      'phone' => 1,
      'phone_ext' => 1,
      'email' => 1,
      'on_hold' => 1,
      'is_bulkmail' => 1,
      'signature_text' => 1,
      'signature_html' => 1,
      'im_provider' => 1,
      'im' => 1,
      'openid' => 1,
      'world_region' => 1,
      'url' => 1,
      'groups' => 1,
      'tags' => 1,
      'notes' => 1,
      'phone_type_id' => 1,
      'phone_type' => 1,
    ];
    if (!$isContactMode) {
      unset($returnProperties['groups'], $returnProperties['tags'], $returnProperties['notes']);
    }
    return $returnProperties;
  }

  /**
   * Get return properties for pledges.
   *
   * @return array
   */
  public static function getPledgeReturnProperties(): array {
    return [
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
      'display_name' => 1,
      'pledge_id' => 1,
      'pledge_amount' => 1,
      'pledge_total_paid' => 1,
      'pledge_create_date' => 1,
      'pledge_start_date' => 1,
      'pledge_next_pay_date' => 1,
      'pledge_next_pay_amount' => 1,
      'pledge_status' => 1,
      'pledge_is_test' => 1,
      'pledge_contribution_page_id' => 1,
      'pledge_financial_type' => 1,
      'pledge_frequency_interval' => 1,
      'pledge_frequency_unit' => 1,
      'pledge_currency' => 1,
      'pledge_campaign_id' => 1,
      'pledge_balance_amount' => 1,
      'pledge_payment_id' => 1,
      'pledge_payment_scheduled_amount' => 1,
      'pledge_payment_scheduled_date' => 1,
      'pledge_payment_paid_amount' => 1,
      'pledge_payment_paid_date' => 1,
      'pledge_payment_reminder_date' => 1,
      'pledge_payment_reminder_count' => 1,
      'pledge_payment_status' => 1,
    ];
  }

  /**
   * Get membership return properties.
   *
   * @return array
   */
  public static function getMembershipReturnProperties(): array {
    return [
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
      'display_name' => 1,
      'membership_type' => 1,
      'member_is_test' => 1,
      'member_is_pay_later' => 1,
      'membership_join_date' => 1,
      'membership_start_date' => 1,
      'membership_end_date' => 1,
      'membership_source' => 1,
      'membership_status' => 1,
      'membership_id' => 1,
      'owner_membership_id' => 1,
      'max_related' => 1,
      'membership_recur_id' => 1,
      'member_campaign_id' => 1,
      'member_is_override' => 1,
    ];
  }

  /**
   * Get return properties for events.
   *
   * @return array
   */
  public static function getEventReturnProperties(): array {
    return [
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
      'display_name' => 1,
      'event_id' => 1,
      'event_title' => 1,
      'event_start_date' => 1,
      'event_end_date' => 1,
      'event_type' => 1,
      'participant_id' => 1,
      'participant_status' => 1,
      'participant_status_id' => 1,
      'participant_role' => 1,
      'participant_role_id' => 1,
      'participant_note' => 1,
      'participant_register_date' => 1,
      'participant_source' => 1,
      'participant_fee_level' => 1,
      'participant_is_test' => 1,
      'participant_is_pay_later' => 1,
      'participant_fee_amount' => 1,
      'participant_discount_name' => 1,
      'participant_fee_currency' => 1,
      'participant_registered_by_id' => 1,
      'participant_campaign_id' => 1,
    ];
  }

  /**
   * Get return properties for activities.
   *
   * @return array
   */
  public static function getActivityReturnProperties(): array {
    return [
      'activity_id' => 1,
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
      'display_name' => 1,
      'activity_type' => 1,
      'activity_type_id' => 1,
      'activity_subject' => 1,
      'activity_date_time' => 1,
      'activity_duration' => 1,
      'activity_location' => 1,
      'activity_details' => 1,
      'activity_status' => 1,
      'activity_priority' => 1,
      'source_contact' => 1,
      'source_record_id' => 1,
      'activity_is_test' => 1,
      'activity_campaign_id' => 1,
      'result' => 1,
      'activity_engagement_level' => 1,
      'parent_id' => 1,
    ];
  }

  /**
   * Get return properties for Case.
   *
   * @return array
   */
  public static function getCaseReturnProperties(): array {
    return [
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
      'display_name' => 1,
      'phone' => 1,
      'case_start_date' => 1,
      'case_end_date' => 1,
      'case_subject' => 1,
      'case_source_contact_id' => 1,
      'case_activity_status' => 1,
      'case_activity_duration' => 1,
      'case_activity_medium_id' => 1,
      'case_activity_details' => 1,
      'case_activity_is_auto' => 1,
      'contact_id' => 1,
      'case_id' => 1,
      'case_activity_subject' => 1,
      'case_status' => 1,
      'case_type' => 1,
      'case_role' => 1,
      'case_deleted' => 1,
      'case_activity_date_time' => 1,
      'case_activity_type' => 1,
    ];
  }

  /**
   * Get return properties for contribution.
   *
   * @return array
   */
  public static function getContributionReturnProperties(): array {
    return [
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
      'display_name' => 1,
      'financial_type' => 1,
      'contribution_source' => 1,
      'receive_date' => 1,
      'thankyou_date' => 1,
      'contribution_cancel_date' => 1,
      'total_amount' => 1,
      'accounting_code' => 1,
      'payment_instrument' => 1,
      'payment_instrument_id' => 1,
      'contribution_check_number' => 1,
      'non_deductible_amount' => 1,
      'fee_amount' => 1,
      'net_amount' => 1,
      'trxn_id' => 1,
      'invoice_id' => 1,
      'invoice_number' => 1,
      'currency' => 1,
      'cancel_reason' => 1,
      'receipt_date' => 1,
      'is_test' => 1,
      'is_pay_later' => 1,
      'contribution_status' => 1,
      'contribution_recur_id' => 1,
      'amount_level' => 1,
      'contribution_note' => 1,
      'contribution_batch' => 1,
      'contribution_campaign_title' => 1,
      'contribution_campaign_id' => 1,
      'contribution_soft_credit_name' => 1,
      'contribution_soft_credit_amount' => 1,
      'contribution_soft_credit_type' => 1,
      'contribution_soft_credit_contact_id' => 1,
      'contribution_soft_credit_contribution_id' => 1,
    ];
  }

  /**
   * Test the column definition when 'all' fields defined.
   *
   * @param int $exportMode
   * @param array $expected
   * @param array $expectedHeaders
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\Exception
   *
   * @dataProvider getSqlColumnsOutput
   */
  public function testGetSQLColumnsAndHeaders(int $exportMode, array $expected, array $expectedHeaders): void {
    $this->ensureComponentIsEnabled($exportMode);
    // We need some data so that we can get to the end of the export
    // function. Hopefully one day that won't be required to get metadata info out.
    // eventually aspire to call $provider->getSQLColumns straight after it
    // is initiated.
    $this->setupBaseExportData($exportMode);
    $this->doExportTest(['selectAll' => TRUE, 'exportMode' => $exportMode, 'ids' => [1]]);
    $this->assertEquals($expected, $this->processor->getSQLColumns());
    $this->assertEquals(array_values($expectedHeaders), $this->processor->getHeaderRows());
  }

  /**
   * Test exported with data entry mis-fire.
   *
   * Not fatal error if data incomplete.
   *
   * https://lab.civicrm.org/dev/core/issues/819
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\Exception
   */
  public function testExportIncompleteSubmission(): void {
    $this->setUpContactExportData();
    $this->doExportTest(['fields' => [['contact_type' => 'Individual', 'name' => '']], 'ids' => [$this->contactIDs[1]]]);
  }

  /**
   * Test exported with fields to output specified.
   *
   * @dataProvider getAllSpecifiableReturnFields
   *
   * @param int $exportMode
   * @param array $selectedFields
   * @param array $expected
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\Exception
   */
  public function testExportSpecifyFields($exportMode, $selectedFields, $expected): void {
    $this->ensureComponentIsEnabled($exportMode);
    $this->setUpContributionExportData();
    $this->doExportTest(['fields' => $selectedFields, 'ids' => [$this->contactIDs[1]], 'exportMode' => $exportMode]);
    $this->assertEquals($expected, $this->processor->getSQLColumns());
  }

  /**
   * Test export fields when no payment fields to be exported.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\Exception
   */
  public function textExportParticipantSpecifyFieldsNoPayment(): void {
    $selectedFields = $this->getAllSpecifiableParticipantReturnFields();
    foreach ($selectedFields as $index => $field) {
      if (str_starts_with($field[1], 'componentPaymentField_')) {
        unset($selectedFields[$index]);
      }
    }

    $expected = $this->getAllSpecifiableParticipantReturnFields();
    foreach ($expected as $index => $field) {
      if (str_starts_with($index, 'componentPaymentField_')) {
        unset($expected[$index]);
      }
    }
    $this->doExportTest(['fields' => $selectedFields, 'ids' => [$this->contactIDs[1]], 'exportMode' => CRM_Export_Form_Select::EVENT_EXPORT]);
    $this->assertEquals($expected, $this->processor->getSQLColumns());
  }

  /**
   * Get all return fields (@return array
   *
   * @todo - still being built up.
   *
   */
  public static function getAllSpecifiableReturnFields(): array {
    return [
      [
        CRM_Export_Form_Select::EVENT_EXPORT,
        self::getAllSpecifiableParticipantReturnFields(),
        self::getAllSpecifiableParticipantReturnColumns(),
      ],
    ];
  }

  /**
   * Get expected return column output for participant mode return all columns.
   *
   * @return array
   */
  public static function getAllSpecifiableParticipantReturnColumns(): array {
    return [
      'participant_campaign_id' => '`participant_campaign_id` varchar(64)',
      'participant_contact_id' => '`participant_contact_id` varchar(64)',
      'componentpaymentfield_contribution_status' => '`componentpaymentfield_contribution_status` varchar(255)',
      'currency' => '`currency` varchar(3)',
      'componentpaymentfield_received_date' => '`componentpaymentfield_received_date` varchar(32)',
      'default_role_id' => '`default_role_id` varchar(64)',
      'participant_discount_name' => '`participant_discount_name` varchar(64)',
      'event_id' => '`event_id` varchar(64)',
      'event_end_date' => '`event_end_date` varchar(32)',
      'event_start_date' => '`event_start_date` varchar(32)',
      'template_title' => '`template_title` varchar(255)',
      'event_title' => '`event_title` varchar(255)',
      'participant_fee_amount' => '`participant_fee_amount` varchar(32)',
      'participant_fee_currency' => '`participant_fee_currency` varchar(3)',
      'fee_label' => '`fee_label` varchar(255)',
      'participant_fee_level' => '`participant_fee_level` longtext',
      'participant_is_pay_later' => '`participant_is_pay_later` varchar(64)',
      'participant_id' => '`participant_id` varchar(64)',
      'participant_note' => '`participant_note` longtext',
      'participant_role_id' => '`participant_role_id` varchar(128)',
      'participant_role' => '`participant_role` varchar(255)',
      'participant_source' => '`participant_source` varchar(128)',
      'participant_status_id' => '`participant_status_id` varchar(64)',
      'participant_status' => '`participant_status` varchar(255)',
      'participant_register_date' => '`participant_register_date` varchar(32)',
      'participant_registered_by_id' => '`participant_registered_by_id` varchar(64)',
      'participant_is_test' => '`participant_is_test` varchar(64)',
      'componentpaymentfield_total_amount' => '`componentpaymentfield_total_amount` varchar(32)',
      'componentpaymentfield_transaction_id' => '`componentpaymentfield_transaction_id` varchar(255)',
      'transferred_to_contact_id' => '`transferred_to_contact_id` varchar(64)',
    ];
  }

  /**
   * @return array
   */
  public static function getAllSpecifiableParticipantReturnFields(): array {
    return [
      0 =>
        [
          0 => 'Participant',
          'name' => '',
        ],
      1 =>
        [
          0 => 'Participant',
          'name' => 'participant_campaign_id',
        ],
      2 =>
        [
          0 => 'Participant',
          'name' => 'participant_contact_id',
        ],
      3 =>
        [
          0 => 'Participant',
          'name' => 'componentPaymentField_contribution_status',
        ],
      4 =>
        [
          0 => 'Participant',
          'name' => 'currency',
        ],
      5 =>
        [
          0 => 'Participant',
          'name' => 'componentPaymentField_received_date',
        ],
      6 =>
        [
          0 => 'Participant',
          'name' => 'default_role_id',
        ],
      7 =>
        [
          0 => 'Participant',
          'name' => 'participant_discount_name',
        ],
      8 =>
        [
          0 => 'Participant',
          'name' => 'event_id',
        ],
      9 =>
        [
          0 => 'Participant',
          'name' => 'event_end_date',
        ],
      10 =>
        [
          0 => 'Participant',
          'name' => 'event_start_date',
        ],
      11 =>
        [
          0 => 'Participant',
          'name' => 'template_title',
        ],
      12 =>
        [
          0 => 'Participant',
          'name' => 'event_title',
        ],
      13 =>
        [
          0 => 'Participant',
          'name' => 'participant_fee_amount',
        ],
      14 =>
        [
          0 => 'Participant',
          'name' => 'participant_fee_currency',
        ],
      15 =>
        [
          0 => 'Participant',
          'name' => 'fee_label',
        ],
      16 =>
        [
          0 => 'Participant',
          'name' => 'participant_fee_level',
        ],
      17 =>
        [
          0 => 'Participant',
          'name' => 'participant_is_pay_later',
        ],
      18 =>
        [
          0 => 'Participant',
          'name' => 'participant_id',
        ],
      19 =>
        [
          0 => 'Participant',
          'name' => 'participant_note',
        ],
      20 =>
        [
          0 => 'Participant',
          'name' => 'participant_role_id',
        ],
      21 =>
        [
          0 => 'Participant',
          'name' => 'participant_role',
        ],
      22 =>
        [
          0 => 'Participant',
          'name' => 'participant_source',
        ],
      23 =>
        [
          0 => 'Participant',
          'name' => 'participant_status_id',
        ],
      24 =>
        [
          0 => 'Participant',
          'name' => 'participant_status',
        ],
      25 =>
        [
          0 => 'Participant',
          'name' => 'participant_status',
        ],
      26 =>
        [
          0 => 'Participant',
          'name' => 'participant_register_date',
        ],
      27 =>
        [
          0 => 'Participant',
          'name' => 'participant_registered_by_id',
        ],
      28 =>
        [
          0 => 'Participant',
          'name' => 'participant_is_test',
        ],
      29 =>
        [
          0 => 'Participant',
          'name' => 'componentPaymentField_total_amount',
        ],
      30 =>
        [
          0 => 'Participant',
          'name' => 'componentPaymentField_transaction_id',
        ],
      31 =>
        [
          0 => 'Participant',
          'name' => 'transferred_to_contact_id',
        ],
    ];
  }

  /**
   * @param string $exportMode
   *
   * @throws \CRM_Core_Exception
   */
  public function setupBaseExportData($exportMode): void {
    $this->createLoggedInUser();
    if ($exportMode === CRM_Export_Form_Select::CASE_EXPORT) {
      $this->setupCaseExportData();
    }
    if ($exportMode === CRM_Export_Form_Select::CONTRIBUTE_EXPORT) {
      $this->setUpContributionExportData();
    }
    if ($exportMode === CRM_Export_Form_Select::MEMBER_EXPORT) {
      $this->setUpMembershipExportData();
    }
    if ($exportMode === CRM_Export_Form_Select::ACTIVITY_EXPORT) {
      $this->setUpActivityExportData();
    }
  }

  /**
   * Get comprehensive sql columns output.
   *
   * @return array
   */
  public static function getSqlColumnsOutput(): array {
    return [
      [
        0,
        self::getBasicSqlColumnDefinition(TRUE),
        self::getBasicHeaderDefinition(TRUE),
      ],
      [
        CRM_Export_Form_Select::ACTIVITY_EXPORT,
        array_merge(self::getBasicSqlColumnDefinition(FALSE), self::getActivitySqlColumns()),
        array_merge(self::getBasicHeaderDefinition(FALSE), self::getActivityHeaderDefinition()),
      ],
      [
        CRM_Export_Form_Select::CASE_EXPORT,
        array_merge(self::getBasicSqlColumnDefinition(FALSE), self::getCaseSqlColumns()),
        array_merge(self::getBasicHeaderDefinition(FALSE), self::getCaseHeaderDefinition()),
      ],
      [
        CRM_Export_Form_Select::CONTRIBUTE_EXPORT,
        array_merge(self::getBasicSqlColumnDefinition(FALSE), self::getContributionSqlColumns()),
        array_merge(self::getBasicHeaderDefinition(FALSE), self::getContributeHeaderDefinition()),
      ],
      [
        CRM_Export_Form_Select::EVENT_EXPORT,
        array_merge(self::getBasicSqlColumnDefinition(FALSE), self::getParticipantSqlColumns()),
        array_merge(self::getBasicHeaderDefinition(FALSE), self::getParticipantHeaderDefinition()),
      ],
      [
        CRM_Export_Form_Select::MEMBER_EXPORT,
        array_merge(self::getBasicSqlColumnDefinition(FALSE), self::getMembershipSqlColumns()),
        array_merge(self::getBasicHeaderDefinition(FALSE), self::getMemberHeaderDefinition()),
      ],
      [
        CRM_Export_Form_Select::PLEDGE_EXPORT,
        array_merge(self::getBasicSqlColumnDefinition(FALSE), self::getPledgeSqlColumns()),
        array_merge(self::getBasicHeaderDefinition(FALSE), self::getPledgeHeaderDefinition()),
      ],

    ];
  }

  /**
   * Get the header definition for exports.
   *
   * @param bool $isContactExport
   *
   * @return array
   */
  protected static function getBasicHeaderDefinition(bool $isContactExport): array {
    $headers = [
      0 => 'Contact ID',
      1 => 'Contact Type',
      2 => 'External Identifier',
      3 => 'Display Name',
      4 => 'Organization Name',
      5 => 'Contact Subtype',
      6 => 'First Name',
      7 => 'Middle Name',
      8 => 'Last Name',
      9 => 'Do Not Email',
      10 => 'Do Not Phone',
      11 => 'Do Not Mail',
      12 => 'Do Not Sms',
      13 => 'Do Not Trade',
      14 => 'No Bulk Emails (User Opt Out)',
      15 => 'Legal Identifier',
      16 => 'Sort Name',
      17 => 'Nickname',
      18 => 'Legal Name',
      19 => 'Image Url',
      20 => 'Preferred Communication Method',
      21 => 'Preferred Language',
      22 => 'Contact Hash',
      23 => 'Contact Source',
      24 => 'Individual Prefix',
      25 => 'Individual Suffix',
      26 => 'Formal Title',
      27 => 'Communication Style',
      28 => 'Email Greeting ID',
      29 => 'Postal Greeting ID',
      30 => 'Addressee ID',
      31 => 'Job Title',
      32 => 'Gender',
      33 => 'Birth Date',
      34 => 'Deceased / Closed',
      35 => 'Deceased / Closed Date',
      36 => 'Household Name',
      37 => 'Sic Code',
      38 => 'Unique ID (OpenID)',
      39 => 'Current Employer ID',
      40 => 'Contact is in Trash',
      41 => 'Created Date',
      42 => 'Modified Date',
      43 => 'Addressee',
      44 => 'Email Greeting',
      45 => 'Postal Greeting',
      46 => 'Current Employer',
      47 => 'Location Type',
      48 => 'Address ID',
      49 => 'Street Address',
      50 => 'Street Number',
      51 => 'Street Number Suffix',
      52 => 'Street Name',
      53 => 'Street Unit',
      54 => 'Supplemental Address 1',
      55 => 'Supplemental Address 2',
      56 => 'Supplemental Address 3',
      57 => 'City',
      58 => 'Postal Code Suffix',
      59 => 'Postal Code',
      60 => 'Latitude',
      61 => 'Longitude',
      62 => 'Is Manually Geocoded',
      63 => 'Address Name',
      64 => 'Master Address ID',
      65 => 'County',
      66 => 'State',
      67 => 'Country',
      68 => 'Phone',
      69 => 'Phone Extension',
      70 => 'Phone Type ID',
      71 => 'Phone Type',
      72 => 'Email',
      73 => 'On Hold',
      74 => 'Use for Bulk Mail',
      75 => 'Signature Text',
      76 => 'Signature Html',
      77 => 'IM Provider',
      78 => 'IM Screen Name',
      79 => 'OpenID',
      80 => 'World Region',
      81 => 'Website',
      82 => 'Group(s)',
      83 => 'Tag(s)',
      84 => 'Note(s)',
    ];
    if (!$isContactExport) {
      unset($headers[82]);
      unset($headers[83]);
      unset($headers[84]);
    }
    return $headers;
  }

  /**
   * Get the definition for activity headers.
   *
   * @return array
   */
  protected static function getActivityHeaderDefinition(): array {
    return [
      82 => 'Activity ID',
      83 => 'Activity Type',
      84 => 'Activity Type ID',
      85 => 'Subject',
      86 => 'Activity Date',
      87 => 'Duration',
      88 => 'Location',
      89 => 'Details',
      90 => 'Activity Status',
      91 => 'Activity Priority',
      92 => 'Source Contact',
      93 => 'source_record_id',
      94 => 'Test',
      95 => 'Campaign ID',
      96 => 'result',
      97 => 'Engagement Index',
      98 => 'parent_id',
    ];
  }

  /**
   * Get the definition for case headers.
   *
   * @return array
   */
  protected static function getCaseHeaderDefinition(): array {
    return [
      82 => 'Contact ID',
      83 => 'Case ID',
      84 => 'Subject',
      85 => 'Case Subject',
      86 => 'Case Status',
      87 => 'Case Type',
      88 => 'Role in Case',
      89 => 'Case is in the Trash',
      90 => 'Activity Date',
      91 => 'Activity Type',
      93 => 'Case Start Date',
      94 => 'Case End Date',
      95 => 'Activity Reporter',
      96 => 'Activity Status',
      97 => 'Duration',
      98 => 'Activity Medium',
      99 => 'Details',
      100 => 'Activity Auto-generated?',
    ];
  }

  /**
   * Get the definition for contribute headers.
   *
   * @return array
   */
  protected static function getContributeHeaderDefinition(): array {
    return [
      82 => 'Financial Type Label',
      83 => 'Contribution Source',
      84 => 'Contribution Date',
      85 => 'Thank-you Date',
      86 => 'Cancelled / Refunded Date',
      87 => 'Total Amount',
      88 => 'Accounting Code',
      89 => 'Payment Methods',
      90 => 'Payment Method ID',
      91 => 'Check Number',
      92 => 'Non-deductible Amount',
      93 => 'Fee Amount',
      94 => 'Net Amount',
      95 => 'Transaction ID',
      96 => 'Invoice Reference',
      97 => 'Invoice Number',
      98 => 'Currency',
      99 => 'Cancellation / Refund Reason',
      100 => 'Receipt Date',
      101 => 'Test',
      102 => 'Is Pay Later',
      103 => 'Contribution Status',
      104 => 'Recurring Contribution ID',
      105 => 'Amount Label',
      106 => 'Contribution Note',
      107 => 'Batch Name',
      108 => 'Campaign Title',
      109 => 'Campaign ID',
      110 => 'Soft Credit For',
      111 => 'Soft Credit Amount',
      112 => 'Soft Credit Type',
      113 => 'Soft Credit For Contact ID',
      114 => 'Soft Credit For Contribution ID',
    ];
  }

  /**
   * Get the definition for event headers.
   *
   * @return array
   */
  protected static function getParticipantHeaderDefinition(): array {
    return [
      82 => 'Event ID',
      83 => 'Event Title',
      84 => 'Event Start Date',
      85 => 'Event End Date',
      86 => 'Event Type',
      87 => 'Participant ID',
      88 => 'Participant Status',
      89 => 'Participant Status Id',
      90 => 'Participant Role',
      91 => 'Participant Role Id',
      92 => 'Participant Note',
      93 => 'Register date',
      94 => 'Participant Source',
      95 => 'Fee level',
      96 => 'Test',
      97 => 'Is Pay Later',
      98 => 'Fee Amount',
      99 => 'Price Set ID',
      100 => 'Fee Currency',
      101 => 'Registered By Participant ID',
      102 => 'Campaign ID',
    ];
  }

  /**
   * Get the definition for member headers.
   *
   * @return array
   */
  protected static function getMemberHeaderDefinition(): array {
    return [
      82 => 'Membership Type',
      83 => 'Test',
      84 => 'Is Pay Later',
      85 => 'Member Since',
      86 => 'Membership Start Date',
      87 => 'Membership Expiration Date',
      88 => 'Membership Source',
      89 => 'Membership Status',
      90 => 'Membership ID',
      91 => 'Primary Member ID',
      92 => 'Max Related',
      93 => 'Recurring Contribution ID',
      94 => 'Campaign ID',
      95 => 'Status Override',
    ];
  }

  /**
   * Get the definition for pledge headers.
   *
   * @return array
   */
  protected static function getPledgeHeaderDefinition(): array {
    return [
      82 => 'Pledge ID',
      83 => 'Total Pledged',
      84 => 'Total Paid',
      85 => 'Pledge Made',
      86 => 'Pledge Start Date',
      87 => 'Next Payment Date',
      88 => 'Next Payment Amount',
      89 => 'Pledge Status',
      90 => 'Test',
      91 => 'Pledge Contribution Page Id',
      92 => 'pledge_financial_type',
      93 => 'Pledge Frequency Interval',
      94 => 'Pledge Frequency Unit',
      95 => 'pledge_currency',
      96 => 'Campaign ID',
      97 => 'Balance Amount',
      98 => 'Payment ID',
      99 => 'Scheduled Amount',
      100 => 'Scheduled Date',
      101 => 'Paid Amount',
      102 => 'Paid Date',
      103 => 'Last Reminder',
      104 => 'Reminders Sent',
      105 => 'Pledge Payment Status',
    ];
  }

  /**
   * Get the column definition for exports.
   *
   * @param bool $isContactExport
   *
   * @return array
   */
  protected static function getBasicSqlColumnDefinition($isContactExport): array {
    $columns = [
      'civicrm_primary_id' => '`civicrm_primary_id` varchar(64)',
      'contact_type' => '`contact_type` varchar(64)',
      'contact_sub_type' => '`contact_sub_type` varchar(255)',
      'do_not_email' => '`do_not_email` varchar(64)',
      'do_not_phone' => '`do_not_phone` varchar(64)',
      'do_not_mail' => '`do_not_mail` varchar(64)',
      'do_not_sms' => '`do_not_sms` varchar(64)',
      'do_not_trade' => '`do_not_trade` varchar(64)',
      'is_opt_out' => '`is_opt_out` varchar(64)',
      'legal_identifier' => '`legal_identifier` varchar(32)',
      'external_identifier' => '`external_identifier` varchar(64)',
      'sort_name' => '`sort_name` varchar(128)',
      'display_name' => '`display_name` varchar(128)',
      'nick_name' => '`nick_name` varchar(128)',
      'legal_name' => '`legal_name` varchar(128)',
      'image_url' => '`image_url` longtext',
      'preferred_communication_method' => '`preferred_communication_method` varchar(255)',
      'preferred_language' => '`preferred_language` varchar(5)',
      'hash' => '`hash` varchar(32)',
      'contact_source' => '`contact_source` varchar(255)',
      'first_name' => '`first_name` varchar(64)',
      'middle_name' => '`middle_name` varchar(64)',
      'last_name' => '`last_name` varchar(64)',
      'prefix_id' => '`prefix_id` varchar(255)',
      'suffix_id' => '`suffix_id` varchar(255)',
      'formal_title' => '`formal_title` varchar(64)',
      'communication_style_id' => '`communication_style_id` varchar(255)',
      'email_greeting_id' => '`email_greeting_id` varchar(64)',
      'postal_greeting_id' => '`postal_greeting_id` varchar(64)',
      'addressee_id' => '`addressee_id` varchar(64)',
      'job_title' => '`job_title` varchar(255)',
      'gender_id' => '`gender_id` varchar(255)',
      'birth_date' => '`birth_date` varchar(32)',
      'is_deceased' => '`is_deceased` varchar(64)',
      'deceased_date' => '`deceased_date` varchar(32)',
      'household_name' => '`household_name` varchar(128)',
      'organization_name' => '`organization_name` varchar(128)',
      'sic_code' => '`sic_code` varchar(8)',
      'user_unique_id' => '`user_unique_id` varchar(255)',
      'current_employer_id' => '`current_employer_id` varchar(64)',
      'contact_is_deleted' => '`contact_is_deleted` varchar(64)',
      'created_date' => '`created_date` varchar(32)',
      'modified_date' => '`modified_date` varchar(32)',
      'addressee' => '`addressee` varchar(255)',
      'email_greeting' => '`email_greeting` varchar(255)',
      'postal_greeting' => '`postal_greeting` varchar(255)',
      'current_employer' => '`current_employer` varchar(255)',
      'location_type' => '`location_type` varchar(255)',
      'address_id' => '`address_id` varchar(64)',
      'street_address' => '`street_address` varchar(96)',
      'street_number' => '`street_number` varchar(64)',
      'street_number_suffix' => '`street_number_suffix` varchar(8)',
      'street_name' => '`street_name` varchar(64)',
      'street_unit' => '`street_unit` varchar(16)',
      'supplemental_address_1' => '`supplemental_address_1` varchar(96)',
      'supplemental_address_2' => '`supplemental_address_2` varchar(96)',
      'supplemental_address_3' => '`supplemental_address_3` varchar(96)',
      'city' => '`city` varchar(64)',
      'postal_code_suffix' => '`postal_code_suffix` varchar(12)',
      'postal_code' => '`postal_code` varchar(64)',
      'geo_code_1' => '`geo_code_1` varchar(32)',
      'geo_code_2' => '`geo_code_2` varchar(32)',
      'manual_geo_code' => '`manual_geo_code` varchar(64)',
      'address_name' => '`address_name` varchar(255)',
      'master_id' => '`master_id` varchar(128)',
      'county' => '`county` varchar(64)',
      'state_province' => '`state_province` varchar(64)',
      'country' => '`country` varchar(64)',
      'phone' => '`phone` varchar(32)',
      'phone_ext' => '`phone_ext` varchar(16)',
      'phone_type_id' => '`phone_type_id` varchar(64)',
      'email' => '`email` varchar(254)',
      'on_hold' => '`on_hold` varchar(64)',
      'is_bulkmail' => '`is_bulkmail` varchar(64)',
      'signature_text' => '`signature_text` longtext',
      'signature_html' => '`signature_html` longtext',
      'im_provider' => '`im_provider` varchar(255)',
      'im' => '`im` varchar(64)',
      'openid' => '`openid` varchar(255)',
      'world_region' => '`world_region` varchar(128)',
      'url' => '`url` varchar(2048)',
      'groups' => '`groups` longtext',
      'tags' => '`tags` longtext',
      'notes' => '`notes` longtext',
      'phone_type' => '`phone_type` varchar(255)',
    ];
    if (!$isContactExport) {
      unset($columns['groups']);
      unset($columns['tags']);
      unset($columns['notes']);
    }
    return $columns;
  }

  /**
   * Get Case SQL columns.
   *
   * @return array
   */
  protected static function getCaseSqlColumns(): array {
    return [
      'case_start_date' => '`case_start_date` varchar(32)',
      'case_end_date' => '`case_end_date` varchar(32)',
      'case_subject' => '`case_subject` varchar(128)',
      'case_source_contact_id' => '`case_source_contact_id` varchar(255)',
      'case_activity_status' => '`case_activity_status` varchar(255)',
      'case_activity_duration' => '`case_activity_duration` varchar(64)',
      'case_activity_medium_id' => '`case_activity_medium_id` varchar(64)',
      'case_activity_details' => '`case_activity_details` longtext',
      'case_activity_is_auto' => '`case_activity_is_auto` varchar(64)',
      'contact_id' => '`contact_id` varchar(64)',
      'case_id' => '`case_id` varchar(64)',
      'case_activity_subject' => '`case_activity_subject` varchar(255)',
      'case_status' => '`case_status` text',
      'case_type' => '`case_type` text',
      'case_role' => '`case_role` text',
      'case_deleted' => '`case_deleted` varchar(64)',
      'case_activity_date_time' => '`case_activity_date_time` varchar(32)',
      'case_activity_type' => '`case_activity_type` varchar(255)',
    ];
  }

  /**
   * Get activity sql columns.
   *
   * @return array
   */
  protected static function getActivitySqlColumns(): array {
    return [
      'activity_id' => '`activity_id` varchar(64)',
      'activity_type' => '`activity_type` varchar(255)',
      'activity_type_id' => '`activity_type_id` varchar(64)',
      'activity_subject' => '`activity_subject` varchar(255)',
      'activity_date_time' => '`activity_date_time` varchar(32)',
      'activity_duration' => '`activity_duration` varchar(64)',
      'activity_location' => '`activity_location` varchar(2048)',
      'activity_details' => '`activity_details` longtext',
      'activity_status' => '`activity_status` varchar(255)',
      'activity_priority' => '`activity_priority` varchar(255)',
      'source_contact' => '`source_contact` varchar(255)',
      'source_record_id' => '`source_record_id` varchar(255)',
      'activity_is_test' => '`activity_is_test` varchar(64)',
      'activity_campaign_id' => '`activity_campaign_id` varchar(64)',
      'result' => '`result` text',
      'activity_engagement_level' => '`activity_engagement_level` varchar(64)',
      'parent_id' => '`parent_id` varchar(255)',
    ];
  }

  /**
   * Get participant sql columns.
   *
   * @return array
   */
  protected static function getParticipantSqlColumns(): array {
    return [
      'event_id' => '`event_id` varchar(64)',
      'event_title' => '`event_title` varchar(255)',
      'event_start_date' => '`event_start_date` varchar(32)',
      'event_end_date' => '`event_end_date` varchar(32)',
      'event_type' => '`event_type` varchar(255)',
      'participant_id' => '`participant_id` varchar(64)',
      'participant_status' => '`participant_status` varchar(255)',
      'participant_status_id' => '`participant_status_id` varchar(64)',
      'participant_role' => '`participant_role` varchar(255)',
      'participant_role_id' => '`participant_role_id` varchar(128)',
      'participant_note' => '`participant_note` longtext',
      'participant_register_date' => '`participant_register_date` varchar(32)',
      'participant_source' => '`participant_source` varchar(128)',
      'participant_fee_level' => '`participant_fee_level` longtext',
      'participant_is_test' => '`participant_is_test` varchar(64)',
      'participant_is_pay_later' => '`participant_is_pay_later` varchar(64)',
      'participant_fee_amount' => '`participant_fee_amount` varchar(32)',
      'participant_discount_name' => '`participant_discount_name` varchar(64)',
      'participant_fee_currency' => '`participant_fee_currency` varchar(3)',
      'participant_registered_by_id' => '`participant_registered_by_id` varchar(64)',
      'participant_campaign_id' => '`participant_campaign_id` varchar(64)',
    ];
  }

  /**
   * Get contribution sql columns.
   *
   * @return array
   */
  public static function getContributionSqlColumns(): array {
    return [
      'civicrm_primary_id' => '`civicrm_primary_id` varchar(64)',
      'contact_type' => '`contact_type` varchar(64)',
      'contact_sub_type' => '`contact_sub_type` varchar(255)',
      'do_not_email' => '`do_not_email` varchar(64)',
      'do_not_phone' => '`do_not_phone` varchar(64)',
      'do_not_mail' => '`do_not_mail` varchar(64)',
      'do_not_sms' => '`do_not_sms` varchar(64)',
      'do_not_trade' => '`do_not_trade` varchar(64)',
      'is_opt_out' => '`is_opt_out` varchar(64)',
      'legal_identifier' => '`legal_identifier` varchar(32)',
      'external_identifier' => '`external_identifier` varchar(64)',
      'sort_name' => '`sort_name` varchar(128)',
      'display_name' => '`display_name` varchar(128)',
      'nick_name' => '`nick_name` varchar(128)',
      'legal_name' => '`legal_name` varchar(128)',
      'image_url' => '`image_url` longtext',
      'preferred_communication_method' => '`preferred_communication_method` varchar(255)',
      'preferred_language' => '`preferred_language` varchar(5)',
      'hash' => '`hash` varchar(32)',
      'contact_source' => '`contact_source` varchar(255)',
      'first_name' => '`first_name` varchar(64)',
      'middle_name' => '`middle_name` varchar(64)',
      'last_name' => '`last_name` varchar(64)',
      'prefix_id' => '`prefix_id` varchar(255)',
      'suffix_id' => '`suffix_id` varchar(255)',
      'formal_title' => '`formal_title` varchar(64)',
      'communication_style_id' => '`communication_style_id` varchar(255)',
      'email_greeting_id' => '`email_greeting_id` varchar(64)',
      'postal_greeting_id' => '`postal_greeting_id` varchar(64)',
      'addressee_id' => '`addressee_id` varchar(64)',
      'job_title' => '`job_title` varchar(255)',
      'gender_id' => '`gender_id` varchar(255)',
      'birth_date' => '`birth_date` varchar(32)',
      'is_deceased' => '`is_deceased` varchar(64)',
      'deceased_date' => '`deceased_date` varchar(32)',
      'household_name' => '`household_name` varchar(128)',
      'organization_name' => '`organization_name` varchar(128)',
      'sic_code' => '`sic_code` varchar(8)',
      'user_unique_id' => '`user_unique_id` varchar(255)',
      'current_employer_id' => '`current_employer_id` varchar(64)',
      'contact_is_deleted' => '`contact_is_deleted` varchar(64)',
      'created_date' => '`created_date` varchar(32)',
      'modified_date' => '`modified_date` varchar(32)',
      'addressee' => '`addressee` varchar(255)',
      'email_greeting' => '`email_greeting` varchar(255)',
      'postal_greeting' => '`postal_greeting` varchar(255)',
      'current_employer' => '`current_employer` varchar(255)',
      'location_type' => '`location_type` varchar(255)',
      'street_address' => '`street_address` varchar(96)',
      'street_number' => '`street_number` varchar(64)',
      'street_number_suffix' => '`street_number_suffix` varchar(8)',
      'street_name' => '`street_name` varchar(64)',
      'street_unit' => '`street_unit` varchar(16)',
      'supplemental_address_1' => '`supplemental_address_1` varchar(96)',
      'supplemental_address_2' => '`supplemental_address_2` varchar(96)',
      'supplemental_address_3' => '`supplemental_address_3` varchar(96)',
      'city' => '`city` varchar(64)',
      'postal_code_suffix' => '`postal_code_suffix` varchar(12)',
      'postal_code' => '`postal_code` varchar(64)',
      'geo_code_1' => '`geo_code_1` varchar(32)',
      'geo_code_2' => '`geo_code_2` varchar(32)',
      'address_name' => '`address_name` varchar(255)',
      'master_id' => '`master_id` varchar(128)',
      'county' => '`county` varchar(64)',
      'state_province' => '`state_province` varchar(64)',
      'country' => '`country` varchar(64)',
      'phone' => '`phone` varchar(32)',
      'phone_ext' => '`phone_ext` varchar(16)',
      'email' => '`email` varchar(254)',
      'on_hold' => '`on_hold` varchar(64)',
      'is_bulkmail' => '`is_bulkmail` varchar(64)',
      'signature_text' => '`signature_text` longtext',
      'signature_html' => '`signature_html` longtext',
      'im_provider' => '`im_provider` varchar(255)',
      'im' => '`im` varchar(64)',
      'openid' => '`openid` varchar(255)',
      'world_region' => '`world_region` varchar(128)',
      'url' => '`url` varchar(2048)',
      'phone_type_id' => '`phone_type_id` varchar(64)',
      'financial_type' => '`financial_type` varchar(255)',
      'contribution_source' => '`contribution_source` varchar(255)',
      'receive_date' => '`receive_date` varchar(32)',
      'thankyou_date' => '`thankyou_date` varchar(32)',
      'contribution_cancel_date' => '`contribution_cancel_date` varchar(32)',
      'total_amount' => '`total_amount` varchar(32)',
      'accounting_code' => '`accounting_code` varchar(64)',
      'payment_instrument' => '`payment_instrument` varchar(255)',
      'payment_instrument_id' => '`payment_instrument_id` varchar(64)',
      'contribution_check_number' => '`contribution_check_number` varchar(255)',
      'non_deductible_amount' => '`non_deductible_amount` varchar(32)',
      'fee_amount' => '`fee_amount` varchar(32)',
      'net_amount' => '`net_amount` varchar(32)',
      'trxn_id' => '`trxn_id` varchar(255)',
      'invoice_id' => '`invoice_id` varchar(255)',
      'invoice_number' => '`invoice_number` varchar(255)',
      'currency' => '`currency` varchar(3)',
      'cancel_reason' => '`cancel_reason` longtext',
      'receipt_date' => '`receipt_date` varchar(32)',
      'is_test' => '`is_test` varchar(64)',
      'is_pay_later' => '`is_pay_later` varchar(64)',
      'contribution_status' => '`contribution_status` varchar(255)',
      'contribution_recur_id' => '`contribution_recur_id` varchar(64)',
      'amount_level' => '`amount_level` longtext',
      'contribution_note' => '`contribution_note` longtext',
      'contribution_batch' => '`contribution_batch` text',
      'contribution_campaign_title' => '`contribution_campaign_title` varchar(255)',
      'contribution_campaign_id' => '`contribution_campaign_id` varchar(64)',
      'contribution_soft_credit_name' => '`contribution_soft_credit_name` varchar(255)',
      'contribution_soft_credit_amount' => '`contribution_soft_credit_amount` varchar(32)',
      'contribution_soft_credit_type' => '`contribution_soft_credit_type` varchar(255)',
      'contribution_soft_credit_contact_id' => '`contribution_soft_credit_contact_id` varchar(64)',
      'contribution_soft_credit_contribution_id' => '`contribution_soft_credit_contribution_id` varchar(64)',
    ];
  }

  /**
   * Get pledge sql columns.
   *
   * @return array
   */
  public static function getPledgeSqlColumns(): array {
    return [
      'pledge_id' => '`pledge_id` varchar(64)',
      'pledge_amount' => '`pledge_amount` varchar(32)',
      'pledge_total_paid' => '`pledge_total_paid` text',
      'pledge_create_date' => '`pledge_create_date` varchar(32)',
      'pledge_start_date' => '`pledge_start_date` varchar(32)',
      'pledge_next_pay_date' => '`pledge_next_pay_date` text',
      'pledge_next_pay_amount' => '`pledge_next_pay_amount` text',
      'pledge_status' => '`pledge_status` varchar(255)',
      'pledge_is_test' => '`pledge_is_test` varchar(64)',
      'pledge_contribution_page_id' => '`pledge_contribution_page_id` varchar(64)',
      'pledge_financial_type' => '`pledge_financial_type` text',
      'pledge_frequency_interval' => '`pledge_frequency_interval` varchar(64)',
      'pledge_frequency_unit' => '`pledge_frequency_unit` varchar(255)',
      'pledge_currency' => '`pledge_currency` text',
      'pledge_campaign_id' => '`pledge_campaign_id` varchar(64)',
      'pledge_balance_amount' => '`pledge_balance_amount` text',
      'pledge_payment_id' => '`pledge_payment_id` varchar(64)',
      'pledge_payment_scheduled_amount' => '`pledge_payment_scheduled_amount` varchar(32)',
      'pledge_payment_scheduled_date' => '`pledge_payment_scheduled_date` varchar(32)',
      'pledge_payment_paid_amount' => '`pledge_payment_paid_amount` text',
      'pledge_payment_paid_date' => '`pledge_payment_paid_date` text',
      'pledge_payment_reminder_date' => '`pledge_payment_reminder_date` varchar(32)',
      'pledge_payment_reminder_count' => '`pledge_payment_reminder_count` varchar(64)',
      'pledge_payment_status' => '`pledge_payment_status` varchar(255)',
    ];
  }

  /**
   * Get membership sql columns.
   *
   * @return array
   */
  public static function getMembershipSqlColumns(): array {
    return [
      'membership_type' => '`membership_type` varchar(128)',
      'member_is_test' => '`member_is_test` varchar(64)',
      'member_is_pay_later' => '`member_is_pay_later` varchar(64)',
      'membership_join_date' => '`membership_join_date` varchar(32)',
      'membership_start_date' => '`membership_start_date` varchar(32)',
      'membership_end_date' => '`membership_end_date` varchar(32)',
      'membership_source' => '`membership_source` varchar(128)',
      'membership_status' => '`membership_status` varchar(255)',
      'membership_id' => '`membership_id` varchar(64)',
      'owner_membership_id' => '`owner_membership_id` varchar(64)',
      'max_related' => '`max_related` varchar(64)',
      'membership_recur_id' => '`membership_recur_id` varchar(64)',
      'member_campaign_id' => '`member_campaign_id` varchar(64)',
      'member_is_override' => '`member_is_override` varchar(64)',
    ];
  }

  /**
   * Change our location types so we have some edge cases in the mix.
   *
   * - a space in the name
   * - name differs from label
   * - non-anglo char in the label (not valid in the name).
   */
  protected function diversifyLocationTypes(): void {
    $this->locationTypes['Main'] = $this->callAPISuccess('Location_type', 'get', [
      'name' => 'Main',
      'return' => 'id',
      'api.LocationType.Create' => ['display_name' => 'Méin'],
    ]);
    $this->locationTypes['Whare Kai'] = $this->callAPISuccess('Location_type', 'create', [
      'name' => 'Whare Kai',
      'display_name' => 'Whare Kai',
    ]);
  }

  /**
   * Test export components.
   *
   * Tests the exportComponents function with the provided parameters.
   *
   * This exportComponents will export a csv but it will also throw a prematureExitException
   * which we catch & grab the processor from.
   *
   * $this->processor is set to the export processor.
   *
   * @param $params
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\Exception
   */
  protected function doExportTest($params): void {
    $fields = $params['fields'] ?? [];
    $fieldDefaults = ['contact_type' => 'Individual', 'phone_type_id' => NULL, 'location_type_id' => NULL];
    foreach ($fields as $key => $field) {
      $fields[$key] = array_merge($fieldDefaults, $field);
    }
    $this->startCapturingOutput();
    try {
      $exportMode = ($params['exportMode'] ?? CRM_Export_Form_Select::CONTACT_EXPORT);
      $ids = $params['ids'] ?? ($exportMode === CRM_Export_Form_Select::CONTACT_EXPORT ? $this->contactIDs : []);
      $defaultClause = (empty($ids) ? NULL : 'contact_a.id IN (' . implode(',', $ids) . ')');
      CRM_Export_BAO_Export::exportComponents(
        $params['selectAll'] ?? !$fields,
        $ids,
        $params['params'] ?? [],
        $params['order'] ?? NULL,
        $fields,
        $params['moreReturnProperties'] ?? NULL,
        $exportMode,
        $params['componentClause'] ?? $defaultClause,
        $params['componentTable'] ?? NULL,
        $params['mergeSameAddress'] ?? FALSE,
        $params['mergeSameHousehold'] ?? FALSE,
        $params['exportParams'] ?? []
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
   * Assert that the received array matches the expected, ignoring time sensitive fields.
   *
   * @param array $expected
   * @param array $row
   * @param array $alternatives
   */
  protected function assertExpectedOutput(array $expected, array $row, array $alternatives = []): void {
    $variableFields = ['Created Date', 'Modified Date', 'Contact Hash'];
    foreach ($expected as $key => $value) {
      if (in_array($key, $variableFields)) {
        $this->assertNotEmpty($row[$key]);
      }
      elseif (array_key_exists($key, $alternatives)) {
        $this->assertContains($row[$key], $alternatives[$key]);
      }
      else {
        $this->assertEquals($value, $row[$key], 'mismatch on ' . $key);
      }
    }
  }

  /**
   * Test get preview function on export processor.
   */
  public function testExportGetPreview(): void {
    $this->setUpContactExportData();
    $fields = [
      ['contact_type' => 'Individual', 'name' => 'first_name'],
      ['contact_type' => 'Individual', 'name' => 'last_name'],
      ['contact_type' => 'Individual', 'name' => 'street_address', 'location_type_id' => 1],
      ['contact_type' => 'Individual', 'name' => 'city', 'location_type_id' => 1],
      ['contact_type' => 'Individual', 'name' => 'country', 'location_type_id' => 1],
      ['contact_type' => 'Individual', 'name' => 'email', 'location_type_id' => 1],
    ];
    $processor = new CRM_Export_BAO_ExportProcessor(FALSE, $fields,
    'AND');
    $processor->setComponentClause('contact_a.id IN (' . implode(',', $this->contactIDs) . ')');
    $result = $processor->getPreview(2);
    $this->assertEquals([
      [
        'first_name' => 'Anthony',
        'last_name' => 'Anderson',
        'Home-street_address' => 'Ambachtstraat 23',
        'Home-city' => 'Brummen',
        'Home-country' => 'Netherlands',
        'Home-email' => 'home@example.com',
      ],
      [
        'first_name' => 'Joe',
        'last_name' => 'Miller',
        'Home-street_address' => 'Ambachtstraat 23',
        'Home-city' => 'Brummen',
        'Home-country' => 'Netherlands',
        'Home-email' => 'joe_miller@civicrm.org',
      ],
    ], $result);
  }

  /**
   * Set up contacts which will be merged with the same address option.
   */
  protected function setUpContactSameAddressExportData(): void {
    $this->setUpContactExportData();
    $this->contactIDs[] = $contact3 = $this->individualCreate(['first_name' => 'Sarah', 'last_name' => 'Smith', 'prefix_id' => 'Dr.']);
    // Create address for contact A.
    $params = [
      'contact_id' => $contact3,
      'location_type_id' => 'Home',
      'street_address' => 'A different address',
      'postal_code' => '6971 BN',
      'country_id' => '1152',
      'city' => 'Brummen',
      'is_primary' => 1,
    ];
    $this->callAPISuccess('address', 'create', $params);
  }

  /**
   * Test for single select Autocomplete custom field.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\UnableToProcessCsv
   */
  public function testSingleAndMultiSelectAutoComplete(): void {
    $customGroupId = $this->customGroupCreate([
      'extends' => 'Individual',
    ])['id'];
    $colors = ['Y' => 'Yellow', 'G' => 'Green', 'R' => 'Red'];
    $fieldId1 = $this->createAutoCompleteCustomField([
      'custom_group_id' => $customGroupId,
      'option_values' => $colors,
      'label' => 'Autocomplete Color',
    ])['id'];
    $fieldId2 = $this->createAutoCompleteCustomField([
      'custom_group_id' => $customGroupId,
      'option_values' => $colors,
      'label' => 'Autocomplete Colors',
      'serialize' => 1,
    ])['id'];
    $contactId = $this->individualCreate([
      "custom_$fieldId1" => 'Y',
      "custom_$fieldId2" => ['Y', 'G'],
    ]);
    $selectedFields = [
      ['name' => 'contact_id'],
      ['name' => "custom_{$fieldId1}"],
      ['name' => "custom_{$fieldId2}"],
    ];

    $this->doExportTest([
      'ids' => [$contactId],
      'fields' => $selectedFields,
      'exportMode' => CRM_Export_Form_Select::CONTACT_EXPORT,
    ]);
    $row = $this->csv->fetchOne();
    $this->assertEquals('Yellow', $row['Autocomplete Color']);
    $this->assertEquals('Yellow, Green', $row['Autocomplete Colors']);
  }

}
