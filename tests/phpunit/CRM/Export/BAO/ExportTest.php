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
  protected $contactIDs = array();

  /**
   * Contribution IDs created for testing.
   *
   * @var array
   */
  protected $contributionIDs = array();

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
    $phoneTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    $imProviders = CRM_Core_PseudoConstant::get('CRM_Core_DAO_IM', 'provider_id');
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

    list($outputFields) = CRM_Export_BAO_Export::getExportStructureArrays($returnProperties, $query, $phoneTypes, $imProviders, $contactRelationshipTypes, '', array());
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
    $this->contributionIDs[] = $this->contributionCreate(array('contact_id' => $this->contactIDs[0]));
  }

  /**
   * Set up some data for us to do testing on.
   */
  public function setUpContactExportData() {
    $this->contactIDs[] = $this->individualCreate();
  }

  public function testExportMasterAddress() {
    $contactA = $this->individualCreate(array(), 0);
    $contactB = $this->individualCreate(array(), 1);

    //create address for contact A
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

    //share address with contact B
    $result = $this->callAPISuccess('address', 'create', array(
      'contact_id' => $contactB,
      'location_type_id' => "Home",
      'master_id' => $addressId,
    ));

    //export the master address for contact B
    $selectedFields = array(
      array('Individual', 'master_id', 1),
    );
    list($tableName, $sqlColumns) = CRM_Export_BAO_Export::exportComponents(
      TRUE,
      array($contactB),
      array(),
      NULL,
      $selectedFields,
      NULL,
      CRM_Export_Form_Select::CONTACT_EXPORT,
      "contact_a.id IN ({$contactB})",
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
    $displayName = CRM_Contact_BAO_Contact::getMasterDisplayName(NULL, $contactB);
    $this->assertEquals($displayName, $masterName);

    // delete the export temp table and component table
    $sql = "DROP TABLE IF EXISTS {$tableName}";
    CRM_Core_DAO::executeQuery($sql);
  }

}
