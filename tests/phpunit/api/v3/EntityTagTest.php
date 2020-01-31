<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *  Test APIv3 civicrm_entity_tag_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Core
 */

/**
 * Class api_v3_EntityTagTest.
 * @group headless
 */
class api_v3_EntityTagTest extends CiviUnitTestCase {

  /**
   * @var int
   */
  protected $_individualID;
  protected $_householdID;
  protected $_organizationID;
  protected $_tagID;
  protected $_apiversion = 3;
  protected $_tag;
  protected $_entity = 'entity_tag';

  /**
   * Basic parameters for create.
   *
   * @var array
   */
  protected $_params = [];

  /**
   * Set up for test.
   */
  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);

    $this->_individualID = $this->individualCreate();
    $this->_tag = $this->tagCreate(['name' => 'EntityTagTest']);
    $this->_tagID = $this->_tag['id'];
    $this->_householdID = $this->houseHoldCreate();
    $this->_organizationID = $this->organizationCreate();
    $this->_params = [
      'entity_id' => $this->_individualID,
      'tag_id' => $this->_tagID,
    ];
  }

  /**
   * Test required parameters.
   *
   * These failure tests are low value and may not be worth putting in v4.
   */
  public function testFailureTests() {
    $this->callAPIFailure('entity_tag', 'create', ['contact_id' => $this->_individualID],
      'tag_id is a required field'
    );
    $this->callAPIFailure('entity_tag', 'create', ['tag_id' => $this->_tagID],
      'contact_id is a required field'
    );
  }

  /**
   * Test basic create.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testContactEntityTagCreate($version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess('entity_tag', 'create', $this->_params);
  }

  /**
   * Test multiple add functionality.
   *
   * This needs review for api v4 as it makes for a very non standard api.
   */
  public function testAddDouble() {

    $result = $this->callAPISuccess('entity_tag', 'create', $this->_params);
    $this->assertEquals($result['added'], 1);

    $params = [
      'contact_id_i' => $this->_individualID,
      'contact_id_o' => $this->_organizationID,
      'tag_id' => $this->_tagID,
    ];

    $result = $this->callAPISuccess('entity_tag', 'create', $params);
    $this->assertEquals($result['added'], 1);
    $this->assertEquals($result['not_added'], 1);
  }

  /**
   * Test that get works without an entity.
   */
  public function testGetNoEntityID() {
    $this->callAPISuccess('entity_tag', 'create', $this->_params);
    $result = $this->callAPISuccess($this->_entity, 'get', ['sequential' => 1, 'tag_id' => $this->_tagID]);
    $this->assertEquals($this->_individualID, $result['values'][0]['entity_id']);
  }

  /**
   * Basic get functionality test.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testIndividualEntityTagGet($version) {
    $this->_apiversion = $version;
    $individualEntity = $this->callAPISuccess('entity_tag', 'create', $this->_params);

    $paramsEntity = [
      'contact_id' => $this->_individualID,
    ];
    $result = $this->callAPIAndDocument('entity_tag', 'get', $paramsEntity, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertEquals($this->_tagID, $result['values'][$result['id']]['tag_id']);
  }

  /**
   * Test memory usage does not escalate crazily.
   */
  public function testMemoryLeak() {
    $start = memory_get_usage();
    for ($i = 0; $i < 100; $i++) {
      $this->callAPISuccess('EntityTag', 'get', []);
      $memUsage = memory_get_usage();
    }
    $max = $start + 2000000;
    $this->assertTrue($memUsage < $max, "mem usage ( $memUsage ) should be less than $max (start was $start) ");
  }

  /**
   * Test tag can be added to a household.
   */
  public function testHouseholdEntityCreate() {
    $params = [
      'contact_id' => $this->_householdID,
      'tag_id' => $this->_tagID,
    ];

    $householdEntity = $this->callAPISuccess('entity_tag', 'create', $params);
    $this->assertEquals($householdEntity['added'], 1);
  }

  /**
   * Test tag can be added to an organization.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testOrganizationEntityGet($version) {
    $this->_apiversion = $version;

    $params = [
      'entity_id' => $this->_organizationID,
      'tag_id' => $this->_tagID,
    ];

    $this->callAPISuccess('entity_tag', 'create', $params);

    $tag = $this->callAPISuccess('entity_tag', 'getsingle', ['contact_id' => $this->_organizationID]);
    $this->assertEquals($this->_organizationID, $tag['entity_id']);
    $this->assertEquals($this->_tagID, $tag['tag_id']);
  }

  /**
   * Civicrm_entity_tag_Delete methods.
   */
  public function testEntityTagDeleteNoTagId() {
    $entityTagParams = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    ];
    $this->entityTagAdd($entityTagParams);

    $params = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
    ];

    $result = $this->callAPISuccess('entity_tag', 'delete', $params);

    $this->assertEquals($result['not_removed'], 0);
    $this->assertEquals($result['removed'], 2);
    $this->assertEquals($result['total_count'], 2);
  }

  public function testEntityTagDeleteINDHH() {
    $entityTagParams = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    ];
    $this->entityTagAdd($entityTagParams);

    $params = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    ];

    $result = $this->callAPISuccess('entity_tag', 'delete', $params);

    $this->assertEquals($result['removed'], 2);
  }

  public function testEntityTagDeleteHH() {
    $entityTagParams = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    ];
    $this->entityTagAdd($entityTagParams);

    $params = [
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    ];

    $result = $this->callAPIAndDocument('entity_tag', 'delete', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['removed'], 1);
  }

  public function testEntityTagDeleteHHORG() {
    $entityTagParams = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    ];
    $this->entityTagAdd($entityTagParams);

    $params = [
      'contact_id_h' => $this->_householdID,
      'contact_id_o' => $this->_organizationID,
      'tag_id' => $this->_tagID,
    ];

    $result = $this->callAPISuccess('entity_tag', 'delete', $params);
    $this->assertEquals($result['removed'], 1);
    $this->assertEquals($result['not_removed'], 1);
  }

  public function testEntityTagCommonDeleteINDHH() {
    $entityTagParams = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    ];
    $this->entityTagAdd($entityTagParams);

    $params = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    ];

    $result = $this->callAPISuccess('entity_tag', 'delete', $params);
    $this->assertEquals($result['removed'], 2);
  }

  public function testEntityTagCommonDeleteHH() {
    $entityTagParams = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    ];
    $this->entityTagAdd($entityTagParams);

    $params = [
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    ];

    $result = $this->callAPISuccess('entity_tag', 'delete', $params);
    $this->assertEquals($result['removed'], 1);
  }

  public function testEntityTagCommonDeleteHHORG() {
    $entityTagParams = [
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    ];
    $this->entityTagAdd($entityTagParams);

    $params = [
      'contact_id_h' => $this->_householdID,
      'contact_id_o' => $this->_organizationID,
      'tag_id' => $this->_tagID,
    ];

    $result = $this->callAPISuccess('entity_tag', 'delete', $params);
    $this->assertEquals($result['removed'], 1);
    $this->assertEquals($result['not_removed'], 1);
  }

  public function testEntityTagJoin() {
    $org = $this->callAPISuccess('Contact', 'create', [
      'contact_type' => 'Organization',
      'organization_name' => 'Org123',
      'api.EntityTag.create' => [
        'tag_id' => $this->_tagID,
      ],
    ]);
    // Fetch contact info via join
    $result = $this->callAPISuccessGetSingle('EntityTag', [
      'return' => ["entity_id.organization_name", "tag_id.name"],
      'entity_id' => $org['id'],
      'entity_table' => "civicrm_contact",
    ]);
    $this->assertEquals('Org123', $result['entity_id.organization_name']);
    $this->assertEquals('EntityTagTest', $result['tag_id.name']);
    // This should return no results by restricting contact_type
    $result = $this->callAPISuccess('EntityTag', 'get', [
      'return' => ["entity_id.organization_name"],
      'entity_id' => $org['id'],
      'entity_table' => "civicrm_contact",
      'entity_id.contact_type' => "Individual",
    ]);
    $this->assertEquals(0, $result['count']);
  }

}
