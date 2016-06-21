<?php
namespace CiviDrupalMemberRoles;

use Civi\Test\EndToEndInterface;

/**
 * Class MemberSyncTest
 * @package CiviDrupalMemberRoles
 * @group e2e
 */
class MemberSyncTest extends \PHPUnit_Framework_TestCase implements EndToEndInterface {

  protected function setUp() {
    $this->assertTrue(module_exists('civicrm_member_roles'), 'civicrm_member_roles should be enabled');
    parent::setUp();
    $this->cleanup();
  }

  protected function tearDown() {
    parent::tearDown();
    $this->cleanup();
  }

  public function testCron() {
    $prefix = 'MemberSyncTest' . \CRM_Utils_String::createRandom(8, \CRM_Utils_String::ALPHANUMERIC);
    $demoContact = $this->getDemoContact();

    $role = $this->createRole("{$prefix}Role");

    $org = civicrm_api3('Contact', 'create', array(
      'sequential' => 1,
      'contact_type' => "Organization",
      'organization_name' => "{$prefix}Org",
    ));

    $memType = civicrm_api3('MembershipType', 'create', array(
      'sequential' => 1,
      'domain_id' => \CRM_Core_Config::domainID(),
      'member_of_contact_id' => $org['id'],
      'financial_type_id' => "Donation",
      'duration_unit' => "year",
      'duration_interval' => 1,
      'name' => "{$prefix}MemType",
    ));

    // Cron doesn't any do anything yet. We haven't configured memberships or rules.
    _civicrm_member_roles_sync(NULL, NULL, 'cron');
    $this->assertFalse($this->checkUserRole($GLOBALS['_CV']['DEMO_USER'], "{$prefix}Role"));

    // We'll setup the membership... but not the rule!
    civicrm_api3('Membership', 'create', array(
      'sequential' => 1,
      'membership_type_id' => $memType['id'],
      'contact_id' => $demoContact['id'],
    ));
    $this->assertFalse($this->checkUserRole($GLOBALS['_CV']['DEMO_USER'], "{$prefix}Role"));

    // Now we get the rule. All conditions are met!
    $this->createRule($role->rid, $memType['id']);
    _civicrm_member_roles_sync(NULL, NULL, 'cron');
    $this->assertTrue($this->checkUserRole($GLOBALS['_CV']['DEMO_USER'], "{$prefix}Role"));
  }

  /**
   * @param $roleName
   * @return \stdClass
   */
  protected function createRole($roleName) {
    $role = new \stdClass();
    $role->name = $roleName;
    user_role_save($role);
    return $role;
  }

  /**
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  protected function getDemoContact() {
    $this->assertNotEmpty($GLOBALS['_CV']['DEMO_USER']);
    $demoContact = civicrm_api3('Contact', 'getsingle', array(
      'id' => '@user:' . $GLOBALS['_CV']['DEMO_USER'],
    ));
    return $demoContact;
  }

  protected function checkUserRole($userName, $roleName) {
    $user = user_load_by_name($userName);
    return in_array($roleName, $user->roles);
  }

  /**
   * @param $roleId
   * @param $memTypeId
   * @throws \Exception
   */
  protected function createRule($roleId, $memTypeId) {
    $codes = array(
      0 => 'current',
      1 => 'expired',
      'current' => array(
        0 => '1',
        1 => '2',
      ),
      'expired' => array(
        0 => '3',
        1 => '4',
        2 => '5',
        3 => '6',
        4 => '7',
      ),
    );
    db_insert('civicrm_member_roles_rules')->fields(array(
      'rid' => (int) $roleId,
      'type_id' => (int) $memTypeId,
      'status_codes' => serialize($codes),
    ))->execute();
  }

  /**
   * Delete any records created with the "MemberSyncTest" prefix.
   * @throws \CiviCRM_API3_Exception
   */
  protected function cleanup() {
    \db_delete('role')->condition('name', 'MemberSyncTest%', 'LIKE')->execute();
    \db_delete('civicrm_member_roles_rules')->execute();
    \civicrm_api3('Membership', 'get', array(
      'contact_id' => '@user:' . $GLOBALS['_CV']['DEMO_USER'],
      'api.Membership.delete' => 1,
    ));
    \civicrm_api3('MembershipType', 'get', array(
      'name' => array('LIKE' => 'MemberSyncTest%'),
      'api.MembershipType.delete' => 1,
    ));
    \civicrm_api3('Contact', 'get', array(
      'organization_name' => 'MemberSyncTest%',
      'api.Contact.delete' => 1,
    ));
  }

}
