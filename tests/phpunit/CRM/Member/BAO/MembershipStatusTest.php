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
 * Class CRM_Member_BAO_MembershipStatusTest
 * @group headless
 */
class CRM_Member_BAO_MembershipStatusTest extends CiviUnitTestCase {

  protected function tearDown(): void {
    foreach ($this->ids as $entity => $ids) {
      foreach ($ids as $id) {
        $this->callAPISuccess($entity, 'Delete', ['id' => $id]);
      }
    }
    parent::tearDown();
  }

  /**
   * Check function add()
   */
  public function testAdd() {
    $params = [
      'name' => 'added',
      'is_active' => 1,
    ];

    $this->ids['MembershipStatus'][0] = $this->callAPISuccess('MembershipStatus', 'create', $params)['id'];

    $result = $this->assertDBNotNull('CRM_Member_BAO_MembershipStatus', $this->ids['MembershipStatus'][0],
      'name', 'id',
      'Database check on updated membership status record.'
    );
    $this->assertEquals($result, 'added', 'Verify membership status is_active.');
  }

  public function testRetrieve() {

    $params = ['name' => 'testStatus', 'is_active' => 1];

    $this->ids['MembershipStatus'][0] = $this->callAPISuccess('MembershipStatus', 'create', $params)['id'];
    $defaults = [];
    $result = CRM_Member_BAO_MembershipStatus::retrieve($params, $defaults);
    $this->assertEquals($result->name, 'testStatus', 'Verify membership status name.');
  }

  public function testPseudoConstantflush() {
    $params = [
      'name' => 'testStatus',
      'is_active' => 1,
    ];
    $this->ids['MembershipStatus'][0] = $this->callAPISuccess('MembershipStatus', 'create', $params)['id'];
    $defaults = [];
    $result = CRM_Member_BAO_MembershipStatus::retrieve($params, $defaults);
    $this->assertEquals($result->name, 'testStatus', 'Verify membership status name.');
    $updateParams = [
      'id' => $this->ids['MembershipStatus'][0],
      'name' => 'testStatus',
      'label' => 'Changed Status',
      'is_active' => 1,
    ];
    $this->callAPISuccess('MembershipStatus', 'create', $updateParams)['id'];
    $result = CRM_Member_PseudoConstant::membershipStatus($this->ids['MembershipStatus'][0], NULL, 'label', FALSE, FALSE);
    $this->assertEquals($result, 'Changed Status', 'Verify updated membership status label From PseudoConstant.');
  }

  public function testSetIsActive() {

    $params = [
      'name' => 'added',
      'is_active' => 1,
    ];

    $this->ids['MembershipStatus'][0] = $this->callAPISuccess('MembershipStatus', 'create', $params)['id'];
    $result = CRM_Member_BAO_MembershipStatus::setIsActive($this->ids['MembershipStatus'][0], 0);
    $this->assertEquals($result, TRUE, 'Verify membership status record updated.');

    $isActive = $this->assertDBNotNull('CRM_Member_BAO_MembershipStatus', $this->ids['MembershipStatus'][0],
      'is_active', 'id',
      'Database check on updated membership status record.'
    );
    $this->assertEquals($isActive, 0, 'Verify membership status is_active.');
  }

  public function testGetMembershipStatus() {
    $params = [
      'name' => 'added',
      'is_active' => 1,
    ];

    $this->ids['MembershipStatus'][0] = $this->callAPISuccess('MembershipStatus', 'create', $params)['id'];
    $result = CRM_Member_BAO_MembershipStatus::getMembershipStatus($this->ids['MembershipStatus'][0]);
    $this->assertEquals($result['name'], 'added', 'Verify membership status name.');
  }

  public function testDel() {
    $params = ['name' => 'testStatus', 'is_active' => 1];

    $membershipID = $this->callAPISuccess('MembershipStatus', 'create', $params)['id'];
    CRM_Member_BAO_MembershipStatus::del($membershipID);
    $defaults = [];
    $result = CRM_Member_BAO_MembershipStatus::retrieve($params, $defaults);
    $this->assertEquals($result === NULL, TRUE, 'Verify membership status record deletion.');
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testExpiredDisabled() {
    $this->callAPISuccess('MembershipStatus', 'get', [
      'name' => 'Expired',
      'api.MembershipStatus.create' => ['label' => 'Expiiiired'],
    ]);

    // Calling it 'Expiiiired' is OK.
    $this->callAPISuccess('job', 'process_membership', []);

    $this->callAPISuccess('MembershipStatus', 'get', [
      'name' => 'Expired',
      'api.MembershipStatus.create' => ['is_active' => 0],
    ]);

    // Disabling 'Expired' is OK.
    $this->callAPISuccess('job', 'process_membership', []);

    $this->callAPISuccess('MembershipStatus', 'get', [
      'name' => 'Expired',
      'api.MembershipStatus.delete' => [],
    ]);

    // Deleting 'Expired' is OK.
    $this->callAPISuccess('job', 'process_membership', []);

    // Put things back like normal
    $this->callAPISuccess('MembershipStatus', 'create', [
      'name' => 'Expired',
      'label' => 'Expired',
      'start_event' => 'end_date',
      'start_event_adjust_unit' => 'month',
      'start_event_adjust_interval' => 1,
      'is_current_member' => 0,
      'is_admin' => 0,
      'weight' => 4,
      'is_default' => 0,
      'is_active' => 1,
      'is_reserved' => 0,
    ]);
    CRM_Core_DAO::executeQuery("UPDATE civicrm_membership_status SET id=4 WHERE name='Expired'");

  }

  public function testGetMembershipStatusByDate() {
    $params = [
      'name' => 'Current',
      'is_active' => 1,
      'start_event' => 'start_date',
      'end_event' => 'end_date',
    ];

    $membershipStatus = CRM_Member_BAO_MembershipStatus::add($params);
    $toDate = date('Ymd');

    $result = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($toDate, $toDate, $toDate, 'now', TRUE, NULL, $params);
    $this->assertEquals($result['name'], 'Current', 'Verify membership status record.');

    $this->callAPISuccess('MembershipStatus', 'Delete', ['id' => $membershipStatus->id]);
  }

  /**
   * Test getMembershipStatusByDate more
   *
   * @dataProvider statusByDateProvider
   *
   * @param array $input
   * @param array $expected
   */
  public function testGetMembershipStatusByDateMore($input, $expected) {
    // Sanity check we have something close to the stock install.
    $this->assertEquals(7, $this->callAPISuccess('MembershipStatus', 'getcount'));

    $this->assertEquals(
      $expected,
      CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate(
        $input['startDate'],
        $input['endDate'],
        $input['joinDate'],
        $input['statusDate']
      )
    );
  }

  /**
   * Data provider for testGetMembershipStatusByDateMore
   * @return array
   */
  public function statusByDateProvider():array {
    return [
      [
        [
          'startDate' => '2020-02-01',
          'endDate' => '2020-04-30',
          'joinDate' => '2020-01-01',
          'statusDate' => '2020-03-01',
        ],
        [
          'id' => '1',
          'name' => 'New',
        ],
      ],
      [
        [
          'startDate' => '2020-02-01',
          'endDate' => '2020-04-30',
          'joinDate' => '2020-01-01',
          'statusDate' => '2020-04-29',
        ],
        [
          'id' => '2',
          'name' => 'Current',
        ],
      ],
      [
        [
          'startDate' => '2020-02-01',
          'endDate' => '2020-04-30',
          'joinDate' => '2020-01-01',
          'statusDate' => '2020-05-01',
        ],
        [
          'id' => '3',
          'name' => 'Grace',
        ],
      ],
      [
        [
          'startDate' => '2020-02-01',
          'endDate' => '2020-04-30',
          'joinDate' => '2020-01-01',
          'statusDate' => '2020-06-01',
        ],
        [
          'id' => '4',
          'name' => 'Expired',
        ],
      ],
      [
        [
          'startDate' => '2020-02-01',
          'endDate' => '2020-04-30',
          'joinDate' => '2019-01-01',
          'statusDate' => '2020-03-01',
        ],
        [
          'id' => '2',
          'name' => 'Current',
        ],
      ],
      [
        [
          'startDate' => '2020-02-01',
          'endDate' => '2020-04-30',
          'joinDate' => '2019-01-01',
          'statusDate' => '2020-04-29',
        ],
        [
          'id' => '2',
          'name' => 'Current',
        ],
      ],
      [
        [
          'startDate' => '2020-02-01',
          'endDate' => '2020-04-30',
          'joinDate' => '2019-01-01',
          'statusDate' => '2020-05-01',
        ],
        [
          'id' => '3',
          'name' => 'Grace',
        ],
      ],
      [
        [
          'startDate' => '2020-02-01',
          'endDate' => '2020-04-30',
          'joinDate' => '2019-01-01',
          'statusDate' => '2020-06-01',
        ],
        [
          'id' => '4',
          'name' => 'Expired',
        ],
      ],
      [
        [
          'startDate' => '2020-02-01',
          'endDate' => '2021-01-31',
          'joinDate' => '2020-01-01',
          'statusDate' => '2020-03-01',
        ],
        [
          'id' => '1',
          'name' => 'New',
        ],
      ],
      [
        [
          'startDate' => '2020-02-01',
          'endDate' => '2021-01-31',
          'joinDate' => '2020-01-01',
          'statusDate' => '2020-04-29',
        ],
        [
          'id' => '2',
          'name' => 'Current',
        ],
      ],
      [
        [
          'startDate' => '2020-02-01',
          'endDate' => '2021-01-31',
          'joinDate' => '2020-01-01',
          'statusDate' => '2020-12-30',
        ],
        [
          'id' => '2',
          'name' => 'Current',
        ],
      ],
      [
        [
          'startDate' => '2020-02-01',
          'endDate' => '2021-01-31',
          'joinDate' => '2020-01-01',
          'statusDate' => '2021-01-01',
        ],
        [
          'id' => '2',
          'name' => 'Current',
        ],
      ],
      [
        [
          'startDate' => '2020-02-01',
          'endDate' => '2021-01-31',
          'joinDate' => '2020-01-01',
          'statusDate' => '2021-01-31',
        ],
        [
          'id' => '2',
          'name' => 'Current',
        ],
      ],
      [
        [
          'startDate' => '2020-02-01',
          'endDate' => '2021-01-31',
          'joinDate' => '2020-01-01',
          'statusDate' => '2021-02-21',
        ],
        [
          'id' => '3',
          'name' => 'Grace',
        ],
      ],
      [
        [
          'startDate' => '2020-02-01',
          'endDate' => '2021-01-31',
          'joinDate' => '2020-01-01',
          'statusDate' => '2021-03-21',
        ],
        [
          'id' => '4',
          'name' => 'Expired',
        ],
      ],
      [
        [
          'startDate' => '2020-02-01',
          'endDate' => '',
          'joinDate' => '2020-01-01',
          'statusDate' => '2020-03-01',
        ],
        [
          'id' => '1',
          'name' => 'New',
        ],
      ],
      [
        [
          'startDate' => '2020-02-01',
          'endDate' => '',
          'joinDate' => '2020-01-01',
          'statusDate' => '2020-06-01',
        ],
        [
          'id' => '2',
          'name' => 'Current',
        ],
      ],
    ];
  }

  public function testgetMembershipStatusCurrent() {
    $params = [
      'name' => 'Current',
      'is_active' => 1,
      'is_current_member' => 1,
    ];

    $membershipStatus = CRM_Member_BAO_MembershipStatus::add($params);
    $result = CRM_Member_BAO_MembershipStatus::getMembershipStatusCurrent();

    $this->assertEquals(empty($result), FALSE, 'Verify membership status records is_current_member.');

    $this->callAPISuccess('MembershipStatus', 'Delete', ['id' => $membershipStatus->id]);
  }

}
