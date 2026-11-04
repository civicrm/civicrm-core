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
   *
   * @throws \CRM_Core_Exception
   */
  public function setUp(): void {
    parent::setUp();
    $this->useTransaction(TRUE);

    $this->_individualID = $this->individualCreate();
    $this->_tag = $this->tagCreate(['name' => 'EntityTagTest']);
    $this->_tagID = $this->_tag['id'];
    $this->_householdID = $this->householdCreate();
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
  public function testFailureTests(): void {
    $this->callAPIFailure('entity_tag', 'create', ['contact_id' => $this->_individualID],
      'tag_id is a required field'
    );
    $this->callAPIFailure('entity_tag', 'create', ['tag_id' => $this->_tagID],
      'contact_id is a required field'
    );
  }

  /**
   * Test basic create.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testContactEntityTagCreate($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess('entity_tag', 'create', $this->_params);
  }

  /**
   * Test multiple add functionality.
   *
   * This needs review for api v4 as it makes for a very non standard api.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddDouble(): void {

    $result = $this->callAPISuccess('entity_tag', 'create', $this->_params);
    $this->assertSame(1, $result['added']);

    $params = [
      'contact_id_i' => $this->_individualID,
      'contact_id_o' => $this->_organizationID,
      'tag_id' => $this->_tagID,
    ];

    $result = $this->callAPISuccess('entity_tag', 'create', $params);
    $this->assertSame(1, $result['added']);
    $this->assertSame(1, $result['not_added']);
  }

  /**
   * Test that get works without an entity.
   */
  public function testGetNoEntityID(): void {
    $this->callAPISuccess('entity_tag', 'create', $this->_params);
    $result = $this->callAPISuccess($this->_entity, 'get', ['sequential' => 1, 'tag_id' => $this->_tagID]);
    $this->assertEquals($this->_individualID, $result['values'][0]['entity_id']);
  }

  /**
   * Basic get functionality test.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testIndividualEntityTagGet($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess('entity_tag', 'create', $this->_params);

    $paramsEntity = [
      'contact_id' => $this->_individualID,
    ];
    $result = $this->callAPISuccess('entity_tag', 'get', $paramsEntity);
    $this->assertSame(1, $result['count']);
    $this->assertEquals($this->_tagID, $result['values'][$result['id']]['tag_id']);
  }

  /**
   * Test memory usage does not escalate crazily.
   *
   * @throws \CRM_Core_Exception
   */
  public function testMemoryLeak(): void {
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
  public function testHouseholdEntityCreate(): void {
    $params = [
      'contact_id' => $this->_householdID,
      'tag_id' => $this->_tagID,
    ];

    $householdEntity = $this->callAPISuccess('entity_tag', 'create', $params);
    $this->assertSame(1, $householdEntity['added']);
  }

  /**
   * Test tag can be added to an organization.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testEntityTagDeleteNoTagId(): void {
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

    $this->assertSame(0, $result['not_removed']);
    $this->assertSame(2, $result['removed']);
    $this->assertSame(2, $result['total_count']);
  }

  public function testEntityTagDeleteINDHH(): void {
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

    $this->assertSame(2, $result['removed']);
  }

  public function testEntityTagDeleteHH(): void {
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
    $this->assertSame(1, $result['removed']);
  }

  public function testEntityTagDeleteHHORG(): void {
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
    $this->assertSame(1, $result['removed']);
    $this->assertSame(1, $result['not_removed']);
  }

  public function testEntityTagCommonDeleteHH(): void {
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
    $this->assertSame(1, $result['removed']);
  }

  public function testEntityTagCommonDeleteHHORG(): void {
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
    $this->assertSame(1, $result['removed']);
    $this->assertSame(1, $result['not_removed']);
  }

  public function testEntityTagJoin(): void {
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
    $this->assertSame('Org123', $result['entity_id.organization_name']);
    $this->assertSame('EntityTagTest', $result['tag_id.name']);
    // This should return no results by restricting contact_type
    $result = $this->callAPISuccess('EntityTag', 'get', [
      'return' => ["entity_id.organization_name"],
      'entity_id' => $org['id'],
      'entity_table' => "civicrm_contact",
      'entity_id.contact_type' => "Individual",
    ]);
    $this->assertSame(0, $result['count']);
  }

}
