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
   * Master Address ID created for testing.
   *
   * @var int
   */
  protected $masterAddressID;

  public function tearDown() {
    $this->quickCleanup(['civicrm_contact', 'civicrm_email', 'civicrm_address', 'civicrm_relationship']);
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Basic test to ensure the exportComponents function completes without error.
   */
  public function testExportComponentsNull() {
    list($tableName, $sqlColumns) = CRM_Export_BAO_Export::exportComponents(
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

    list($outputFields) = CRM_Export_BAO_Export::getExportStructureArrays($returnProperties, $query, $contactRelationshipTypes, '', array());
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
    list($tableName, $sqlColumns) = $this->doExport($fields, $this->contactIDs[0]);
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
    $result = $this->callAPISuccess('address', 'create', array(
      'contact_id' => $contactA['id'],
      'location_type_id' => 'Home',
      'street_address' => 'ABC 12',
      'postal_code' => '123 AB',
      'country_id' => '1152',
      'city' => 'ABC',
      'is_primary' => 1,
    ));

    //create address for contact B
    $result = $this->callAPISuccess('address', 'create', array(
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

}
