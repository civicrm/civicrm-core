<?php

use CRM_Afform_ExtensionUtil as E;

// Adds option group for Afform.status_id
return [
  [
    'name' => 'AfformSubmissionStatus',
    'entity' => 'OptionGroup',
    'update' => 'always',
    'cleanup' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'afform_submission_status',
        'title' => E::ts('Afform Submission Status'),
        'description' => NULL,
        'data_type' => NULL,
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'is_locked' => FALSE,
        'option_value_fields' => [
          'name',
          'label',
          'icon',
          'description',
        ],
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'AfformSubmissionStatus:Processed',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_submission_status',
        'name' => 'Processed',
        'value' => 1,
        'label' => E::ts('Processed'),
        'description' => NULL,
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'icon' => 'fa-check-square-o',
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
  [
    'name' => 'AfformSubmissionStatus:Pending',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_submission_status',
        'name' => 'Pending',
        'value' => 2,
        'label' => E::ts('Pending'),
        'description' => NULL,
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'icon' => 'fa-circle-pause',
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
  [
    'name' => 'AfformSubmissionStatus:Draft',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_submission_status',
        'name' => 'Draft',
        'value' => 3,
        'label' => E::ts('Draft'),
        'description' => NULL,
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'icon' => 'fa-pen-to-square',
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
  [
    'name' => 'AfformSubmissionStatus:Rejected',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_submission_status',
        'name' => 'Rejected',
        'value' => 4,
        'label' => E::ts('Rejected'),
        'description' => NULL,
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'icon' => 'fa-rectangle-xmark',
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
];
