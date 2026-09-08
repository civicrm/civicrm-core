<?php

namespace Civi\Test;

use Civi\Api4\Contact;
use Civi\Api4\Group;
use Civi\Api4\GroupContact;
use Civi\Api4\UFMatch;

/**
 * Class ContactTestTrait
 *
 * @package Civi\Test
 *
 * This trait defines a number of helper functions for managing
 * test contacts. Historicallly, it depends on having access to the
 * API test functions ($this->callAPISuccess()) but that is now the exception.
 * It requires the standard PHPUnit assertions ($this->assertEquals). It should
 * not impose any other requirements for the downstream consumer class.
 */
trait ContactTestTrait {

  use EntityTrait;

  /**
   * API version to use for any api calls.
   *
   * @var int
   */
  public $apiversion = 4;

  private array $apiV4Fields = [];

  /**
   * Emulate a logged in user since certain functions use that.
   * value to store a record in the DB (like activity)
   *
   * @see https://issues.civicrm.org/jira/browse/CRM-8180
   *
   * @return int
   *   Contact ID of the created user.
   */
  public function createLoggedInUser(array $params = []): int {
    $params += [
      'first_name' => 'Logged In',
      'last_name' => 'User ' . mt_rand(),
      'contact_type' => 'Individual',
    ];
    $contactID = $this->individualCreate($params, 'logged_in');
    try {
      $existingMatchIDs = (array) UFMatch::get(FALSE)
        ->addWhere('uf_id', '=', 6)
        ->execute()
        ->column('id');
      if ($existingMatchIDs) {
        UFMatch::delete(FALSE)->addWhere('id', 'IN', $existingMatchIDs)->execute();
      }
      UFMatch::create(FALSE)->setValues([
        'contact_id' => $contactID,
        'uf_name' => 'superman',
        'uf_id' => 6,
      ])->execute();
    }
    catch (\CRM_Core_Exception $e) {
      $this->fail($e->getMessage());
    }

    $session = \CRM_Core_Session::singleton();
    $session->set('userID', $contactID);
    return $contactID;
  }

  /**
   * Generic function to create Organisation, to be used in test cases
   *
   * @param array $params
   *   parameters for civicrm_contact_add api function call
   * @param int|string $identifier
   *   If the identifier is numeric (discouraged) it will affect which contact is loaded.
   *   Numeric identifiers and values for random other than FALSE are generally
   *   discouraged in favour if specifying data in params where variety is needed.
   *
   * @return int
   *   id of Organisation created
   */
  public function organizationCreate(array $params = [], $identifier = 'organization_0'): int {
    $seq = is_numeric($identifier) ? $identifier : 0;
    $params = array_merge($this->sampleContact('Organization', $seq), $params);
    return $this->_contactCreate($params, $identifier);
  }

  /**
   * Generic function to create Individual, to be used in test cases
   *
   * @param array $params
   *   parameters for civicrm_contact_add api function call
   * @param int|string $identifier
   *   If the identifier is numeric (discouraged) it will affect which contact is loaded.
   *   Numeric identifiers and values for random other than FALSE are generally
   *   discouraged in favour if specifying data in params where variety is needed.
   * @param bool $random
   *   Random is deprecated.
   *
   * @return int
   *   id of Individual created
   */
  public function individualCreate(array $params = [], $identifier = 'individual_0', bool $random = FALSE): int {
    $seq = is_numeric($identifier) ? $identifier : 0;
    $params = array_merge($this->sampleContact('Individual', $seq, $random), $params);
    $this->_contactCreate($params, $identifier);
    return $this->ids['Contact'][$identifier];
  }

  /**
   * Generic function to create Household, to be used in test cases
   *
   * @param array $params
   *   parameters for civicrm_contact_add api function call
   * @param int|string $identifier
   *   If the identifier is numeric (discouraged) it will affect which contact is loaded.
   *   Numeric identifiers and values for random other than FALSE are generally
   *   discouraged in favour if specifying data in params where variety is needed.
   *
   * @return int
   *   id of Household created
   */
  public function householdCreate(array $params = [], $identifier = 'household_0'): int {
    $seq = is_numeric($identifier) ? $identifier : 0;
    $params = array_merge($this->sampleContact('Household', $seq), $params);
    return $this->_contactCreate($params, $identifier);
  }

  /**
   * Helper function for getting sample contact properties.
   *
   * @param string $contact_type
   *   enum contact type: Individual, Organization
   * @param int $seq
   *   sequence number for the values of this type
   * @param bool $random
   *
   * @return array
   *   properties of sample contact (ie. $params for API call)
   */
  private function sampleContact(string $contact_type, int $seq = 0, bool $random = FALSE): array {
    $samples = [
      'Individual' => [
        // The number of values in each list need to be coprime numbers to not have duplicates
        'first_name' => ['Anthony', 'Joe', 'Terrence', 'Lucie', 'Albert', 'Bill', 'Kim'],
        'middle_name' => ['J.', 'M.', 'P', 'L.', 'K.', 'A.', 'B.', 'C.', 'D', 'E.', 'Z.'],
        'last_name' => ['Anderson', 'Miller', 'Smith', 'Collins', 'Peterson'],
      ],
      'Organization' => [
        'organization_name' => [
          'Unit Test Organization',
          'Acme',
          'Roberts and Sons',
          'Cryo Space Labs',
          'Sharper Pens',
        ],
      ],
      'Household' => [
        'household_name' => ['Unit Test household'],
      ],
    ];
    $params = ['contact_type' => $contact_type];
    foreach ($samples[$contact_type] as $key => $values) {
      $params[$key] = $values[$seq % count($values)];
      if ($random) {
        $params[$key] .= bin2hex(random_bytes(3));
      }
    }
    if ($contact_type === 'Individual') {
      $params['email_primary.email'] = strtolower(
        $params['first_name'] . '_' . $params['last_name'] . '@civicrm.org'
      );
      $params['prefix_id'] = 3;
      $params['suffix_id'] = 3;
    }
    return $params;
  }

  /**
   * Private helper function for calling civicrm_contact_add.
   *
   * @param array $params
   *   For civicrm_contact_add api function call.
   * @param string $identifier
   *
   * @return int
   *   id of contact created
   */
  private function _contactCreate(array $params, string $identifier = 'Contact'): int {
    $version = $this->_apiversion;
    $defaultVersion = 4;
    // Assume api v4 is the default unless there are no incoming parameters that we
    // can easily ascertain as api v4. (Currently not checking more than email & contact
    // fields but they can always pass in version to use phone etc.
    if (!isset($this->apiV4Fields[$params['contact_type']])) {
      try {
        $this->apiV4Fields[$params['contact_type']] = (array) Contact::getFields(FALSE)
          ->addValue('contact_type', $params['contact_type'])
          ->setAction('create')
          ->execute()
          ->indexBy('name');
        $this->apiV4Fields[$params['contact_type']]['version'] = [];
        $this->apiV4Fields[$params['contact_type']]['email_primary.email'] = [];
      }
      catch (\CRM_Core_Exception $e) {
        $this->fail($e->getMessage());
      }
    }
    $nonV4Fields = array_diff_key($params, $this->apiV4Fields[$params['contact_type']]);
    foreach ($nonV4Fields as $key => $value) {
      if (str_contains($key, '_primary.') || str_contains($key, '_billing.')) {
        unset($nonV4Fields[$key]);
      }
      // PseudoConstant syntax (eg 'gender_id:name') is valid apiv4 for any
      // field that has options, so check against the base field name too.
      [$fieldName] = explode(':', $key, 2);
      if ($fieldName !== $key && isset($this->apiV4Fields[$params['contact_type']][$fieldName])) {
        unset($nonV4Fields[$key]);
      }
    }
    if (!empty($nonV4Fields)) {
      // Let's fall back to the earlier assumption of apiv3
      $defaultVersion = 3;
      if (isset($params['email_primary.email']) && !isset($params['email'])) {
        $params['email'] = $params['email_primary.email'];
      }
    }

    $this->_apiversion = $params['version'] ?? $defaultVersion;
    $result = $this->callAPISuccess('Contact', 'create', $params);
    $this->_apiversion = $version;
    $this->ids['Contact'][$identifier] = (int) $result['id'];
    return (int) $result['id'];
  }

  /**
   * Delete contact, ensuring it is not the domain contact
   *
   * @param int $contactID
   *   Contact ID to delete
   */
  public function contactDelete($contactID): void {
    $domain = new \CRM_Core_BAO_Domain();
    $domain->contact_id = $contactID;
    if (!$domain->find(TRUE)) {
      try {
        Contact::delete(FALSE)
          ->addWhere('id', '=', $contactID)
          ->setUseTrash(FALSE)
          ->execute();
      }
      catch (\CRM_Core_Exception $e) {
        $this->fail($e->getMessage());
      }
    }
  }

  /**
   * Add a Group.
   *
   * @param array $params
   * @param string $identifier
   *
   * @return int
   *   groupId of created group
   */
  public function groupCreate(array $params = [], string $identifier = 'group'): int {
    $params = array_merge([
      'name' => 'Test Group 1',
      'domain_id' => 1,
      'title' => 'New Test Group Created',
      'frontend_title' => 'Public group name',
      'description' => 'New Test Group Created',
      'is_active' => 1,
      'visibility' => 'Public Pages',
      'group_type' => [
        '1' => 1,
        '2' => 1,
      ],
    ], $params);

    $result = $this->createTestEntity('Group', $params, $identifier);
    return $result['id'];
  }

  /**
   * Delete a Group.
   *
   * @param int $gid
   */
  public function groupDelete($gid) {
    try {
      Group::delete(FALSE)->addWhere('id', '=', $gid)->execute();
    }
    catch (\CRM_Core_Exception $e) {
      $this->fail($e->getMessage());
    }
  }

  /**
   * Function to add a Group.
   *
   * @params array to add group
   *
   * @param int $groupID
   * @param int $totalCount
   * @param bool $random
   *
   * @return int
   *   groupId of created group
   */
  public function groupContactCreate($groupID, $totalCount = 10, $random = FALSE): array {
    $records = [];
    for ($i = 1; $i <= $totalCount; $i++) {
      $contactID = $this->individualCreate([], 0, $random);
      $records[] = ['group_id' => $groupID, 'contact_id' => $contactID];
    }
    try {
      GroupContact::save(FALSE)->setRecords($records)->execute();
    }
    catch (\CRM_Core_Exception $e) {
      $this->fail($e->getMessage());
    }
    // These are all fresh contacts being added for the first time, so every
    // record is a genuine addition - matches the v3 GroupContact.create
    // 'added' count that some callers still check.
    return ['added' => $totalCount];
  }

}
