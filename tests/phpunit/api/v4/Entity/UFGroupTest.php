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

namespace Civi\tests\phpunit\api\v4\Entity;

use api\v4\Api4TestBase;
use Civi\Api4\UFGroup;

/**
 * @group headless
 */
class UFGroupTest extends Api4TestBase {

  /**
   * Test group_type_label extra field on UFGroup Api4 get.
   */
  public function testGroupTypeLabel(): void {
    $profiles = [
      'contacts' => [
        'title' => 'Contacts Profile',
        'group_type' => 'Individual,Contact',
        'expected' => ['Individual', 'Contact'],
      ],
      'contribution' => [
        'title' => 'Contribution Profile',
        'group_type' => "Individual,Contact\x01ContributionType:1:2",
        'expected' => ['Individual', 'Contact', 'Contributions: Donation, Member Dues'],
      ],
      'participant_role' => [
        'title' => 'Participant Role Profile',
        'group_type' => "Individual,Contact\x01ParticipantRole:1:2",
        'expected' => ['Individual', 'Contact', 'Role: Attendee, Volunteer'],
      ],
      'participant_event_type' => [
        'title' => 'Participant Event Type Profile',
        'group_type' => "Individual,Contact\x01ParticipantEventType:1:2",
        'expected' => ['Individual', 'Contact', 'Event Type: Conference, Exhibition'],
      ],
      'multiple_subtypes' => [
        'title' => 'Multiple Subtypes Profile',
        'group_type' => "Individual,Contact\x01ParticipantRole:1,ContributionType:1",
        'expected' => ['Individual', 'Contact', 'Role: Attendee', 'Contributions: Donation'],
      ],
      'empty' => [
        'title' => 'Empty Group Type Profile',
        'group_type' => NULL,
        'expected' => [],
      ],
    ];

    foreach ($profiles as $key => $data) {
      $profile = $this->createTestRecord('UFGroup', [
        'title' => $data['title'],
        'group_type' => $data['group_type'],
      ]);

      $result = UFGroup::get(FALSE)
        ->addWhere('id', '=', $profile['id'])
        ->addSelect('group_type_label')
        ->execute()
        ->first();

      $this->assertEquals($data['expected'], $result['group_type_label']);
    }
  }

}
