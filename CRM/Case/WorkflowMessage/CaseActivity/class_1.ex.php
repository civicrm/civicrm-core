<?php
return [
  'title' => ts('Case Activity (Class-style example)'),
  'tags' => ['phpunit', 'preview'],
  'data' => function (\Civi\WorkflowMessage\Examples $examples) {
    return $examples->extend('generic.alex.data', [
      'modelProps' => [
        'contact' => [
          'role' => 'myrole',
        ],
        'isCaseActivity' => 1,
        'clientId' => 101,
        'activityTypeName' => 'Follow up',
        'activityFields' => [
          [
            'label' => 'Case ID',
            'type' => 'String',
            'value' => '1234',
          ],
        ],
        'activitySubject' => 'Test 123',
        'activityCustomGroups' => [],
        'idHash' => 'abcdefg',
      ],
    ]);
  },
  'asserts' => [
    'default' => [
      ['for' => 'subject', 'regex' => '/\[case #abcdefg\] Test 123/'],
      ['for' => 'text', 'regex' => '/Your Case Role\(s\) : myrole/'],
    ],
  ],
];
