<?php

/**
 * Class CRM_Core_DAOTest
 * @group headless
 */
class CRM_Export_BAO_ExportTest extends CiviUnitTestCase {

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

  public function tearDown() {
    $this->quickCleanup([
      'civicrm_contact',
      'civicrm_email',
      'civicrm_address',
      'civicrm_relationship',
      'civicrm_membership',
      'civicrm_case',
      'civicrm_case_contact',
      'civicrm_case_activity',
    ]);
    $this->quickCleanUpFinancialEntities();
    if (!empty($this->locationTypes)) {
      $this->callAPISuccess('LocationType', 'delete', ['id' => $this->locationTypes['Whare Kai']['id']]);
      $this->callAPISuccess('LocationType', 'create', ['id' => $this->locationTypes['Main']['id'], 'name' => 'Main']);
    }
    parent::tearDown();
  }

  /**
   * Basic test to ensure the exportComponents function completes without error.
   */
  public function testExportComponentsNull() {
    list($tableName) = CRM_Export_BAO_Export::exportComponents(
      TRUE,
      array(),
      array(),
      NULL,
      NULL,
      NULL,
      CRM_Export_Form_Select::CONTACT_EXPORT,
      NULL,
      NULL,
      FALSE,
      FALSE,
      array(
        'exportOption' => 1,
        'suppress_csv_for_testing' => TRUE,
      )
    );

    // delete the export temp table and component table
    $sql = "DROP TABLE IF EXISTS {$tableName}";
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Basic test to ensure the exportComponents function can export selected fields for contribution.
   */
  public function testExportComponentsContribution() {
    $this->setUpContributionExportData();
    $selectedFields = array(
      array('Individual', 'first_name'),
      array('Individual', 'last_name'),
      array('Contribution', 'receive_date'),
      array('Contribution', 'contribution_source'),
      array('Individual', 'street_address', 1),
      array('Individual', 'city', 1),
      array('Individual', 'country', 1),
      array('Individual', 'email', 1),
      array('Contribution', 'trxn_id'),
    );

    list($tableName) = CRM_Export_BAO_Export::exportComponents(
      TRUE,
      $this->contributionIDs,
      array(),
      'receive_date desc',
      $selectedFields,
      NULL,
      CRM_Export_Form_Select::CONTRIBUTE_EXPORT,
      'civicrm_contribution.id IN ( ' . implode(',', $this->contributionIDs) . ')',
      NULL,
      FALSE,
      FALSE,
      array(
        'exportOption' => CRM_Export_Form_Select::CONTRIBUTE_EXPORT,
        'suppress_csv_for_testing' => TRUE,
      )
    );

    // delete the export temp table and component table
    $sql = "DROP TABLE IF EXISTS {$tableName}";
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Basic test to ensure the exportComponents function can export selected fields for contribution.
   */
  public function testExportComponentsMembership() {
    $this->setUpMembershipExportData();
    list($tableName) = CRM_Export_BAO_Export::exportComponents(
      TRUE,
      $this->membershipIDs,
      [],
      NULL,
      NULL,
      NULL,
      CRM_Export_Form_Select::MEMBER_EXPORT,
      'civicrm_membership.id IN ( ' . implode(',', $this->membershipIDs) . ')',
      NULL,
      FALSE,
      FALSE,
      array(
        'exportOption' => CRM_Export_Form_Select::MEMBER_EXPORT,
        'suppress_csv_for_testing' => TRUE,
      )
    );

    $dao = CRM_Core_DAO::executeQuery('SELECT * from ' . $tableName);
    $dao->fetch();
    $this->assertEquals('100.00', $dao->componentpaymentfield_total_amount);
    $this->assertEquals('Completed', $dao->componentpaymentfield_contribution_status);
    $this->assertEquals('Credit Card', $dao->componentpaymentfield_payment_instrument);
    $this->assertEquals(1, $dao->N);

    // delete the export temp table and component table
    $sql = "DROP TABLE IF EXISTS {$tableName}";
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Basic test to ensure the exportComponents function can export selected fields for contribution.
   */
  public function testExportComponentsActivity() {
    $this->setUpActivityExportData();
    $selectedFields = array(
      array('Individual', 'display_name'),
      array('Individual', '5_a_b', 'display_name'),
    );

    list($tableName) = CRM_Export_BAO_Export::exportComponents(
      FALSE,
      $this->activityIDs,
      array(),
      '`activity_date_time` desc',
      $selectedFields,
      NULL,
      CRM_Export_Form_Select::ACTIVITY_EXPORT,
      'civicrm_activity.id IN ( ' . implode(',', $this->activityIDs) . ')',
      NULL,
      FALSE,
      FALSE,
      array(
        'exportOption' => CRM_Export_Form_Select::ACTIVITY_EXPORT,
        'suppress_csv_for_testing' => TRUE,
      )
    );

    // delete the export temp table and component table
    $sql = "DROP TABLE IF EXISTS {$tableName}";
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Test the function that extracts the arrays used to structure the output.
   *
   * The keys in the output fields array should by matched by field aliases in the sql query (with
   * exceptions of course - currently country is one - although maybe a future refactor can change that!).
   *
   * We are trying to move towards simpler processing in the per row iteration as that may be
   * repeated 100,000 times and in general we should simply be able to match the query fields to
   * our expected rows & do a little pseudoconstant mapping.
   */
  public function testGetExportStructureArrays() {
    // This is how return properties are formatted internally within the function for passing to the BAO query.
    $returnProperties = array(
      'first_name' => 1,
      'last_name' => 1,
      'receive_date' => 1,
      'contribution_source' => 1,
      'location' => array(
        'Home' => array(
          'street_address' => 1,
          'city' => 1,
          'country' => 1,
          'email' => 1,
          'im-1' => 1,
          'im_provider' => 1,
          'phone-1' => 1,
        ),
      ),
      'phone' => 1,
      'trxn_id' => 1,
      'contribution_id' => 1,
    );

    $contactRelationshipTypes = CRM_Contact_BAO_Relationship::getContactRelationshipType(
      NULL,
      NULL,
      NULL,
      NULL,
      TRUE,
      'name',
      FALSE
    );

    $query = new CRM_Contact_BAO_Query(array(), $returnProperties, NULL,
      FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CONTRIBUTE,
      FALSE, TRUE, TRUE, NULL, 'AND'
    );

    list($select) = $query->query();
    $pattern = '/as `?([^`,]*)/';
    $queryFieldAliases = array();
    preg_match_all($pattern, $select, $queryFieldAliases, PREG_PATTERN_ORDER);
    $processor = new CRM_Export_BAO_ExportProcessor(CRM_Contact_BAO_Query::MODE_CONTRIBUTE, NULL, 'AND');
    $processor->setQueryFields($query->_fields);

    list($outputFields) = CRM_Export_BAO_Export::getExportStructureArrays($returnProperties, $processor, $contactRelationshipTypes, '');
    foreach (array_keys($outputFields) as $fieldAlias) {
      if ($fieldAlias == 'Home-country') {
        $this->assertTrue(in_array($fieldAlias . '_id', $queryFieldAliases[1]), 'Country is subject to some funky translate so we make sure country id is present');
      }
      else {
        $this->assertTrue(in_array($fieldAlias, $queryFieldAliases[1]), 'looking for field ' . $fieldAlias . ' in generaly the alias fields need to match the outputfields');
      }
    }

  }

  /**
   * Set up some data for us to do testing on.
   */
  public function setUpContributionExportData() {
    $this->setUpContactExportData();
    $this->contributionIDs[] = $this->contributionCreate(array('contact_id' => $this->contactIDs[0], 'trxn_id' => 'null', 'invoice_id' => 'null'));
    $this->contributionIDs[] = $this->contributionCreate(array('contact_id' => $this->contactIDs[1], 'trxn_id' => 'null', 'invoice_id' => 'null'));
  }

  /**
   * Set up some data for us to do testing on.
   */
  public function setUpMembershipExportData() {
    $this->setUpContactExportData();
    // Create an extra so we don't get false passes due to 1
    $this->contactMembershipCreate(['contact_id' => $this->contactIDs[0]]);
    $this->membershipIDs[] = $this->contactMembershipCreate(['contact_id' => $this->contactIDs[0]]);
    $this->setUpContributionExportData();
    $this->callAPISuccess('membership_payment', 'create', array(
      'contribution_id' => $this->contributionIDs[0],
      'membership_id' => $this->membershipIDs[0],
    ));
    $this->callAPISuccess('LineItem', 'get', [
      'entity_table' => 'civicrm_membership',
      'membership_id' => $this->membershipIDs[0],
      'api.LineItem.create' => ['contribution_id' => $this->contributionIDs[0]],
    ]);
  }

  /**
   * Set up data to test case export.
   */
  public function setupCaseExportData() {
    $contactID1 = $this->individualCreate();
    $contactID2 = $this->individualCreate(array(), 1);

    $case = $this->callAPISuccess('case', 'create', array(
      'case_type_id' => 1,
      'subject' => 'blah',
      'contact_id' => $contactID1,
    ));
    $this->callAPISuccess('CaseContact', 'create', [
      'case_id' => $case['id'],
      'contact_id' => $contactID2,
    ]);
  }

  /**
   * Set up some data for us to do testing on.
   */
  public function setUpActivityExportData() {
    $this->setUpContactExportData();
    $this->activityIDs[] = $this->activityCreate(array('contact_id' => $this->contactIDs[0]))['id'];
  }

  /**
   * Set up some data for us to do testing on.
   */
  public function setUpContactExportData() {
    $this->contactIDs[] = $contactA = $this->individualCreate(['gender_id' => 'Female']);
    // Create address for contact A.
    $params = array(
      'contact_id' => $contactA,
      'location_type_id' => 'Home',
      'street_address' => 'Ambachtstraat 23',
      'postal_code' => '6971 BN',
      'country_id' => '1152',
      'city' => 'Brummen',
      'is_primary' => 1,
    );
    $result = $this->callAPISuccess('address', 'create', $params);
    $addressId = $result['id'];

    $this->callAPISuccess('email', 'create', array(
      'id' => $this->callAPISuccessGetValue('Email', ['contact_id' => $params['contact_id'], 'return' => 'id']),
      'location_type_id' => 'Home',
      'email' => 'home@example.com',
      'is_primary' => 1,
    ));
    $this->callAPISuccess('email', 'create', array('contact_id' => $params['contact_id'], 'location_type_id' => 'Work', 'email' => 'work@example.com', 'is_primary' => 0));

    $params['is_primary'] = 0;
    $params['location_type_id'] = 'Work';
    $this->callAPISuccess('address', 'create', $params);
    $this->contactIDs[] = $contactB = $this->individualCreate();

    $this->callAPISuccess('address', 'create', array(
      'contact_id' => $contactB,
      'location_type_id' => "Home",
      'master_id' => $addressId,
    ));
    $this->masterAddressID = $addressId;

  }

  /**
   * Test variants of primary address exporting.
   *
   * @param int $isPrimaryOnly
   *
   * @dataProvider getPrimarySearchOptions
   */
  public function testExportPrimaryAddress($isPrimaryOnly) {
    \Civi::settings()->set('searchPrimaryDetailsOnly', $isPrimaryOnly);
    $this->setUpContactExportData();

    $selectedFields = [['Individual', 'email', ' '], ['Individual', 'email', '1'], ['Individual', 'email', '2']];
    list($tableName) = CRM_Export_BAO_Export::exportComponents(
      TRUE,
      [],
      [['email', 'LIKE', 'c', 0, 1]],
      NULL,
      $selectedFields,
      NULL,
      CRM_Export_Form_Select::CONTACT_EXPORT,
      "contact_a.id IN ({$this->contactIDs[0]}, {$this->contactIDs[1]})",
      NULL,
      FALSE,
      FALSE,
      array(
        'exportOption' => CRM_Export_Form_Select::CONTACT_EXPORT,
        'suppress_csv_for_testing' => TRUE,
      )
    );

    $dao = CRM_Core_DAO::executeQuery('SELECT * from ' . $tableName);
    $dao->fetch();
    $this->assertEquals('home@example.com', $dao->email);
    $this->assertEquals('work@example.com', $dao->work_email);
    $this->assertEquals('home@example.com', $dao->home_email);
    $this->assertEquals(2, $dao->N);
    \Civi::settings()->set('searchPrimaryDetailsOnly', FALSE);
  }

  /**
   * Get the options for the primary search setting field.
   * @return array
   */
  public function getPrimarySearchOptions() {
    return [[TRUE], [FALSE]];
  }

  /**
   * Test that when exporting a pseudoField it is reset for NULL entries.
   *
   * ie. we have a contact WITH a gender & one without - make sure the latter one
   * does NOT retain the gender of the former.
   */
  public function testExportPseudoField() {
    $this->setUpContactExportData();
    $selectedFields = [['Individual', 'gender_id']];
    list($tableName, $sqlColumns) = $this->doExport($selectedFields, $this->contactIDs);
    $this->assertEquals('Female,', CRM_Core_DAO::singleValueQuery("SELECT GROUP_CONCAT(gender_id) FROM {$tableName}"));
  }

  /**
   * Test that when exporting a pseudoField it is reset for NULL entries.
   *
   * This is specific to the example in CRM-14398
   */
  public function testExportPseudoFieldCampaign() {
    $this->setUpContributionExportData();
    $campaign = $this->callAPISuccess('Campaign', 'create', ['title' => 'Big campaign']);
    $this->callAPISuccess('Contribution', 'create', ['campaign_id' => 'Big_campaign', 'id' => $this->contributionIDs[0]]);
    $selectedFields = [['Individual', 'gender_id'], ['Contribution', 'contribution_campaign_title']];
    list($tableName, $sqlColumns) = CRM_Export_BAO_Export::exportComponents(
      TRUE,
      $this->contactIDs[1],
      array(),
      NULL,
      $selectedFields,
      NULL,
      CRM_Export_Form_Select::CONTRIBUTE_EXPORT,
      "contact_a.id IN (" . implode(",", $this->contactIDs) . ")",
      NULL,
      FALSE,
      FALSE,
      array(
        'exportOption' => CRM_Export_Form_Select::CONTACT_EXPORT,
        'suppress_csv_for_testing' => TRUE,
      )
    );
    $this->assertEquals('Big campaign,', CRM_Core_DAO::singleValueQuery("SELECT GROUP_CONCAT(contribution_campaign_title) FROM {$tableName}"));
  }

  /**
   * Test exporting relationships.
   */
  public function testExportRelationships() {
    $organization1 = $this->organizationCreate(['organization_name' => 'Org 1', 'legal_name' => 'pretty legal', 'contact_source' => 'friend who took a law paper once']);
    $organization2 = $this->organizationCreate(['organization_name' => 'Org 2', 'legal_name' => 'well dodgey']);
    $contact1 = $this->individualCreate(['employer_id' => $organization1, 'first_name' => 'one']);
    $contact2 = $this->individualCreate(['employer_id' => $organization2, 'first_name' => 'one']);
    $employerRelationshipTypeID = $this->callAPISuccessGetValue('RelationshipType', ['return' => 'id', 'label_a_b' => 'Employee of']);
    $selectedFields = [
      ['Individual', 'first_name', ''],
      ['Individual', $employerRelationshipTypeID . '_a_b', 'organization_name', ''],
      ['Individual', $employerRelationshipTypeID . '_a_b', 'legal_name', ''],
      ['Individual', $employerRelationshipTypeID . '_a_b', 'contact_source', ''],
    ];
    list($tableName, $sqlColumns, $headerRows) = CRM_Export_BAO_Export::exportComponents(
      FALSE,
      [$contact1, $contact2],
      [],
      NULL,
      $selectedFields,
      NULL,
      CRM_Export_Form_Select::CONTACT_EXPORT,
      "contact_a.id IN ( $contact1, $contact2 )",
      NULL,
      FALSE,
      FALSE,
      [
        'exportOption' => CRM_Export_Form_Select::CONTACT_EXPORT,
        'suppress_csv_for_testing' => TRUE,
      ]
    );

    $dao = CRM_Core_DAO::executeQuery("SELECT * FROM {$tableName}");
    $dao->fetch();
    $this->assertEquals('one', $dao->first_name);
    $this->assertEquals('Org 1', $dao->{$employerRelationshipTypeID . '_a_b_organization_name'});
    $this->assertEquals('pretty legal', $dao->{$employerRelationshipTypeID . '_a_b_legal_name'});
    $this->assertEquals('friend who took a law paper once', $dao->{$employerRelationshipTypeID . '_a_b_contact_source'});

    $dao->fetch();
    $this->assertEquals('Org 2', $dao->{$employerRelationshipTypeID . '_a_b_organization_name'});
    $this->assertEquals('well dodgey', $dao->{$employerRelationshipTypeID . '_a_b_legal_name'});

    $this->assertEquals([
      0 => 'First Name',
      1 => 'Employee of-Organization Name',
      2 => 'Employee of-Legal Name',
      3 => 'Employee of-Contact Source',
    ], $headerRows);
  }

  /**
   * Test exporting relationships.
   *
   * This is to ensure that CRM-13995 remains fixed.
   */
  public function testExportRelationshipsMergeToHousehold() {
    list($householdID, $houseHoldTypeID) = $this->setUpHousehold();

    $selectedFields = [
      ['Individual', $houseHoldTypeID . '_a_b', 'state_province', ''],
      ['Individual', $houseHoldTypeID . '_a_b', 'city', ''],
      ['Individual', 'city', ''],
      ['Individual', 'state_province', ''],
      ['Individual', 'contact_source', ''],
    ];
    list($tableName, $sqlColumns, $headerRows) = CRM_Export_BAO_Export::exportComponents(
      FALSE,
      $this->contactIDs,
      [],
      NULL,
      $selectedFields,
      NULL,
      CRM_Export_Form_Select::CONTACT_EXPORT,
      "contact_a.id IN (" . implode(",", $this->contactIDs) . ")",
      NULL,
      FALSE,
      TRUE,
      [
        'exportOption' => CRM_Export_Form_Select::CONTACT_EXPORT,
        'suppress_csv_for_testing' => TRUE,
      ]
    );
    $dao = CRM_Core_DAO::executeQuery("SELECT * FROM {$tableName}");
    while ($dao->fetch()) {
      $this->assertEquals('Portland', $dao->city);
      $this->assertEquals('ME', $dao->state_province);
      $this->assertEquals($householdID, $dao->civicrm_primary_id);
      $this->assertEquals($householdID, $dao->civicrm_primary_id);
      $this->assertEquals('household sauce', $dao->contact_source);
    }

    $this->assertEquals([
      0 => 'City',
      1 => 'State',
      2 => 'Contact Source',
      3 => 'Household ID',
    ], $headerRows);
    $this->assertEquals(
      [
        'city' => 'city varchar(64)',
        'state_province' => 'state_province varchar(64)',
        'civicrm_primary_id' => 'civicrm_primary_id varchar(16)',
        'contact_source' => 'contact_source varchar(255)',
      ], $sqlColumns);
  }

  /**
   * Test exporting relationships.
   */
  public function testExportRelationshipsMergeToHouseholdAllFields() {
    list($householdID) = $this->setUpHousehold();
    list($tableName) = CRM_Export_BAO_Export::exportComponents(
      FALSE,
      $this->contactIDs,
      [],
      NULL,
      NULL,
      NULL,
      CRM_Export_Form_Select::CONTACT_EXPORT,
      "contact_a.id IN (" . implode(",", $this->contactIDs) . ")",
      NULL,
      FALSE,
      TRUE,
      [
        'exportOption' => CRM_Export_Form_Select::CONTACT_EXPORT,
        'suppress_csv_for_testing' => TRUE,
      ]
    );
    $dao = CRM_Core_DAO::executeQuery("SELECT * FROM {$tableName}");
    while ($dao->fetch()) {
      $this->assertEquals('Unit Test household', $dao->display_name);
      $this->assertEquals('Portland', $dao->city);
      $this->assertEquals('ME', $dao->state_province);
      $this->assertEquals($householdID, $dao->civicrm_primary_id);
      $this->assertEquals($householdID, $dao->civicrm_primary_id);
      $this->assertEquals('Unit Test household', $dao->addressee);
      $this->assertEquals(1, $dao->N);
    }
  }

  /**
   * Test master_address_id field.
   */
  public function testExportCustomData() {
    $this->setUpContactExportData();

    $customData = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, 'ContactTest.php');

    $this->callAPISuccess('Contact', 'create', [
      'id' => $this->contactIDs[1],
      'custom_' . $customData['custom_field_id'] => 'BlahdeBlah',
      'api.Address.create' => ['location_type_id' => 'Billing', 'city' => 'Waipu'],
    ]);
    $selectedFields = [
      ['Individual', 'city', CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Address', 'location_type_id', 'Billing')],
      ['Individual', 'custom_1'],
    ];

    list($tableName, $sqlColumns) = $this->doExport($selectedFields, $this->contactIDs[1]);
    $this->assertEquals([
      'billing_city' => 'billing_city varchar(64)',
      'custom_1' => 'custom_1 varchar(255)',
    ], $sqlColumns);

    $dao = CRM_Core_DAO::executeQuery('SELECT * FROM ' . $tableName);
    while ($dao->fetch()) {
      $this->assertEquals('BlahdeBlah', $dao->custom_1);
      $this->assertEquals('Waipu', $dao->billing_city);
    }
  }

  /**
   * Attempt to do a fairly full export of location data.
   */
  public function testExportIMData() {
    // Use default providers.
    $providers = ['AIM', 'GTalk', 'Jabber', 'MSN', 'Skype', 'Yahoo'];
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
      $this->contactIDs[3] => ['label' => 'Employee of']
    ];

    foreach ($relationships as $contactID => $relationshipType) {
      $relationshipTypeID = $this->callAPISuccess('RelationshipType', 'getvalue', ['label_a_b' => $relationshipType['label'], 'return' => 'id']);
      $result = $this->callAPISuccess('Relationship', 'create', [
        'contact_id_a' => $this->contactIDs[0],
        'relationship_type_id' => $relationshipTypeID,
        'contact_id_b' => $contactID
      ]);
      $relationships[$contactID]['id'] = $result['id'];
      $relationships[$contactID]['relationship_type_id'] = $relationshipTypeID;
    }

    $fields = [['Individual', 'contact_id']];
    // ' ' denotes primary location type.
    foreach (array_keys(array_merge($locationTypes, [' ' => ['Primary']])) as $locationType) {
      $fields[] = [
        'Individual',
        'im_provider',
        CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_IM', 'location_type_id', $locationType),
      ];
      foreach ($relationships as $contactID => $relationship) {
        $fields[] = [
          'Individual',
          $relationship['relationship_type_id'] . '_a_b',
          'im_provider',
          CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_IM', 'location_type_id', $locationType),
        ];
      }
      foreach ($providers as $provider) {
        $fields[] = [
          'Individual',
          'im',
          CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_IM', 'location_type_id', $locationType),
          CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_IM', 'provider_id', $provider),
        ];
        foreach ($relationships as $contactID => $relationship) {
          $fields[] = [
            'Individual',
            $relationship['relationship_type_id'] . '_a_b',
            'im',
            CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_IM', 'location_type_id', $locationType),
            CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_IM', 'provider_id', $provider),
          ];
        }
      }
    }
    list($tableName, $sqlColumns) = $this->doExport($fields, $this->contactIDs[0]);

    $dao = CRM_Core_DAO::executeQuery('SELECT * FROM ' . $tableName);
    while ($dao->fetch()) {
      $id = $dao->contact_id;
      $this->assertEquals('AIM', $dao->billing_im_provider);
      $this->assertEquals('BillingJabber' . $id, $dao->billing_im_screen_name_jabber);
      $this->assertEquals('BillingSkype' . $id, $dao->billing_im_screen_name_skype);
      foreach ($relationships as $relatedContactID => $relationship) {
        $relationshipString = $field = $relationship['relationship_type_id'] . '_a_b';
        $field = $relationshipString . '_billing_im_screen_name_yahoo';
        $this->assertEquals('BillingYahoo' . $relatedContactID, $dao->$field);
        // @todo efforts to output 'im_provider' for related contacts seem to be giving a blank field.
      }
    }

    $this->assertEquals([
      'billing_im_provider' => 'billing_im_provider text',
      'billing_im_screen_name' => 'billing_im_screen_name varchar(64)',
      'billing_im_screen_name_jabber' => 'billing_im_screen_name_jabber varchar(64)',
      'billing_im_screen_name_skype' => 'billing_im_screen_name_skype varchar(64)',
      'billing_im_screen_name_yahoo' => 'billing_im_screen_name_yahoo varchar(64)',
      'home_im_provider' => 'home_im_provider text',
      'home_im_screen_name' => 'home_im_screen_name varchar(64)',
      'home_im_screen_name_jabber' => 'home_im_screen_name_jabber varchar(64)',
      'home_im_screen_name_skype' => 'home_im_screen_name_skype varchar(64)',
      'home_im_screen_name_yahoo' => 'home_im_screen_name_yahoo varchar(64)',
      'main_im_provider' => 'main_im_provider text',
      'main_im_screen_name' => 'main_im_screen_name varchar(64)',
      'main_im_screen_name_jabber' => 'main_im_screen_name_jabber varchar(64)',
      'main_im_screen_name_skype' => 'main_im_screen_name_skype varchar(64)',
      'main_im_screen_name_yahoo' => 'main_im_screen_name_yahoo varchar(64)',
      'other_im_provider' => 'other_im_provider text',
      'other_im_screen_name' => 'other_im_screen_name varchar(64)',
      'other_im_screen_name_jabber' => 'other_im_screen_name_jabber varchar(64)',
      'other_im_screen_name_skype' => 'other_im_screen_name_skype varchar(64)',
      'other_im_screen_name_yahoo' => 'other_im_screen_name_yahoo varchar(64)',
      'im_provider' => 'im_provider text',
      'im_screen_name' => 'im_screen_name varchar(64)',
      'contact_id' => 'contact_id varchar(255)',
      '2_a_b_im_provider' => '2_a_b_im_provider text',
      '2_a_b_billing_im_screen_name' => '2_a_b_billing_im_screen_name varchar(64)',
      '2_a_b_billing_im_screen_name_jabber' => '2_a_b_billing_im_screen_name_jabber varchar(64)',
      '2_a_b_billing_im_screen_name_skype' => '2_a_b_billing_im_screen_name_skype varchar(64)',
      '2_a_b_billing_im_screen_name_yahoo' => '2_a_b_billing_im_screen_name_yahoo varchar(64)',
      '2_a_b_home_im_screen_name' => '2_a_b_home_im_screen_name varchar(64)',
      '2_a_b_home_im_screen_name_jabber' => '2_a_b_home_im_screen_name_jabber varchar(64)',
      '2_a_b_home_im_screen_name_skype' => '2_a_b_home_im_screen_name_skype varchar(64)',
      '2_a_b_home_im_screen_name_yahoo' => '2_a_b_home_im_screen_name_yahoo varchar(64)',
      '2_a_b_main_im_screen_name' => '2_a_b_main_im_screen_name varchar(64)',
      '2_a_b_main_im_screen_name_jabber' => '2_a_b_main_im_screen_name_jabber varchar(64)',
      '2_a_b_main_im_screen_name_skype' => '2_a_b_main_im_screen_name_skype varchar(64)',
      '2_a_b_main_im_screen_name_yahoo' => '2_a_b_main_im_screen_name_yahoo varchar(64)',
      '2_a_b_other_im_screen_name' => '2_a_b_other_im_screen_name varchar(64)',
      '2_a_b_other_im_screen_name_jabber' => '2_a_b_other_im_screen_name_jabber varchar(64)',
      '2_a_b_other_im_screen_name_skype' => '2_a_b_other_im_screen_name_skype varchar(64)',
      '2_a_b_other_im_screen_name_yahoo' => '2_a_b_other_im_screen_name_yahoo varchar(64)',
      '2_a_b_im_screen_name' => '2_a_b_im_screen_name varchar(64)',
      '8_a_b_im_provider' => '8_a_b_im_provider text',
      '8_a_b_billing_im_screen_name' => '8_a_b_billing_im_screen_name varchar(64)',
      '8_a_b_billing_im_screen_name_jabber' => '8_a_b_billing_im_screen_name_jabber varchar(64)',
      '8_a_b_billing_im_screen_name_skype' => '8_a_b_billing_im_screen_name_skype varchar(64)',
      '8_a_b_billing_im_screen_name_yahoo' => '8_a_b_billing_im_screen_name_yahoo varchar(64)',
      '8_a_b_home_im_screen_name' => '8_a_b_home_im_screen_name varchar(64)',
      '8_a_b_home_im_screen_name_jabber' => '8_a_b_home_im_screen_name_jabber varchar(64)',
      '8_a_b_home_im_screen_name_skype' => '8_a_b_home_im_screen_name_skype varchar(64)',
      '8_a_b_home_im_screen_name_yahoo' => '8_a_b_home_im_screen_name_yahoo varchar(64)',
      '8_a_b_main_im_screen_name' => '8_a_b_main_im_screen_name varchar(64)',
      '8_a_b_main_im_screen_name_jabber' => '8_a_b_main_im_screen_name_jabber varchar(64)',
      '8_a_b_main_im_screen_name_skype' => '8_a_b_main_im_screen_name_skype varchar(64)',
      '8_a_b_main_im_screen_name_yahoo' => '8_a_b_main_im_screen_name_yahoo varchar(64)',
      '8_a_b_other_im_screen_name' => '8_a_b_other_im_screen_name varchar(64)',
      '8_a_b_other_im_screen_name_jabber' => '8_a_b_other_im_screen_name_jabber varchar(64)',
      '8_a_b_other_im_screen_name_skype' => '8_a_b_other_im_screen_name_skype varchar(64)',
      '8_a_b_other_im_screen_name_yahoo' => '8_a_b_other_im_screen_name_yahoo varchar(64)',
      '8_a_b_im_screen_name' => '8_a_b_im_screen_name varchar(64)',
      '5_a_b_im_provider' => '5_a_b_im_provider text',
      '5_a_b_billing_im_screen_name' => '5_a_b_billing_im_screen_name varchar(64)',
      '5_a_b_billing_im_screen_name_jabber' => '5_a_b_billing_im_screen_name_jabber varchar(64)',
      '5_a_b_billing_im_screen_name_skype' => '5_a_b_billing_im_screen_name_skype varchar(64)',
      '5_a_b_billing_im_screen_name_yahoo' => '5_a_b_billing_im_screen_name_yahoo varchar(64)',
      '5_a_b_home_im_screen_name' => '5_a_b_home_im_screen_name varchar(64)',
      '5_a_b_home_im_screen_name_jabber' => '5_a_b_home_im_screen_name_jabber varchar(64)',
      '5_a_b_home_im_screen_name_skype' => '5_a_b_home_im_screen_name_skype varchar(64)',
      '5_a_b_home_im_screen_name_yahoo' => '5_a_b_home_im_screen_name_yahoo varchar(64)',
      '5_a_b_main_im_screen_name' => '5_a_b_main_im_screen_name varchar(64)',
      '5_a_b_main_im_screen_name_jabber' => '5_a_b_main_im_screen_name_jabber varchar(64)',
      '5_a_b_main_im_screen_name_skype' => '5_a_b_main_im_screen_name_skype varchar(64)',
      '5_a_b_main_im_screen_name_yahoo' => '5_a_b_main_im_screen_name_yahoo varchar(64)',
      '5_a_b_other_im_screen_name' => '5_a_b_other_im_screen_name varchar(64)',
      '5_a_b_other_im_screen_name_jabber' => '5_a_b_other_im_screen_name_jabber varchar(64)',
      '5_a_b_other_im_screen_name_skype' => '5_a_b_other_im_screen_name_skype varchar(64)',
      '5_a_b_other_im_screen_name_yahoo' => '5_a_b_other_im_screen_name_yahoo varchar(64)',
      '5_a_b_im_screen_name' => '5_a_b_im_screen_name varchar(64)',
      'whare_kai_im_provider' => 'whare_kai_im_provider text',
      'whare_kai_im_screen_name' => 'whare_kai_im_screen_name varchar(64)',
      'whare_kai_im_screen_name_jabber' => 'whare_kai_im_screen_name_jabber varchar(64)',
      'whare_kai_im_screen_name_skype' => 'whare_kai_im_screen_name_skype varchar(64)',
      'whare_kai_im_screen_name_yahoo' => 'whare_kai_im_screen_name_yahoo varchar(64)',
      '2_a_b_whare_kai_im_screen_name' => '2_a_b_whare_kai_im_screen_name varchar(64)',
      '2_a_b_whare_kai_im_screen_name_jabber' => '2_a_b_whare_kai_im_screen_name_jabber varchar(64)',
      '2_a_b_whare_kai_im_screen_name_skype' => '2_a_b_whare_kai_im_screen_name_skype varchar(64)',
      '2_a_b_whare_kai_im_screen_name_yahoo' => '2_a_b_whare_kai_im_screen_name_yahoo varchar(64)',
      '8_a_b_whare_kai_im_screen_name' => '8_a_b_whare_kai_im_screen_name varchar(64)',
      '8_a_b_whare_kai_im_screen_name_jabber' => '8_a_b_whare_kai_im_screen_name_jabber varchar(64)',
      '8_a_b_whare_kai_im_screen_name_skype' => '8_a_b_whare_kai_im_screen_name_skype varchar(64)',
      '8_a_b_whare_kai_im_screen_name_yahoo' => '8_a_b_whare_kai_im_screen_name_yahoo varchar(64)',
      '5_a_b_whare_kai_im_screen_name' => '5_a_b_whare_kai_im_screen_name varchar(64)',
      '5_a_b_whare_kai_im_screen_name_jabber' => '5_a_b_whare_kai_im_screen_name_jabber varchar(64)',
      '5_a_b_whare_kai_im_screen_name_skype' => '5_a_b_whare_kai_im_screen_name_skype varchar(64)',
      '5_a_b_whare_kai_im_screen_name_yahoo' => '5_a_b_whare_kai_im_screen_name_yahoo varchar(64)',
    ], $sqlColumns);

  }


  /**
   * Test phone data export.
   *
   * Less over the top complete than the im test.
   */
  public function testExportPhoneData() {
    $this->contactIDs[] = $this->individualCreate();
    $this->contactIDs[] = $this->individualCreate();
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
      $this->contactIDs[1] => ['label' => 'Spouse of']
    ];

    foreach ($relationships as $contactID => $relationshipType) {
      $relationshipTypeID = $this->callAPISuccess('RelationshipType', 'getvalue', ['label_a_b' => $relationshipType['label'], 'return' => 'id']);
      $result = $this->callAPISuccess('Relationship', 'create', [
        'contact_id_a' => $this->contactIDs[0],
        'relationship_type_id' => $relationshipTypeID,
        'contact_id_b' => $contactID
      ]);
      $relationships[$contactID]['id'] = $result['id'];
      $relationships[$contactID]['relationship_type_id'] = $relationshipTypeID;
    }

    $fields = [['Individual', 'contact_id']];
    // ' ' denotes primary location type.
    foreach (array_keys(array_merge($locationTypes, [' ' => ['Primary']])) as $locationType) {
      $fields[] = [
        'Individual',
        'phone',
        CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Phone', 'location_type_id', $locationType),
      ];
      $fields[] = [
        'Individual',
        'phone_type_id',
        CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Phone', 'location_type_id', $locationType),
      ];
      foreach ($relationships as $contactID => $relationship) {
        $fields[] = [
          'Individual',
          $relationship['relationship_type_id'] . '_a_b',
          'phone_type_id',
          CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Phone', 'location_type_id', $locationType),
        ];
      }
      foreach ($phoneTypes as $phoneType) {
        $fields[] = [
          'Individual',
          'phone',
          CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Phone', 'location_type_id', $locationType),
          CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Phone', 'phone_type_id', $phoneType),
        ];
        foreach ($relationships as $contactID => $relationship) {
          $fields[] = [
            'Individual',
            $relationship['relationship_type_id'] . '_a_b',
            'phone_type_id',
            CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Phone', 'location_type_id', $locationType),
            CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Phone', 'phone_type_id', $phoneType),
          ];
        }
      }
    }
    list($tableName) = $this->doExport($fields, $this->contactIDs[0]);

    $dao = CRM_Core_DAO::executeQuery('SELECT * FROM ' . $tableName);
    while ($dao->fetch()) {
      // note there is some chance these might be random on some mysql co
      $this->assertEquals('BillingMobile3', $dao->billing_phone_mobile);
      $this->assertEquals('', $dao->billing_phone_phone);
      $relField = '2_a_b_phone_type_id';
      $this->assertEquals('Phone', $dao->$relField);
      $this->assertEquals('Mobile', $dao->phone_type_id);
      $this->assertEquals('Mobile', $dao->billing_phone_type_id);
    }
  }

  /**
   * Export City against multiple location types.
   */
  public function testExportAddressData() {
    $this->diversifyLocationTypes();

    $locationTypes = ['Billing' => 'Billing', 'Home' => 'Home', 'Main' => 'Méin', 'Other' => 'Other', 'Whare Kai' => 'Whare Kai'];

    $this->contactIDs[] = $this->individualCreate();
    $this->contactIDs[] = $this->individualCreate();
    $this->contactIDs[] = $this->householdCreate();
    $this->contactIDs[] = $this->organizationCreate();
    $fields = [['Individual', 'contact_id']];
    foreach ($this->contactIDs as $contactID) {
      foreach ($locationTypes as $locationName => $locationLabel) {
        $this->callAPISuccess('Address', 'create', [
          'contact_id' => $contactID,
          'location_type_id' => $locationName,
          'street_address' => $locationLabel . $contactID . 'street_address',
          'city' => $locationLabel . $contactID . 'city',
          'postal_code' => $locationLabel . $contactID . 'postal_code',
        ]);
        $fields[] = ['Individual', 'city', CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Address', 'location_type_id', $locationName)];
        $fields[] = ['Individual', 'street_address', CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Address', 'location_type_id', $locationName)];
        $fields[] = ['Individual', 'postal_code', CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Address', 'location_type_id', $locationName)];
      }
    }

    $relationships = [
      $this->contactIDs[1] => ['label' => 'Spouse of'],
      $this->contactIDs[2] => ['label' => 'Household Member of'],
      $this->contactIDs[3] => ['label' => 'Employee of']
    ];

    foreach ($relationships as $contactID => $relationshipType) {
      $relationshipTypeID = $this->callAPISuccess('RelationshipType', 'getvalue', ['label_a_b' => $relationshipType['label'], 'return' => 'id']);
      $result = $this->callAPISuccess('Relationship', 'create', [
        'contact_id_a' => $this->contactIDs[0],
        'relationship_type_id' => $relationshipTypeID,
        'contact_id_b' => $contactID
      ]);
      $relationships[$contactID]['id'] = $result['id'];
      $relationships[$contactID]['relationship_type_id'] = $relationshipTypeID;
    }

    // ' ' denotes primary location type.
    foreach (array_keys(array_merge($locationTypes, [' ' => ['Primary']])) as $locationType) {
      foreach ($relationships as $contactID => $relationship) {
        $fields[] = [
          'Individual',
          $relationship['relationship_type_id'] . '_a_b',
          'city',
          CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_IM', 'location_type_id', $locationType),
        ];
      }
    }
    list($tableName, $sqlColumns) = $this->doExport($fields, $this->contactIDs[0]);

    $dao = CRM_Core_DAO::executeQuery('SELECT * FROM ' . $tableName);
    while ($dao->fetch()) {
      $id = $dao->contact_id;
      $this->assertEquals('Méin' . $id . 'city', $dao->main_city);
      $this->assertEquals('Billing' . $id . 'street_address', $dao->billing_street_address);
      $this->assertEquals('Whare Kai' . $id . 'postal_code', $dao->whare_kai_postal_code);
      foreach ($relationships as $relatedContactID => $relationship) {
        $relationshipString = $field = $relationship['relationship_type_id'] . '_a_b';
        $field = $relationshipString . '_main_city';
        $this->assertEquals('Méin' . $relatedContactID . 'city', $dao->$field);
      }
    }

    $this->assertEquals([
      'contact_id' => 'contact_id varchar(255)',
      'billing_city' => 'billing_city varchar(64)',
      'billing_street_address' => 'billing_street_address varchar(96)',
      'billing_postal_code' => 'billing_postal_code varchar(64)',
      'home_city' => 'home_city varchar(64)',
      'home_street_address' => 'home_street_address varchar(96)',
      'home_postal_code' => 'home_postal_code varchar(64)',
      'main_city' => 'main_city varchar(64)',
      'main_street_address' => 'main_street_address varchar(96)',
      'main_postal_code' => 'main_postal_code varchar(64)',
      'other_city' => 'other_city varchar(64)',
      'other_street_address' => 'other_street_address varchar(96)',
      'other_postal_code' => 'other_postal_code varchar(64)',
      'whare_kai_city' => 'whare_kai_city varchar(64)',
      'whare_kai_street_address' => 'whare_kai_street_address varchar(96)',
      'whare_kai_postal_code' => 'whare_kai_postal_code varchar(64)',
      '2_a_b_billing_city' => '2_a_b_billing_city varchar(64)',
      '2_a_b_home_city' => '2_a_b_home_city varchar(64)',
      '2_a_b_main_city' => '2_a_b_main_city varchar(64)',
      '2_a_b_other_city' => '2_a_b_other_city varchar(64)',
      '2_a_b_whare_kai_city' => '2_a_b_whare_kai_city varchar(64)',
      '2_a_b_city' => '2_a_b_city varchar(64)',
      '8_a_b_billing_city' => '8_a_b_billing_city varchar(64)',
      '8_a_b_home_city' => '8_a_b_home_city varchar(64)',
      '8_a_b_main_city' => '8_a_b_main_city varchar(64)',
      '8_a_b_other_city' => '8_a_b_other_city varchar(64)',
      '8_a_b_whare_kai_city' => '8_a_b_whare_kai_city varchar(64)',
      '8_a_b_city' => '8_a_b_city varchar(64)',
      '5_a_b_billing_city' => '5_a_b_billing_city varchar(64)',
      '5_a_b_home_city' => '5_a_b_home_city varchar(64)',
      '5_a_b_main_city' => '5_a_b_main_city varchar(64)',
      '5_a_b_other_city' => '5_a_b_other_city varchar(64)',
      '5_a_b_whare_kai_city' => '5_a_b_whare_kai_city varchar(64)',
      '5_a_b_city' => '5_a_b_city varchar(64)',
    ], $sqlColumns);
  }

  /**
   * Test master_address_id field.
   */
  public function testExportMasterAddress() {
    $this->setUpContactExportData();

    //export the master address for contact B
    $selectedFields = array(
      array('Individual', 'master_id', 1),
    );
    list($tableName, $sqlColumns) = CRM_Export_BAO_Export::exportComponents(
      TRUE,
      array($this->contactIDs[1]),
      array(),
      NULL,
      $selectedFields,
      NULL,
      CRM_Export_Form_Select::CONTACT_EXPORT,
      "contact_a.id IN ({$this->contactIDs[1]})",
      NULL,
      FALSE,
      FALSE,
      array(
        'exportOption' => CRM_Export_Form_Select::CONTACT_EXPORT,
        'suppress_csv_for_testing' => TRUE,
      )
    );
    $field = key($sqlColumns);

    //assert the exported result
    $masterName = CRM_Core_DAO::singleValueQuery("SELECT {$field} FROM {$tableName}");
    $displayName = CRM_Contact_BAO_Contact::getMasterDisplayName($this->masterAddressID);
    $this->assertEquals($displayName, $masterName);

    // delete the export temp table and component table
    $sql = "DROP TABLE IF EXISTS {$tableName}";
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Test that deceased and do not mail contacts are removed from contacts before
   *
   * @dataProvider getReasonsNotToMail
   *
   * @param array $reason
   * @param array $addressReason
   */
  public function testExportDeceasedDoNotMail($reason, $addressReason) {
    $contactA = $this->callAPISuccess('contact', 'create', array(
      'first_name' => 'John',
      'last_name' => 'Doe',
      'contact_type' => 'Individual',
    ));

    $contactB = $this->callAPISuccess('contact', 'create', array_merge([
      'first_name' => 'Jane',
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

    //export and merge contacts with same address
    list($tableName, $sqlColumns, $headerRows, $processor) = CRM_Export_BAO_Export::exportComponents(
      TRUE,
      array($contactA['id'], $contactB['id']),
      array(),
      NULL,
      NULL,
      NULL,
      CRM_Export_Form_Select::CONTACT_EXPORT,
      "contact_a.id IN ({$contactA['id']}, {$contactB['id']})",
      NULL,
      TRUE,
      FALSE,
      array(
        'exportOption' => CRM_Export_Form_Select::CONTACT_EXPORT,
        'mergeOption' => TRUE,
        'suppress_csv_for_testing' => TRUE,
        'postal_mailing_export' => array(
          'postal_mailing_export' => TRUE,
        ),
      )
    );

    $this->assertTrue(!in_array('state_province_id', $processor->getHeaderRows()));
    $greeting = CRM_Core_DAO::singleValueQuery("SELECT email_greeting FROM {$tableName}");

    //Assert email_greeting is not merged
    $this->assertNotContains(',', (string) $greeting);

    // delete the export temp table and component table
    $sql = "DROP TABLE IF EXISTS {$tableName}";
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Get reasons that a contact is not postalable.
   * @return array
   */
  public function getReasonsNotToMail() {
    return [
      [['is_deceased' => 1], []],
      [['do_not_mail' => 1], []],
      [[], ['street_address' => '']],
    ];
  }
  /**
   * @return array
   */
  protected function setUpHousehold() {
    $this->setUpContactExportData();
    $householdID = $this->householdCreate([
      'source' => 'household sauce',
      'api.Address.create' => [
        'city' => 'Portland',
        'state_province_id' => 'Maine',
        'location_type_id' => 'Home'
      ]
    ]);

    $relationshipTypes = $this->callAPISuccess('RelationshipType', 'get', [])['values'];
    $houseHoldTypeID = NULL;
    foreach ($relationshipTypes as $id => $relationshipType) {
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
    return array($householdID, $houseHoldTypeID);
  }

  /**
   * Do a CiviCRM export.
   *
   * @param $selectedFields
   * @param int $id
   *
   * @param int $exportMode
   *
   * @return array
   */
  protected function doExport($selectedFields, $id, $exportMode = CRM_Export_Form_Select::CONTACT_EXPORT) {
    $ids = (array) $id;
    list($tableName, $sqlColumns) = CRM_Export_BAO_Export::exportComponents(
      TRUE,
      $ids,
      array(),
      NULL,
      $selectedFields,
      NULL,
      $exportMode,
      "contact_a.id IN (" . implode(',', $ids) . ")",
      NULL,
      FALSE,
      FALSE,
      array(
        'exportOption' => CRM_Export_Form_Select::CONTACT_EXPORT,
        'suppress_csv_for_testing' => TRUE,
      )
    );
    return array($tableName, $sqlColumns);
  }

  /**
   * Ensure component is enabled.
   *
   * @param int $exportMode
   */
  public function ensureComponentIsEnabled($exportMode) {
    if ($exportMode === CRM_Export_Form_Select::CASE_EXPORT) {
      CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
    }
  }

  /**
   * Test our export all field metadata retrieval.
   *
   * @dataProvider additionalFieldsDataProvider
   * @param int $exportMode
   * @param $expected
   */
  public function testAdditionalReturnProperties($exportMode, $expected) {
    $this->ensureComponentIsEnabled($exportMode);
    $processor = new CRM_Export_BAO_ExportProcessor($exportMode, NULL, 'AND');
    $metadata = $processor->getAdditionalReturnProperties();
    $this->assertEquals($expected, $metadata);
  }

  /**
   * Test our export all field metadata retrieval.
   *
   * @dataProvider allFieldsDataProvider
   * @param int $exportMode
   * @param $expected
   */
  public function testDefaultReturnProperties($exportMode, $expected) {
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
  public function additionalFieldsDataProvider() {
    return [
      [
        'anything that will then be defaulting ton contact',
        $this->getExtraReturnProperties(),
      ],
      [
        CRM_Export_Form_Select::ACTIVITY_EXPORT,
        array_merge($this->getExtraReturnProperties(), $this->getActivityReturnProperties()),
      ],
      [
        CRM_Export_Form_Select::CASE_EXPORT,
        array_merge($this->getExtraReturnProperties(), $this->getCaseReturnProperties()),
      ],
      [
        CRM_Export_Form_Select::CONTRIBUTE_EXPORT,
        array_merge($this->getExtraReturnProperties(), $this->getContributionReturnProperties()),
      ],
      [
        CRM_Export_Form_Select::EVENT_EXPORT,
        array_merge($this->getExtraReturnProperties(), $this->getEventReturnProperties()),
      ],
      [
        CRM_Export_Form_Select::MEMBER_EXPORT,
        array_merge($this->getExtraReturnProperties(), $this->getMembershipReturnProperties()),
      ],
      [
        CRM_Export_Form_Select::PLEDGE_EXPORT,
        array_merge($this->getExtraReturnProperties(), $this->getPledgeReturnProperties()),
      ],

    ];
  }

  /**
   * get data for testing field metadata by query mode.
   */
  public function allFieldsDataProvider() {
    return [
      [
        'anything that will then be defaulting ton contact',
        $this->getBasicReturnProperties(TRUE),
      ],
      [
        CRM_Export_Form_Select::ACTIVITY_EXPORT,
        array_merge($this->getBasicReturnProperties(FALSE), $this->getActivityReturnProperties()),
      ],
      [
        CRM_Export_Form_Select::CASE_EXPORT,
        array_merge($this->getBasicReturnProperties(FALSE), $this->getCaseReturnProperties()),
      ],
      [
        CRM_Export_Form_Select::CONTRIBUTE_EXPORT,
        array_merge($this->getBasicReturnProperties(FALSE), $this->getContributionReturnProperties()),
      ],
      [
        CRM_Export_Form_Select::EVENT_EXPORT,
        array_merge($this->getBasicReturnProperties(FALSE), $this->getEventReturnProperties()),
      ],
      [
        CRM_Export_Form_Select::MEMBER_EXPORT,
        array_merge($this->getBasicReturnProperties(FALSE), $this->getMembershipReturnProperties()),
      ],
      [
        CRM_Export_Form_Select::PLEDGE_EXPORT,
        array_merge($this->getBasicReturnProperties(FALSE), $this->getPledgeReturnProperties()),
      ],
    ];
  }

  /**
   * Get return properties manually added in.
   */
  public function getExtraReturnProperties() {
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
  protected function getBasicReturnProperties($isContactMode) {
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
      'preferred_mail_format' => 1,
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
    ];
    if (!$isContactMode) {
      unset($returnProperties['groups']);
      unset($returnProperties['tags']);
      unset($returnProperties['notes']);
    }
    return $returnProperties;
  }

  /**
   * Get return properties for pledges.
   *
   * @return array
   */
  public function getPledgeReturnProperties() {
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
  public function getMembershipReturnProperties() {
    return [
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
      'display_name' => 1,
      'membership_type' => 1,
      'member_is_test' => 1,
      'member_is_pay_later' => 1,
      'join_date' => 1,
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
      'member_auto_renew' => 1,
    ];
  }

  /**
   * Get return properties for events.
   *
   * @return array
   */
  public function getEventReturnProperties() {
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
  public function getActivityReturnProperties() {
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
  public function getCaseReturnProperties() {
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
      'case_recent_activity_date' => 1,
      'case_recent_activity_type' => 1,
      'case_scheduled_activity_date' => 1,
    ];
  }

  /**
   * Get return properties for contribution.
   *
   * @return array
   */
  public function getContributionReturnProperties() {
    return [
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
      'display_name' => 1,
      'financial_type' => 1,
      'contribution_source' => 1,
      'receive_date' => 1,
      'thankyou_date' => 1,
      'cancel_date' => 1,
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
   * @dataProvider getSqlColumnsOutput
   */
  public function testGetSQLColumnsAndHeaders($exportMode, $expected, $expectedHeaders) {
    $this->ensureComponentIsEnabled($exportMode);
    // We need some data so that we can get to the end of the export
    // function. Hopefully one day that won't be required to get metadata info out.
    // eventually aspire to call $provider->getSQLColumns straight after it
    // is intiated.
    $this->setupBaseExportData($exportMode);

    $result = CRM_Export_BAO_Export::exportComponents(
      TRUE,
      [1],
      [],
      NULL,
      NULL,
      NULL,
      $exportMode,
      NULL,
      NULL,
      FALSE,
      FALSE,
      array(
        'exportOption' => CRM_Export_Form_Select::CONTRIBUTE_EXPORT,
        'suppress_csv_for_testing' => TRUE,
      )
    );
    $this->assertEquals($expected, $result[1]);
    $this->assertEquals($expectedHeaders, $result[2]);
  }

  /**
   * Test exported with fields to output specified.
   *
   * @dataProvider getAllSpecifiableReturnFields
   *
   * @param int $exportMode
   * @param array $selectedFields
   * @param array $expected
   */
  public function testExportSpecifyFields($exportMode, $selectedFields, $expected) {
    $this->ensureComponentIsEnabled($exportMode);
    $this->setUpContributionExportData();
    list($tableName, $sqlColumns) = $this->doExport($selectedFields, $this->contactIDs[1], $exportMode);
    $this->assertEquals($expected, $sqlColumns);
  }

  /**
   * Test export fields when no payment fields to be exported.
   */
  public function textExportParticipantSpecifyFieldsNoPayment() {
    $selectedFields = $this->getAllSpecifiableParticipantReturnFields();
    foreach ($selectedFields as $index => $field) {
      if (substr($field[1], 0, 22) === 'componentPaymentField_') {
        unset ($selectedFields[$index]);
      }
    }

    $expected = $this->getAllSpecifiableParticipantReturnFields();
    foreach ($expected as $index => $field) {
      if (substr($index, 0, 22) === 'componentPaymentField_') {
        unset ($expected[$index]);
      }
    }

    list($tableName, $sqlColumns) = $this->doExport($selectedFields, $this->contactIDs[1], CRM_Export_Form_Select::EVENT_EXPORT);
    $this->assertEquals($expected, $sqlColumns);
  }
  /**
   * Get all return fields (@todo - still being built up.
   *
   * @return array
   */
  public function getAllSpecifiableReturnFields() {
    return [
      [
        CRM_Export_Form_Select::EVENT_EXPORT,
        $this->getAllSpecifiableParticipantReturnFields(),
        $this->getAllSpecifiableParticipantReturnColumns(),
      ],
    ];
  }

  /**
   * Get expected return column output for participant mode return all columns.
   *
   * @return array
   */
  public function getAllSpecifiableParticipantReturnColumns() {
    return [
      'participant_campaign_id' => 'participant_campaign_id varchar(128)',
      'participant_contact_id' => 'participant_contact_id varchar(16)',
      'componentpaymentfield_contribution_status' => 'componentpaymentfield_contribution_status text',
      'currency' => 'currency varchar(3)',
      'componentpaymentfield_received_date' => 'componentpaymentfield_received_date text',
      'default_role_id' => 'default_role_id varchar(16)',
      'participant_discount_name' => 'participant_discount_name varchar(16)',
      'event_id' => 'event_id varchar(16)',
      'event_end_date' => 'event_end_date varchar(32)',
      'event_start_date' => 'event_start_date varchar(32)',
      'template_title' => 'template_title varchar(255)',
      'event_title' => 'event_title varchar(255)',
      'participant_fee_amount' => 'participant_fee_amount varchar(32)',
      'participant_fee_currency' => 'participant_fee_currency varchar(3)',
      'fee_label' => 'fee_label varchar(255)',
      'participant_fee_level' => 'participant_fee_level longtext',
      'participant_is_pay_later' => 'participant_is_pay_later varchar(16)',
      'participant_id' => 'participant_id varchar(16)',
      'participant_note' => 'participant_note text',
      'participant_role_id' => 'participant_role_id varchar(128)',
      'participant_role' => 'participant_role varchar(255)',
      'participant_source' => 'participant_source varchar(128)',
      'participant_status_id' => 'participant_status_id varchar(16)',
      'participant_status' => 'participant_status varchar(255)',
      'participant_register_date' => 'participant_register_date varchar(32)',
      'participant_registered_by_id' => 'participant_registered_by_id varchar(16)',
      'participant_is_test' => 'participant_is_test varchar(16)',
      'componentpaymentfield_total_amount' => 'componentpaymentfield_total_amount text',
      'componentpaymentfield_transaction_id' => 'componentpaymentfield_transaction_id varchar(255)',
      'transferred_to_contact_id' => 'transferred_to_contact_id varchar(16)',
    ];
  }

  /**
   * @return array
   */
  public function getAllSpecifiableParticipantReturnFields() {
    return [
      0 =>
        [
          0 => 'Participant',
          1 => '',
        ],
      1 =>
        [
          0 => 'Participant',
          1 => 'participant_campaign_id',
        ],
      2 =>
        [
          0 => 'Participant',
          1 => 'participant_contact_id',
        ],
      3 =>
        [
          0 => 'Participant',
          1 => 'componentPaymentField_contribution_status',
        ],
      4 =>
        [
          0 => 'Participant',
          1 => 'currency',
        ],
      5 =>
        [
          0 => 'Participant',
          1 => 'componentPaymentField_received_date',
        ],
      6 =>
        [
          0 => 'Participant',
          1 => 'default_role_id',
        ],
      7 =>
        [
          0 => 'Participant',
          1 => 'participant_discount_name',
        ],
      8 =>
        [
          0 => 'Participant',
          1 => 'event_id',
        ],
      9 =>
        [
          0 => 'Participant',
          1 => 'event_end_date',
        ],
      10 =>
        [
          0 => 'Participant',
          1 => 'event_start_date',
        ],
      11 =>
        [
          0 => 'Participant',
          1 => 'template_title',
        ],
      12 =>
        [
          0 => 'Participant',
          1 => 'event_title',
        ],
      13 =>
        [
          0 => 'Participant',
          1 => 'participant_fee_amount',
        ],
      14 =>
        [
          0 => 'Participant',
          1 => 'participant_fee_currency',
        ],
      15 =>
        [
          0 => 'Participant',
          1 => 'fee_label',
        ],
      16 =>
        [
          0 => 'Participant',
          1 => 'participant_fee_level',
        ],
      17 =>
        [
          0 => 'Participant',
          1 => 'participant_is_pay_later',
        ],
      18 =>
        [
          0 => 'Participant',
          1 => 'participant_id',
        ],
      19 =>
        [
          0 => 'Participant',
          1 => 'participant_note',
        ],
      20 =>
        [
          0 => 'Participant',
          1 => 'participant_role_id',
        ],
      21 =>
        [
          0 => 'Participant',
          1 => 'participant_role',
        ],
      22 =>
        [
          0 => 'Participant',
          1 => 'participant_source',
        ],
      23 =>
        [
          0 => 'Participant',
          1 => 'participant_status_id',
        ],
      24 =>
        [
          0 => 'Participant',
          1 => 'participant_status',
        ],
      25 =>
        [
          0 => 'Participant',
          1 => 'participant_status',
        ],
      26 =>
        [
          0 => 'Participant',
          1 => 'participant_register_date',
        ],
      27 =>
        [
          0 => 'Participant',
          1 => 'participant_registered_by_id',
        ],
      28 =>
        [
          0 => 'Participant',
          1 => 'participant_is_test',
        ],
      29 =>
        [
          0 => 'Participant',
          1 => 'componentPaymentField_total_amount',
        ],
      30 =>
        [
          0 => 'Participant',
          1 => 'componentPaymentField_transaction_id',
        ],
      31 =>
        [
          0 => 'Participant',
          1 => 'transferred_to_contact_id',
        ],
    ];
  }

  /**
   * @param string $exportMode
   */
  public function setupBaseExportData($exportMode) {
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
  public function getSqlColumnsOutput() {
    return [
      [
        'anything that will then be defaulting ton contact',
        $this->getBasicSqlColumnDefinition(TRUE),
        $this->getBasicHeaderDefinition(TRUE),
      ],
      [
        CRM_Export_Form_Select::ACTIVITY_EXPORT,
        array_merge($this->getBasicSqlColumnDefinition(FALSE), $this->getActivitySqlColumns()),
        array_merge($this->getBasicHeaderDefinition(FALSE), $this->getActivityHeaderDefinition()),
      ],
      [
        CRM_Export_Form_Select::CASE_EXPORT,
        array_merge($this->getBasicSqlColumnDefinition(FALSE), $this->getCaseSqlColumns()),
        array_merge($this->getBasicHeaderDefinition(FALSE), $this->getCaseHeaderDefinition()),
      ],
      [
        CRM_Export_Form_Select::CONTRIBUTE_EXPORT,
        array_merge($this->getBasicSqlColumnDefinition(FALSE), $this->getContributionSqlColumns()),
        array_merge($this->getBasicHeaderDefinition(FALSE), $this->getContributeHeaderDefinition()),
      ],
      [
        CRM_Export_Form_Select::EVENT_EXPORT,
        array_merge($this->getBasicSqlColumnDefinition(FALSE), $this->getParticipantSqlColumns()),
        array_merge($this->getBasicHeaderDefinition(FALSE), $this->getParticipantHeaderDefinition()),
      ],
      [
        CRM_Export_Form_Select::MEMBER_EXPORT,
        array_merge($this->getBasicSqlColumnDefinition(FALSE), $this->getMembershipSqlColumns()),
        array_merge($this->getBasicHeaderDefinition(FALSE), $this->getMemberHeaderDefinition()),
      ],
      [
        CRM_Export_Form_Select::PLEDGE_EXPORT,
        array_merge($this->getBasicSqlColumnDefinition(FALSE), $this->getPledgeSqlColumns()),
        array_merge($this->getBasicHeaderDefinition(FALSE), $this->getPledgeHeaderDefinition()),
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
  protected function getBasicHeaderDefinition($isContactExport) {
    $headers = [
        0 => 'Contact ID',
        1 => 'Contact Type',
        2 => 'Contact Subtype',
        3 => 'Do Not Email',
        4 => 'Do Not Phone',
        5 => 'Do Not Mail',
        6 => 'Do Not Sms',
        7 => 'Do Not Trade',
        8 => 'No Bulk Emails (User Opt Out)',
        9 => 'Legal Identifier',
        10 => 'External Identifier',
        11 => 'Sort Name',
        12 => 'Display Name',
        13 => 'Nickname',
        14 => 'Legal Name',
        15 => 'Image Url',
        16 => 'Preferred Communication Method',
        17 => 'Preferred Language',
        18 => 'Preferred Mail Format',
        19 => 'Contact Hash',
        20 => 'Contact Source',
        21 => 'First Name',
        22 => 'Middle Name',
        23 => 'Last Name',
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
        34 => 'Deceased',
        35 => 'Deceased Date',
        36 => 'Household Name',
        37 => 'Organization Name',
        38 => 'Sic Code',
        39 => 'Unique ID (OpenID)',
        40 => 'Current Employer ID',
        41 => 'Contact is in Trash',
        42 => 'Created Date',
        43 => 'Modified Date',
        44 => 'Addressee',
        45 => 'Email Greeting',
        46 => 'Postal Greeting',
        47 => 'Current Employer',
        48 => 'Location Type',
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
        62 => 'Address Name',
        63 => 'Master Address Belongs To',
        64 => 'County',
        65 => 'State',
        66 => 'Country',
        67 => 'Phone',
        68 => 'Phone Extension',
        69 => 'Phone Type',
        70 => 'Email',
        71 => 'On Hold',
        72 => 'Use for Bulk Mail',
        73 => 'Signature Text',
        74 => 'Signature Html',
        75 => 'IM Provider',
        76 => 'IM Screen Name',
        77 => 'OpenID',
        78 => 'World Region',
        79 => 'Website',
        80 => 'Group(s)',
        81 => 'Tag(s)',
        82 => 'Note(s)',
      ];
    if (!$isContactExport) {
      unset($headers[80]);
      unset($headers[81]);
      unset($headers[82]);
    }
    return $headers;
  }

  /**
   * Get the definition for activity headers.
   *
   * @return array
   */
  protected function getActivityHeaderDefinition() {
    return [
      81 => 'Activity ID',
      82 => 'Activity Type',
      83 => 'Activity Type ID',
      84 => 'Subject',
      85 => 'Activity Date',
      86 => 'Duration',
      87 => 'Location',
      88 => 'Details',
      89 => 'Activity Status',
      90 => 'Activity Priority',
      91 => 'Source Contact',
      92 => 'source_record_id',
      93 => 'Test',
      94 => 'Campaign ID',
      95 => 'result',
      96 => 'Engagement Index',
      97 => 'parent_id',
    ];
  }

  /**
   * Get the definition for case headers.
   *
   * @return array
   */
  protected function getCaseHeaderDefinition() {
    return [
      81 => 'contact_id',
      82 => 'Case ID',
      83 => 'case_activity_subject',
      84 => 'Case Subject',
      85 => 'Case Status',
      86 => 'Case Type',
      87 => 'Role in Case',
      88 => 'Case is in the Trash',
      89 => 'case_recent_activity_date',
      90 => 'case_recent_activity_type',
      91 => 'case_scheduled_activity_date',
      92 => 'Case Start Date',
      93 => 'Case End Date',
      94 => 'case_source_contact_id',
      95 => 'case_activity_status',
      96 => 'case_activity_duration',
      97 => 'case_activity_medium_id',
      98 => 'case_activity_details',
      99 => 'case_activity_is_auto',
    ];
  }

  /**
   * Get the definition for contribute headers.
   *
   * @return array
   */
  protected function getContributeHeaderDefinition() {
    return [
      81 => 'Financial Type',
      82 => 'Contribution Source',
      83 => 'Date Received',
      84 => 'Thank-you Date',
      85 => 'Cancel Date',
      86 => 'Total Amount',
      87 => 'Accounting Code',
      88 => 'Payment Methods',
      89 => 'Payment Method ID',
      90 => 'Check Number',
      91 => 'Non-deductible Amount',
      92 => 'Fee Amount',
      93 => 'Net Amount',
      94 => 'Transaction ID',
      95 => 'Invoice Reference',
      96 => 'Invoice Number',
      97 => 'Currency',
      98 => 'Cancellation / Refund Reason',
      99 => 'Receipt Date',
      106 => 'Test',
      107 => 'Is Pay Later',
      108 => 'Contribution Status',
      109 => 'Recurring Contribution ID',
      110 => 'Amount Label',
      111 => 'Contribution Note',
      112 => 'Batch Name',
      113 => 'Campaign Title',
      114 => 'Campaign ID',
      116 => 'Soft Credit For',
      117 => 'Soft Credit Amount',
      118 => 'Soft Credit Type',
      119 => 'Soft Credit For Contact ID',
      120 => 'Soft Credit For Contribution ID',
    ];
  }

  /**
   * Get the definition for event headers.
   *
   * @return array
   */
  protected function getParticipantHeaderDefinition() {
    return [
      81 => 'Event',
      82 => 'Event Title',
      83 => 'Event Start Date',
      84 => 'Event End Date',
      85 => 'Event Type',
      86 => 'Participant ID',
      87 => 'Participant Status',
      88 => 'Participant Status Id',
      89 => 'Participant Role',
      90 => 'Participant Role Id',
      91 => 'Participant Note',
      92 => 'Register date',
      93 => 'Participant Source',
      94 => 'Fee level',
      95 => 'Test',
      96 => 'Is Pay Later',
      97 => 'Fee Amount',
      98 => 'Discount Name',
      99 => 'Fee Currency',
      100 => 'Registered By ID',
      101 => 'Campaign ID',
    ];
  }

  /**
   * Get the definition for member headers.
   *
   * @return array
   */
  protected function getMemberHeaderDefinition() {
    return [
      81 => 'Membership Type',
      82 => 'Test',
      83 => 'Is Pay Later',
      84 => 'Member Since',
      85 => 'Membership Start Date',
      86 => 'Membership Expiration Date',
      87 => 'Source',
      88 => 'Membership Status',
      89 => 'Membership ID',
      90 => 'Primary Member ID',
      91 => 'max_related',
      92 => 'membership_recur_id',
      93 => 'Campaign ID',
      94 => 'member_is_override',
      95 => 'member_auto_renew',
    ];
  }

  /**
   * Get the definition for pledge headers.
   *
   * @return array
   */
  protected function getPledgeHeaderDefinition() {
    return [
      81 => 'Pledge ID',
      82 => 'Total Pledged',
      83 => 'Total Paid',
      84 => 'Pledge Made',
      85 => 'pledge_start_date',
      86 => 'Next Payment Date',
      87 => 'Next Payment Amount',
      88 => 'Pledge Status',
      89 => 'Test',
      90 => 'Pledge Contribution Page Id',
      91 => 'pledge_financial_type',
      92 => 'Pledge Frequency Interval',
      93 => 'Pledge Frequency Unit',
      94 => 'pledge_currency',
      95 => 'Campaign ID',
      96 => 'Balance Amount',
      97 => 'Payment ID',
      98 => 'Scheduled Amount',
      99 => 'Scheduled Date',
      100 => 'Paid Amount',
      101 => 'Paid Date',
      102 => 'Last Reminder',
      103 => 'Reminders Sent',
      104 => 'Pledge Payment Status',
    ];
  }

  /**
   * Get the column definition for exports.
   *
   * @param bool $isContactExport
   *
   * @return array
   */
  protected function getBasicSqlColumnDefinition($isContactExport) {
    $columns = [
      'civicrm_primary_id' => 'civicrm_primary_id varchar(16)',
      'contact_type' => 'contact_type varchar(64)',
      'contact_sub_type' => 'contact_sub_type varchar(255)',
      'do_not_email' => 'do_not_email varchar(16)',
      'do_not_phone' => 'do_not_phone varchar(16)',
      'do_not_mail' => 'do_not_mail varchar(16)',
      'do_not_sms' => 'do_not_sms varchar(16)',
      'do_not_trade' => 'do_not_trade varchar(16)',
      'is_opt_out' => 'is_opt_out varchar(16)',
      'legal_identifier' => 'legal_identifier varchar(32)',
      'external_identifier' => 'external_identifier varchar(64)',
      'sort_name' => 'sort_name varchar(128)',
      'display_name' => 'display_name varchar(128)',
      'nick_name' => 'nick_name varchar(128)',
      'legal_name' => 'legal_name varchar(128)',
      'image_url' => 'image_url longtext',
      'preferred_communication_method' => 'preferred_communication_method varchar(255)',
      'preferred_language' => 'preferred_language varchar(5)',
      'preferred_mail_format' => 'preferred_mail_format varchar(8)',
      'hash' => 'hash varchar(32)',
      'contact_source' => 'contact_source varchar(255)',
      'first_name' => 'first_name varchar(64)',
      'middle_name' => 'middle_name varchar(64)',
      'last_name' => 'last_name varchar(64)',
      'prefix_id' => 'prefix_id varchar(255)',
      'suffix_id' => 'suffix_id varchar(255)',
      'formal_title' => 'formal_title varchar(64)',
      'communication_style_id' => 'communication_style_id varchar(16)',
      'email_greeting_id' => 'email_greeting_id varchar(16)',
      'postal_greeting_id' => 'postal_greeting_id varchar(16)',
      'addressee_id' => 'addressee_id varchar(16)',
      'job_title' => 'job_title varchar(255)',
      'gender_id' => 'gender_id varchar(16)',
      'birth_date' => 'birth_date varchar(32)',
      'is_deceased' => 'is_deceased varchar(16)',
      'deceased_date' => 'deceased_date varchar(32)',
      'household_name' => 'household_name varchar(128)',
      'organization_name' => 'organization_name varchar(128)',
      'sic_code' => 'sic_code varchar(8)',
      'user_unique_id' => 'user_unique_id varchar(255)',
      'current_employer_id' => 'current_employer_id varchar(16)',
      'contact_is_deleted' => 'contact_is_deleted varchar(16)',
      'created_date' => 'created_date varchar(32)',
      'modified_date' => 'modified_date varchar(32)',
      'addressee' => 'addressee varchar(255)',
      'email_greeting' => 'email_greeting varchar(255)',
      'postal_greeting' => 'postal_greeting varchar(255)',
      'current_employer' => 'current_employer varchar(128)',
      'location_type' => 'location_type text',
      'street_address' => 'street_address varchar(96)',
      'street_number' => 'street_number varchar(16)',
      'street_number_suffix' => 'street_number_suffix varchar(8)',
      'street_name' => 'street_name varchar(64)',
      'street_unit' => 'street_unit varchar(16)',
      'supplemental_address_1' => 'supplemental_address_1 varchar(96)',
      'supplemental_address_2' => 'supplemental_address_2 varchar(96)',
      'supplemental_address_3' => 'supplemental_address_3 varchar(96)',
      'city' => 'city varchar(64)',
      'postal_code_suffix' => 'postal_code_suffix varchar(12)',
      'postal_code' => 'postal_code varchar(64)',
      'geo_code_1' => 'geo_code_1 varchar(32)',
      'geo_code_2' => 'geo_code_2 varchar(32)',
      'address_name' => 'address_name varchar(255)',
      'master_id' => 'master_id varchar(128)',
      'county' => 'county varchar(64)',
      'state_province' => 'state_province varchar(64)',
      'country' => 'country varchar(64)',
      'phone' => 'phone varchar(32)',
      'phone_ext' => 'phone_ext varchar(16)',
      'phone_type_id' => 'phone_type_id varchar(16)',
      'email' => 'email varchar(254)',
      'on_hold' => 'on_hold varchar(16)',
      'is_bulkmail' => 'is_bulkmail varchar(16)',
      'signature_text' => 'signature_text longtext',
      'signature_html' => 'signature_html longtext',
      'im_provider' => 'im_provider text',
      'im_screen_name' => 'im_screen_name varchar(64)',
      'openid' => 'openid varchar(255)',
      'world_region' => 'world_region varchar(128)',
      'url' => 'url varchar(128)',
      'groups' => 'groups text',
      'tags' => 'tags text',
      'notes' => 'notes text',
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
  protected function getCaseSqlColumns() {
    return [
      'case_start_date' => 'case_start_date varchar(32)',
      'case_end_date' => 'case_end_date varchar(32)',
      'case_subject' => 'case_subject varchar(128)',
      'case_source_contact_id' => 'case_source_contact_id varchar(255)',
      'case_activity_status' => 'case_activity_status text',
      'case_activity_duration' => 'case_activity_duration text',
      'case_activity_medium_id' => 'case_activity_medium_id varchar(255)',
      'case_activity_details' => 'case_activity_details text',
      'case_activity_is_auto' => 'case_activity_is_auto text',
      'contact_id' => 'contact_id varchar(255)',
      'case_id' => 'case_id varchar(16)',
      'case_activity_subject' => 'case_activity_subject text',
      'case_status' => 'case_status text',
      'case_type' => 'case_type text',
      'case_role' => 'case_role text',
      'case_deleted' => 'case_deleted varchar(16)',
      'case_recent_activity_date' => 'case_recent_activity_date text',
      'case_recent_activity_type' => 'case_recent_activity_type text',
      'case_scheduled_activity_date' => 'case_scheduled_activity_date text',
    ];
  }

  /**
   * Get activity sql columns.
   *
   * @return array
   */
  protected function getActivitySqlColumns() {
    return [
      'activity_id' => 'activity_id varchar(16)',
      'activity_type' => 'activity_type varchar(255)',
      'activity_type_id' => 'activity_type_id varchar(16)',
      'activity_subject' => 'activity_subject varchar(255)',
      'activity_date_time' => 'activity_date_time varchar(32)',
      'activity_duration' => 'activity_duration varchar(16)',
      'activity_location' => 'activity_location varchar(255)',
      'activity_details' => 'activity_details longtext',
      'activity_status' => 'activity_status varchar(255)',
      'activity_priority' => 'activity_priority varchar(255)',
      'source_contact' => 'source_contact varchar(255)',
      'source_record_id' => 'source_record_id varchar(255)',
      'activity_is_test' => 'activity_is_test varchar(16)',
      'activity_campaign_id' => 'activity_campaign_id varchar(128)',
      'result' => 'result text',
      'activity_engagement_level' => 'activity_engagement_level varchar(16)',
      'parent_id' => 'parent_id varchar(255)',
    ];
  }

  /**
   * Get participant sql columns.
   *
   * @return array
   */
  protected function getParticipantSqlColumns() {
    return [
      'event_id' => 'event_id varchar(16)',
      'event_title' => 'event_title varchar(255)',
      'event_start_date' => 'event_start_date varchar(32)',
      'event_end_date' => 'event_end_date varchar(32)',
      'event_type' => 'event_type varchar(255)',
      'participant_id' => 'participant_id varchar(16)',
      'participant_status' => 'participant_status varchar(255)',
      'participant_status_id' => 'participant_status_id varchar(16)',
      'participant_role' => 'participant_role varchar(255)',
      'participant_role_id' => 'participant_role_id varchar(128)',
      'participant_note' => 'participant_note text',
      'participant_register_date' => 'participant_register_date varchar(32)',
      'participant_source' => 'participant_source varchar(128)',
      'participant_fee_level' => 'participant_fee_level longtext',
      'participant_is_test' => 'participant_is_test varchar(16)',
      'participant_is_pay_later' => 'participant_is_pay_later varchar(16)',
      'participant_fee_amount' => 'participant_fee_amount varchar(32)',
      'participant_discount_name' => 'participant_discount_name varchar(16)',
      'participant_fee_currency' => 'participant_fee_currency varchar(3)',
      'participant_registered_by_id' => 'participant_registered_by_id varchar(16)',
      'participant_campaign_id' => 'participant_campaign_id varchar(128)',
    ];
  }

  /**
   * Get contribution sql columns.
   *
   * @return array
   */
  public function getContributionSqlColumns() {
    return [
      'civicrm_primary_id' => 'civicrm_primary_id varchar(16)',
      'contact_type' => 'contact_type varchar(64)',
      'contact_sub_type' => 'contact_sub_type varchar(255)',
      'do_not_email' => 'do_not_email varchar(16)',
      'do_not_phone' => 'do_not_phone varchar(16)',
      'do_not_mail' => 'do_not_mail varchar(16)',
      'do_not_sms' => 'do_not_sms varchar(16)',
      'do_not_trade' => 'do_not_trade varchar(16)',
      'is_opt_out' => 'is_opt_out varchar(16)',
      'legal_identifier' => 'legal_identifier varchar(32)',
      'external_identifier' => 'external_identifier varchar(64)',
      'sort_name' => 'sort_name varchar(128)',
      'display_name' => 'display_name varchar(128)',
      'nick_name' => 'nick_name varchar(128)',
      'legal_name' => 'legal_name varchar(128)',
      'image_url' => 'image_url longtext',
      'preferred_communication_method' => 'preferred_communication_method varchar(255)',
      'preferred_language' => 'preferred_language varchar(5)',
      'preferred_mail_format' => 'preferred_mail_format varchar(8)',
      'hash' => 'hash varchar(32)',
      'contact_source' => 'contact_source varchar(255)',
      'first_name' => 'first_name varchar(64)',
      'middle_name' => 'middle_name varchar(64)',
      'last_name' => 'last_name varchar(64)',
      'prefix_id' => 'prefix_id varchar(255)',
      'suffix_id' => 'suffix_id varchar(255)',
      'formal_title' => 'formal_title varchar(64)',
      'communication_style_id' => 'communication_style_id varchar(16)',
      'email_greeting_id' => 'email_greeting_id varchar(16)',
      'postal_greeting_id' => 'postal_greeting_id varchar(16)',
      'addressee_id' => 'addressee_id varchar(16)',
      'job_title' => 'job_title varchar(255)',
      'gender_id' => 'gender_id varchar(16)',
      'birth_date' => 'birth_date varchar(32)',
      'is_deceased' => 'is_deceased varchar(16)',
      'deceased_date' => 'deceased_date varchar(32)',
      'household_name' => 'household_name varchar(128)',
      'organization_name' => 'organization_name varchar(128)',
      'sic_code' => 'sic_code varchar(8)',
      'user_unique_id' => 'user_unique_id varchar(255)',
      'current_employer_id' => 'current_employer_id varchar(16)',
      'contact_is_deleted' => 'contact_is_deleted varchar(16)',
      'created_date' => 'created_date varchar(32)',
      'modified_date' => 'modified_date varchar(32)',
      'addressee' => 'addressee varchar(255)',
      'email_greeting' => 'email_greeting varchar(255)',
      'postal_greeting' => 'postal_greeting varchar(255)',
      'current_employer' => 'current_employer varchar(128)',
      'location_type' => 'location_type text',
      'street_address' => 'street_address varchar(96)',
      'street_number' => 'street_number varchar(16)',
      'street_number_suffix' => 'street_number_suffix varchar(8)',
      'street_name' => 'street_name varchar(64)',
      'street_unit' => 'street_unit varchar(16)',
      'supplemental_address_1' => 'supplemental_address_1 varchar(96)',
      'supplemental_address_2' => 'supplemental_address_2 varchar(96)',
      'supplemental_address_3' => 'supplemental_address_3 varchar(96)',
      'city' => 'city varchar(64)',
      'postal_code_suffix' => 'postal_code_suffix varchar(12)',
      'postal_code' => 'postal_code varchar(64)',
      'geo_code_1' => 'geo_code_1 varchar(32)',
      'geo_code_2' => 'geo_code_2 varchar(32)',
      'address_name' => 'address_name varchar(255)',
      'master_id' => 'master_id varchar(128)',
      'county' => 'county varchar(64)',
      'state_province' => 'state_province varchar(64)',
      'country' => 'country varchar(64)',
      'phone' => 'phone varchar(32)',
      'phone_ext' => 'phone_ext varchar(16)',
      'email' => 'email varchar(254)',
      'on_hold' => 'on_hold varchar(16)',
      'is_bulkmail' => 'is_bulkmail varchar(16)',
      'signature_text' => 'signature_text longtext',
      'signature_html' => 'signature_html longtext',
      'im_provider' => 'im_provider text',
      'im_screen_name' => 'im_screen_name varchar(64)',
      'openid' => 'openid varchar(255)',
      'world_region' => 'world_region varchar(128)',
      'url' => 'url varchar(128)',
      'phone_type_id' => 'phone_type_id varchar(16)',
      'financial_type' => 'financial_type varchar(255)',
      'contribution_source' => 'contribution_source varchar(255)',
      'receive_date' => 'receive_date varchar(32)',
      'thankyou_date' => 'thankyou_date varchar(32)',
      'cancel_date' => 'cancel_date varchar(32)',
      'total_amount' => 'total_amount varchar(32)',
      'accounting_code' => 'accounting_code varchar(64)',
      'payment_instrument' => 'payment_instrument varchar(255)',
      'payment_instrument_id' => 'payment_instrument_id varchar(16)',
      'contribution_check_number' => 'contribution_check_number varchar(255)',
      'non_deductible_amount' => 'non_deductible_amount varchar(32)',
      'fee_amount' => 'fee_amount varchar(32)',
      'net_amount' => 'net_amount varchar(32)',
      'trxn_id' => 'trxn_id varchar(255)',
      'invoice_id' => 'invoice_id varchar(255)',
      'invoice_number' => 'invoice_number varchar(255)',
      'currency' => 'currency varchar(3)',
      'cancel_reason' => 'cancel_reason longtext',
      'receipt_date' => 'receipt_date varchar(32)',
      'is_test' => 'is_test varchar(16)',
      'is_pay_later' => 'is_pay_later varchar(16)',
      'contribution_status' => 'contribution_status varchar(255)',
      'contribution_recur_id' => 'contribution_recur_id varchar(16)',
      'amount_level' => 'amount_level longtext',
      'contribution_note' => 'contribution_note text',
      'contribution_batch' => 'contribution_batch text',
      'contribution_campaign_title' => 'contribution_campaign_title varchar(255)',
      'contribution_campaign_id' => 'contribution_campaign_id varchar(128)',
      'contribution_soft_credit_name' => 'contribution_soft_credit_name varchar(255)',
      'contribution_soft_credit_amount' => 'contribution_soft_credit_amount varchar(255)',
      'contribution_soft_credit_type' => 'contribution_soft_credit_type varchar(255)',
      'contribution_soft_credit_contact_id' => 'contribution_soft_credit_contact_id varchar(255)',
      'contribution_soft_credit_contribution_id' => 'contribution_soft_credit_contribution_id varchar(255)',
    ];
  }

  /**
   * Get pledge sql columns.
   *
   * @return array
   */
  public function getPledgeSqlColumns() {
    return [
      'pledge_id' => 'pledge_id varchar(16)',
      'pledge_amount' => 'pledge_amount varchar(32)',
      'pledge_total_paid' => 'pledge_total_paid text',
      'pledge_create_date' => 'pledge_create_date varchar(32)',
      'pledge_start_date' => 'pledge_start_date text',
      'pledge_next_pay_date' => 'pledge_next_pay_date text',
      'pledge_next_pay_amount' => 'pledge_next_pay_amount text',
      'pledge_status' => 'pledge_status varchar(255)',
      'pledge_is_test' => 'pledge_is_test varchar(16)',
      'pledge_contribution_page_id' => 'pledge_contribution_page_id varchar(255)',
      'pledge_financial_type' => 'pledge_financial_type text',
      'pledge_frequency_interval' => 'pledge_frequency_interval varchar(255)',
      'pledge_frequency_unit' => 'pledge_frequency_unit varchar(255)',
      'pledge_currency' => 'pledge_currency text',
      'pledge_campaign_id' => 'pledge_campaign_id varchar(128)',
      'pledge_balance_amount' => 'pledge_balance_amount text',
      'pledge_payment_id' => 'pledge_payment_id varchar(16)',
      'pledge_payment_scheduled_amount' => 'pledge_payment_scheduled_amount varchar(32)',
      'pledge_payment_scheduled_date' => 'pledge_payment_scheduled_date varchar(32)',
      'pledge_payment_paid_amount' => 'pledge_payment_paid_amount text',
      'pledge_payment_paid_date' => 'pledge_payment_paid_date text',
      'pledge_payment_reminder_date' => 'pledge_payment_reminder_date varchar(32)',
      'pledge_payment_reminder_count' => 'pledge_payment_reminder_count varchar(16)',
      'pledge_payment_status' => 'pledge_payment_status varchar(255)',
    ];
  }

  /**
   * Get membership sql columns.
   *
   * @return array
   */
  public function getMembershipSqlColumns() {
    return [
      'membership_type' => 'membership_type varchar(128)',
      'member_is_test' => 'member_is_test varchar(16)',
      'member_is_pay_later' => 'member_is_pay_later varchar(16)',
      'join_date' => 'join_date varchar(32)',
      'membership_start_date' => 'membership_start_date varchar(32)',
      'membership_end_date' => 'membership_end_date varchar(32)',
      'membership_source' => 'membership_source varchar(128)',
      'membership_status' => 'membership_status varchar(255)',
      'membership_id' => 'membership_id varchar(16)',
      'owner_membership_id' => 'owner_membership_id varchar(16)',
      'max_related' => 'max_related text',
      'membership_recur_id' => 'membership_recur_id varchar(255)',
      'member_campaign_id' => 'member_campaign_id varchar(128)',
      'member_is_override' => 'member_is_override text',
      'member_auto_renew' => 'member_auto_renew text',
    ];
  }

  /**
   * Change our location types so we have some edge cases in the mix.
   *
   * - a space in the name
   * - name differs from label
   * - non-anglo char in the label (not valid in the name).
   */
  protected function diversifyLocationTypes() {
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

}
