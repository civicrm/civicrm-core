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
 * Test class for UFMatch api.
 *
 * @package CiviCRM
 * @group headless
 */
class api_v3_UFMatchTest extends CiviUnitTestCase {

  public function tearDown(): void {
    $this->quickCleanup([
      'civicrm_group',
      'civicrm_uf_group',
      'civicrm_uf_join',
      'civicrm_uf_match',
    ]);
    parent::tearDown();
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testCreate(int $version): void {
    $this->_apiversion = $version;
    $params = [
      'contact_id' => $this->individualCreate(),
      'uf_id' => '2',
      'uf_name' => 'blahdyblah@gmail.com',
      'domain_id' => 1,
    ];
    $result = $this->callAPISuccess('UFMatch', 'create', $params);
    $this->getAndCheck($params, $result['id'], 'uf_match');
  }

  /**
   * Test Civi to CMS email sync optional
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testUFNameMatchSync(int $version): void {
    $this->_apiversion = $version;
    $this->createTestEntity('UFMatch', [
      'contact_id' => $this->individualCreate(),
      'uf_id' => '2',
      'uf_name' => 'blahdyblah@gmail.com',
      'domain_id' => 1,
    ]);
    $email1 = 'a@test.com';
    $email2 = 'b@test.com';

    // Case A: Enable CMS integration
    Civi::settings()->set('syncCMSEmail', TRUE);
    $this->callAPISuccess('email', 'create', [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'email' => $email1,
      'is_primary' => 1,
    ]);
    $ufName = $this->callAPISuccess('UFMatch', 'getvalue', [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'return' => 'uf_name',
    ]);
    $this->assertEquals($email1, $ufName);

    // Case B: Disable CMS integration
    Civi::settings()->set('syncCMSEmail', FALSE);
    $this->callAPISuccess('Email', 'create', [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'email' => $email2,
      'is_primary' => 1,
    ]);
    $ufName = $this->callAPISuccess('UFMatch', 'getvalue', [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'return' => 'uf_name',
    ]);
    $this->assertNotEquals($email2, $ufName, 'primary email will not match if changed on disabled CMS integration setting');
    $this->assertEquals($email1, $ufName);
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testDelete(int $version): void {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess('UFMatch', 'create', [
      'contact_id' => $this->individualCreate(),
      'uf_id' => '2',
      'uf_name' => 'blahdyblah@gmail.com',
      'domain_id' => 1,
    ]);
    $this->assertEquals(1, $this->callAPISuccess('UFMatch', 'getcount', [
      'id' => $result['id'],
    ]));
    $this->callAPISuccess('UFMatch', 'delete', ['id' => $result['id']]);
    $this->callAPISuccessGetCount('UFMatch', ['id' => $result['id']], 0);
  }

}
