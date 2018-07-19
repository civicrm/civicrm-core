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

    list($tableName, $sqlColumns) = CRM_Export_BAO_Export::exportComponents(
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
    $processor = new CRM_Export_BAO_ExportProcessor(CRM_Contact_BAO_Query::MODE_CONTRIBUTE, 'AND');
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
    $this->membershipIDs[] = $this->contactMembershipCreate(['contact_id' => $this->contactIDs[0]]);
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
    list($tableName, $sqlColumns) = CRM_Export_BAO_Export::exportComponents(
      TRUE,
      $this->contactIDs[1],
      array(),
      NULL,
      $selectedFields,
      NULL,
      CRM_Export_Form_Select::CONTACT_EXPORT,
      "contact_a.id IN (" . implode(",", $this->contactIDs) . ")",
      NULL,
      FALSE,
      FALSE,
      array(
        'exportOption' => CRM_Export_Form_Select::CONTACT_EXPORT,
        'suppress_csv_for_testing' => TRUE,
      )
    );
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
    ];
    list($tableName) = CRM_Export_BAO_Export::exportComponents(
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
    }

  }

  /**
   * Test exporting relationships.
   */
  public function testExportRelationshipsMergeToHouseholdAllFields() {
    $this->markTestIncomplete('Does not yet work under CI due to mysql limitation (number of columns in table). Works on some boxes');
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
      $this->assertEquals('Portland', $dao->city);
      $this->assertEquals('ME', $dao->state_province);
      $this->assertEquals($householdID, $dao->civicrm_primary_id);
      $this->assertEquals($householdID, $dao->civicrm_primary_id);
      $this->assertEquals('Unit Test Household', $dao->addressee);
      $this->assertEquals('Unit Test Household', $dao->display_name);
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
      'billing_city' => 'billing_city text',
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
    $locationTypes = ['Billing', 'Home', 'Main', 'Other'];

    $this->contactIDs[] = $this->individualCreate();
    $this->contactIDs[] = $this->individualCreate();
    $this->contactIDs[] = $this->householdCreate();
    $this->contactIDs[] = $this->organizationCreate();
    foreach ($this->contactIDs as $contactID) {
      foreach ($providers as $provider) {
        foreach ($locationTypes as $locationType) {
          $this->callAPISuccess('IM', 'create', [
            'contact_id' => $contactID,
            'location_type_id' => $locationType,
            'provider_id' => $provider,
            'name' => $locationType . $provider . $contactID,
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
    foreach (array_merge($locationTypes, [' ']) as $locationType) {
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
    list($tableName) = $this->doExport($fields, $this->contactIDs[0]);

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

    // early return for now until we solve a leakage issue.
    return;

    $this->assertEquals([
      'billing_im_provider' => 'billing_im_provider text',
      'billing_im_screen_name' => 'billing_im_screen_name text',
      'billing_im_screen_name_jabber' => 'billing_im_screen_name_jabber text',
      'billing_im_screen_name_skype' => 'billing_im_screen_name_skype text',
      'billing_im_screen_name_yahoo' => 'billing_im_screen_name_yahoo text',
      'home_im_provider' => 'home_im_provider text',
      'home_im_screen_name' => 'home_im_screen_name text',
      'home_im_screen_name_jabber' => 'home_im_screen_name_jabber text',
      'home_im_screen_name_skype' => 'home_im_screen_name_skype text',
      'home_im_screen_name_yahoo' => 'home_im_screen_name_yahoo text',
      'main_im_provider' => 'main_im_provider text',
      'main_im_screen_name' => 'main_im_screen_name text',
      'main_im_screen_name_jabber' => 'main_im_screen_name_jabber text',
      'main_im_screen_name_skype' => 'main_im_screen_name_skype text',
      'main_im_screen_name_yahoo' => 'main_im_screen_name_yahoo text',
      'other_im_provider' => 'other_im_provider text',
      'other_im_screen_name' => 'other_im_screen_name text',
      'other_im_screen_name_jabber' => 'other_im_screen_name_jabber text',
      'other_im_screen_name_skype' => 'other_im_screen_name_skype text',
      'other_im_screen_name_yahoo' => 'other_im_screen_name_yahoo text',
      'im_provider' => 'im_provider text',
      'im' => 'im varchar(64)',
      'contact_id' => 'contact_id varchar(255)',
      '2_a_b_im_provider' => '2_a_b_im_provider text',
      '2_a_b_billing_im_screen_name' => '2_a_b_billing_im_screen_name text',
      '2_a_b_billing_im_screen_name_jabber' => '2_a_b_billing_im_screen_name_jabber text',
      '2_a_b_billing_im_screen_name_skype' => '2_a_b_billing_im_screen_name_skype text',
      '2_a_b_billing_im_screen_name_yahoo' => '2_a_b_billing_im_screen_name_yahoo text',
      '2_a_b_home_im_screen_name' => '2_a_b_home_im_screen_name text',
      '2_a_b_home_im_screen_name_jabber' => '2_a_b_home_im_screen_name_jabber text',
      '2_a_b_home_im_screen_name_skype' => '2_a_b_home_im_screen_name_skype text',
      '2_a_b_home_im_screen_name_yahoo' => '2_a_b_home_im_screen_name_yahoo text',
      '2_a_b_main_im_screen_name' => '2_a_b_main_im_screen_name text',
      '2_a_b_main_im_screen_name_jabber' => '2_a_b_main_im_screen_name_jabber text',
      '2_a_b_main_im_screen_name_skype' => '2_a_b_main_im_screen_name_skype text',
      '2_a_b_main_im_screen_name_yahoo' => '2_a_b_main_im_screen_name_yahoo text',
      '2_a_b_other_im_screen_name' => '2_a_b_other_im_screen_name text',
      '2_a_b_other_im_screen_name_jabber' => '2_a_b_other_im_screen_name_jabber text',
      '2_a_b_other_im_screen_name_skype' => '2_a_b_other_im_screen_name_skype text',
      '2_a_b_other_im_screen_name_yahoo' => '2_a_b_other_im_screen_name_yahoo text',
      '2_a_b_im' => '2_a_b_im text',
      '8_a_b_im_provider' => '8_a_b_im_provider text',
      '8_a_b_billing_im_screen_name' => '8_a_b_billing_im_screen_name text',
      '8_a_b_billing_im_screen_name_jabber' => '8_a_b_billing_im_screen_name_jabber text',
      '8_a_b_billing_im_screen_name_skype' => '8_a_b_billing_im_screen_name_skype text',
      '8_a_b_billing_im_screen_name_yahoo' => '8_a_b_billing_im_screen_name_yahoo text',
      '8_a_b_home_im_screen_name' => '8_a_b_home_im_screen_name text',
      '8_a_b_home_im_screen_name_jabber' => '8_a_b_home_im_screen_name_jabber text',
      '8_a_b_home_im_screen_name_skype' => '8_a_b_home_im_screen_name_skype text',
      '8_a_b_home_im_screen_name_yahoo' => '8_a_b_home_im_screen_name_yahoo text',
      '8_a_b_main_im_screen_name' => '8_a_b_main_im_screen_name text',
      '8_a_b_main_im_screen_name_jabber' => '8_a_b_main_im_screen_name_jabber text',
      '8_a_b_main_im_screen_name_skype' => '8_a_b_main_im_screen_name_skype text',
      '8_a_b_main_im_screen_name_yahoo' => '8_a_b_main_im_screen_name_yahoo text',
      '8_a_b_other_im_screen_name' => '8_a_b_other_im_screen_name text',
      '8_a_b_other_im_screen_name_jabber' => '8_a_b_other_im_screen_name_jabber text',
      '8_a_b_other_im_screen_name_skype' => '8_a_b_other_im_screen_name_skype text',
      '8_a_b_other_im_screen_name_yahoo' => '8_a_b_other_im_screen_name_yahoo text',
      '8_a_b_im' => '8_a_b_im text',
      '5_a_b_im_provider' => '5_a_b_im_provider text',
      '5_a_b_billing_im_screen_name' => '5_a_b_billing_im_screen_name text',
      '5_a_b_billing_im_screen_name_jabber' => '5_a_b_billing_im_screen_name_jabber text',
      '5_a_b_billing_im_screen_name_skype' => '5_a_b_billing_im_screen_name_skype text',
      '5_a_b_billing_im_screen_name_yahoo' => '5_a_b_billing_im_screen_name_yahoo text',
      '5_a_b_home_im_screen_name' => '5_a_b_home_im_screen_name text',
      '5_a_b_home_im_screen_name_jabber' => '5_a_b_home_im_screen_name_jabber text',
      '5_a_b_home_im_screen_name_skype' => '5_a_b_home_im_screen_name_skype text',
      '5_a_b_home_im_screen_name_yahoo' => '5_a_b_home_im_screen_name_yahoo text',
      '5_a_b_main_im_screen_name' => '5_a_b_main_im_screen_name text',
      '5_a_b_main_im_screen_name_jabber' => '5_a_b_main_im_screen_name_jabber text',
      '5_a_b_main_im_screen_name_skype' => '5_a_b_main_im_screen_name_skype text',
      '5_a_b_main_im_screen_name_yahoo' => '5_a_b_main_im_screen_name_yahoo text',
      '5_a_b_other_im_screen_name' => '5_a_b_other_im_screen_name text',
      '5_a_b_other_im_screen_name_jabber' => '5_a_b_other_im_screen_name_jabber text',
      '5_a_b_other_im_screen_name_skype' => '5_a_b_other_im_screen_name_skype text',
      '5_a_b_other_im_screen_name_yahoo' => '5_a_b_other_im_screen_name_yahoo text',
      '5_a_b_im' => '5_a_b_im text',
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
   */
  public function testExportDeceasedDoNotMail() {
    $contactA = $this->callAPISuccess('contact', 'create', array(
      'first_name' => 'John',
      'last_name' => 'Doe',
      'contact_type' => 'Individual',
    ));

    $contactB = $this->callAPISuccess('contact', 'create', array(
      'first_name' => 'Jane',
      'last_name' => 'Doe',
      'contact_type' => 'Individual',
      'is_deceased' => 1,
    ));

    //create address for contact A
    $this->callAPISuccess('address', 'create', array(
      'contact_id' => $contactA['id'],
      'location_type_id' => 'Home',
      'street_address' => 'ABC 12',
      'postal_code' => '123 AB',
      'country_id' => '1152',
      'city' => 'ABC',
      'is_primary' => 1,
    ));

    //create address for contact B
    $this->callAPISuccess('address', 'create', array(
      'contact_id' => $contactB['id'],
      'location_type_id' => 'Home',
      'street_address' => 'ABC 12',
      'postal_code' => '123 AB',
      'country_id' => '1152',
      'city' => 'ABC',
      'is_primary' => 1,
    ));

    //export and merge contacts with same address
    list($tableName, $sqlColumns) = CRM_Export_BAO_Export::exportComponents(
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

    $greeting = CRM_Core_DAO::singleValueQuery("SELECT email_greeting FROM {$tableName}");

    //Assert email_greeting is not merged
    $this->assertNotContains(',', (string) $greeting);

    // delete the export temp table and component table
    $sql = "DROP TABLE IF EXISTS {$tableName}";
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * @return array
   */
  protected function setUpHousehold() {
    $this->setUpContactExportData();
    $householdID = $this->householdCreate([
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
   * @param $selectedFields
   * @return array
   */
  protected function doExport($selectedFields, $id) {
    list($tableName, $sqlColumns) = CRM_Export_BAO_Export::exportComponents(
      TRUE,
      array($id),
      array(),
      NULL,
      $selectedFields,
      NULL,
      CRM_Export_Form_Select::CONTACT_EXPORT,
      "contact_a.id IN ({$id})",
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
   * Test the column definition when 'all' fields defined.
   *
   * @param $exportMode
   * @param $expected
   *
   * @dataProvider getSqlColumnsOutput
   */
  public function testGetSQLColumns($exportMode, $expected) {
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
      ],
      [
        CRM_Export_Form_Select::ACTIVITY_EXPORT,
        array_merge($this->getBasicSqlColumnDefinition(FALSE), $this->getActivitySqlColumns()),
      ],
      [
        CRM_Export_Form_Select::CASE_EXPORT,
        array_merge($this->getBasicSqlColumnDefinition(FALSE), $this->getCaseSqlColumns()),
      ],
      [
        CRM_Export_Form_Select::CONTRIBUTE_EXPORT,
        array_merge($this->getBasicSqlColumnDefinition(FALSE), $this->getContributionSqlColumns()),
      ],
      [
        CRM_Export_Form_Select::EVENT_EXPORT,
        array_merge($this->getBasicSqlColumnDefinition(FALSE), $this->getParticipantSqlColumns()),
      ],
      [
        CRM_Export_Form_Select::MEMBER_EXPORT,
        array_merge($this->getBasicSqlColumnDefinition(FALSE), $this->getMembershipSqlColumns()),
      ],
      [
        CRM_Export_Form_Select::PLEDGE_EXPORT,
        array_merge($this->getBasicSqlColumnDefinition(FALSE), $this->getPledgeSqlColumns()),
      ],

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
      'email' => 'email varchar(254)',
      'on_hold' => 'on_hold varchar(16)',
      'is_bulkmail' => 'is_bulkmail varchar(16)',
      'signature_text' => 'signature_text longtext',
      'signature_html' => 'signature_html longtext',
      'im_provider' => 'im_provider text',
      'im' => 'im varchar(64)',
      'openid' => 'openid varchar(255)',
      'world_region' => 'world_region varchar(128)',
      'url' => 'url varchar(128)',
      'groups' => 'groups text',
      'tags' => 'tags text',
      'notes' => 'notes text',
      'phone_type_id' => 'phone_type_id varchar(255)',
      'provider_id' => 'provider_id varchar(255)',
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
      'im' => 'im varchar(64)',
      'openid' => 'openid varchar(255)',
      'world_region' => 'world_region varchar(128)',
      'url' => 'url varchar(128)',
      'phone_type_id' => 'phone_type_id varchar(255)',
      'provider_id' => 'provider_id varchar(255)',
      'financial_type' => 'financial_type varchar(64)',
      'contribution_source' => 'contribution_source varchar(255)',
      'receive_date' => 'receive_date varchar(32)',
      'thankyou_date' => 'thankyou_date varchar(32)',
      'cancel_date' => 'cancel_date varchar(32)',
      'total_amount' => 'total_amount varchar(32)',
      'accounting_code' => 'accounting_code varchar(64)',
      'payment_instrument' => 'payment_instrument text',
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
      'product_name' => 'product_name varchar(255)',
      'sku' => 'sku varchar(50)',
      'product_option' => 'product_option varchar(255)',
      'fulfilled_date' => 'fulfilled_date varchar(32)',
      'contribution_start_date' => 'contribution_start_date varchar(32)',
      'contribution_end_date' => 'contribution_end_date varchar(32)',
      'is_test' => 'is_test varchar(16)',
      'is_pay_later' => 'is_pay_later varchar(16)',
      'contribution_status' => 'contribution_status text',
      'contribution_recur_id' => 'contribution_recur_id varchar(16)',
      'amount_level' => 'amount_level longtext',
      'contribution_note' => 'contribution_note text',
      'contribution_batch' => 'contribution_batch text',
      'contribution_campaign_title' => 'contribution_campaign_title varchar(255)',
      'contribution_campaign_id' => 'contribution_campaign_id varchar(128)',
      'contribution_product_id' => 'contribution_product_id varchar(255)',
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

}
