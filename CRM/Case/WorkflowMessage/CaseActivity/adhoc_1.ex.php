<?php
return [
  'title' => ts('Case Activity (Adhoc-style example)'),
  'tags' => [],
  'data' => function (\Civi\WorkflowMessage\Examples $examples) {
    $contact = $examples->extend('generic.alex.data.modelProps.contact', [
      'role' => 'myrole',
    ]);
    return [
      'tokenContext' => [
        'contact' => $contact,
      ],
      'tplParams' => [
        'contact' => $contact,
        'isCaseActivity' => 1,
        'client_id' => 101,
        'activityTypeName' => 'Follow up',
        'activitySubject' => 'Test 123',
        'idHash' => 'abcdefg',
        'activity' => [
          'fields' => [
            [
              'label' => 'Case ID',
              'type' => 'String',
              'value' => '1234',
            ],
          ],
        ],
      ],
    ];
  },
];
