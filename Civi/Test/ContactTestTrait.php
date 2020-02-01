<?php

namespace Civi\Test;

/**
 * Class ContactTestTrait
 *
 * @package Civi\Test
 *
 * This trait defines a number of helper functions for managing
 * test contacts. Generally, it depends on having access to the
 * API test functions ($this->callAPISuccess()) and to the
 * standard PHPUnit assertions ($this->assertEquals). It should
 * not impose any other requirements for the downstream consumer class.
 */
trait ContactTestTrait {

  /**
   * Emulate a logged in user since certain functions use that.
   * value to store a record in the DB (like activity)
   * CRM-8180
   *
   * @return int
   *   Contact ID of the created user.
   */
  public function createLoggedInUser() {
    $params = [
      'first_name' => 'Logged In',
      'last_name' => 'User ' . rand(),
      'contact_type' => 'Individual',
      'domain_id' => \CRM_Core_Config::domainID(),
    ];
    $contactID = $this->individualCreate($params);
    $this->callAPISuccess('UFMatch', 'get', ['uf_id' => 6, 'api.UFMatch.delete' => []]);
    $this->callAPISuccess('UFMatch', 'create', [
      'contact_id' => $contactID,
      'uf_name' => 'superman',
      'uf_id' => 6,
    ]);

    $session = \CRM_Core_Session::singleton();
    $session->set('userID', $contactID);
    return $contactID;
  }

  /**
   * Generic function to create Organisation, to be used in test cases
   *
   * @param array $params
   *   parameters for civicrm_contact_add api function call
   * @param int $seq
   *   sequence number if creating multiple organizations
   *
   * @return int
   *   id of Organisation created
   */
  public function organizationCreate($params = [], $seq = 0) {
    if (!$params) {
      $params = [];
    }
    $params = array_merge($this->sampleContact('Organization', $seq), $params);
    return $this->_contactCreate($params);
  }

  /**
   * Generic function to create Individual, to be used in test cases
   *
   * @param array $params
   *   parameters for civicrm_contact_add api function call
   * @param int $seq
   *   sequence number if creating multiple individuals
   * @param bool $random
   *
   * @return int
   *   id of Individual created
   *
   * @throws \CRM_Core_Exception
   */
  public function individualCreate($params = [], $seq = 0, $random = FALSE) {
    $params = array_merge($this->sampleContact('Individual', $seq, $random), $params);
    return $this->_contactCreate($params);
  }

  /**
   * Generic function to create Household, to be used in test cases
   *
   * @param array $params
   *   parameters for civicrm_contact_add api function call
   * @param int $seq
   *   sequence number if creating multiple households
   *
   * @return int
   *   id of Household created
   *
   * @throws \CRM_Core_Exception
   */
  public function householdCreate($params = [], $seq = 0) {
    $params = array_merge($this->sampleContact('Household', $seq), $params);
    return $this->_contactCreate($params);
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
  public function sampleContact($contact_type, $seq = 0, $random = FALSE) {
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
        $params[$key] .= substr(sha1(rand()), 0, 5);
      }
    }
    if ($contact_type == 'Individual') {
      $params['email'] = strtolower(
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
   *
   * @return int
   *   id of contact created
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  private function _contactCreate($params) {
    $result = civicrm_api3('contact', 'create', $params);
    if (!empty($result['is_error']) || empty($result['id'])) {
      throw new \CRM_Core_Exception('Could not create test contact, with message: ' . \CRM_Utils_Array::value('error_message', $result) . "\nBacktrace:" . \CRM_Utils_Array::value('trace', $result));
    }
    return (int) $result['id'];
  }

  /**
   * Delete contact, ensuring it is not the domain contact
   *
   * @param int $contactID
   *   Contact ID to delete
   */
  public function contactDelete($contactID) {
    $domain = new \CRM_Core_BAO_Domain();
    $domain->contact_id = $contactID;
    if (!$domain->find(TRUE)) {
      $this->callAPISuccess('contact', 'delete', [
        'id' => $contactID,
        'skip_undelete' => 1,
      ]);
    }
  }

  /**
   * Add a Group.
   *
   * @param array $params
   *
   * @return int
   *   groupId of created group
   */
  public function groupCreate($params = []) {
    $params = array_merge([
      'name' => 'Test Group 1',
      'domain_id' => 1,
      'title' => 'New Test Group Created',
      'description' => 'New Test Group Created',
      'is_active' => 1,
      'visibility' => 'Public Pages',
      'group_type' => [
        '1' => 1,
        '2' => 1,
      ],
    ], $params);

    $result = $this->callAPISuccess('Group', 'create', $params);
    return $result['id'];
  }

  /**
   * Delete a Group.
   *
   * @param int $gid
   */
  public function groupDelete($gid) {
    $params = [
      'id' => $gid,
    ];

    $this->callAPISuccess('Group', 'delete', $params);
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
  public function groupContactCreate($groupID, $totalCount = 10, $random = FALSE) {
    $params = ['group_id' => $groupID];
    for ($i = 1; $i <= $totalCount; $i++) {
      $contactID = $this->individualCreate([], 0, $random);
      if ($i == 1) {
        $params += ['contact_id' => $contactID];
      }
      else {
        $params += ["contact_id.$i" => $contactID];
      }
    }
    $result = $this->callAPISuccess('GroupContact', 'create', $params);

    return $result;
  }

}
