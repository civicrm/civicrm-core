<?php
return [
  [
    'name' => 'CaseType_BunnyDance',
    'entity' => 'CaseType',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'BunnyDance',
        'title' => 'Bunny Dance Case',
        'description' => 'The mysterious case of the dancing bunny',
        'is_active' => TRUE,
        'is_reserved' => TRUE,
        'weight' => 1,
        'definition' => [
          'activityTypes' => [
            ['name' => 'Open Case', 'max_instances' => '1'],
            ['name' => 'Follow up'],
            ['name' => 'Change Case Type'],
            ['name' => 'Change Case Status'],
            ['name' => 'Change Case Start Date'],
            ['name' => 'Link Cases'],
            ['name' => 'Email'],
            ['name' => 'Meeting'],
            ['name' => 'Phone Call'],
            ['name' => 'Nibble'],
          ],
          'activitySets' => [
            [
              'name' => 'standard_timeline',
              'label' => 'Standard Timeline',
              'timeline' => 1,
              'activityTypes' => [
                ['name' => 'Open Case', 'status' => 'Completed'],
                ['name' => 'Phone Call', 'reference_offset' => '1', 'reference_select' => 'newest'],
                ['name' => 'Follow up', 'reference_offset' => '7', 'reference_select' => 'newest'],
              ],
            ],
          ],
          'timelineActivityTypes' => [
            ['name' => 'Open Case', 'status' => 'Completed'],
            ['name' => 'Phone Call', 'reference_offset' => '1', 'reference_select' => 'newest'],
            ['name' => 'Follow up', 'reference_offset' => '7', 'reference_select' => 'newest'],
          ],
          'caseRoles' => [
            ['name' => 'Case Coordinator', 'creator' => '1', 'manager' => '1'],
          ],
        ],
      ],
    ],
  ],
];
