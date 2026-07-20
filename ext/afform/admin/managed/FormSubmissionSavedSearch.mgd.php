<?php

use CRM_AfformAdmin_ExtensionUtil as E;

// This file declares a SavedSearch and SearchDisplay for viewing form submissions.
return [
  [
    'name' => 'AfAdmin_Submission_List',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'AfAdmin_Submission_List',
        'label' => E::ts('Form Submissions'),
        'api_entity' => 'AfformSubmissionData',
        'api_params' => [
          'version' => 4,
          'select' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
];
