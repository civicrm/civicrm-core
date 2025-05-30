<?php
return [
  [
    'module' => 'queue_tasks',
    'name' => 'batch_merge',
    'entity' => 'Queue',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'batch_merge',
        'type' => 'Sql',
        'runner' => 'task',
        'batch_limit' => 3,
        'retry_limit' => 1,
        'retry_interval' => 200,
        'error' => 'abort',
      ],
    ],
  ],
];
