<?php
use CRM_Afform_ExtensionUtil as E;

return [
  'name' => 'Afform',
  // Afforms are stored in files, not sql tables
  'table' => NULL,
  'class' => NULL,
  'getInfo' => fn() => [
    'title' => E::ts('Afform'),
    'title_plural' => E::ts('Afforms'),
    'description' => E::ts('FormBuilder forms'),
    'log' => FALSE,
  ],
  'getPaths' => fn() => [
    'view' => '[server_route]',
    'edit' => 'civicrm/admin/afform#/edit/[name]',
  ],
  'getFields' => fn() => [
    'name' => [
      'title' => E::ts('Name'),
      'data_type' => 'String',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Afform file name'),
      'primary_key' => TRUE,
    ],
    'type' => [
      'title' => E::ts('Type'),
      'data_type' => 'String',
      'pseudoconstant' => ['option_group_name' => 'afform_type'],
      'default_value' => 'form',
    ],
    'requires' => [
      'title' => E::ts('Requires'),
      'data_type' => 'Array',
    ],
    'entity_type' => [
      'title' => E::ts('Block Entity'),
      'data_type' => 'String',
      'description' => E::ts('Block used for this entity type'),
    ],
    'join_entity' => [
      'title' => E::ts('Join Entity'),
      'data_type' => 'String',
      'description' => E::ts('Used for blocks that join a sub-entity (e.g. Emails for a Contact)'),
    ],
    'title' => [
      'title' => E::ts('Title'),
      'data_type' => 'String',
      'required' => TRUE,
    ],
    'description' => [
      'title' => E::ts('Description'),
      'data_type' => 'String',
    ],
    'placement' => [
      'title' => E::ts('Placement'),
      'pseudoconstant' => ['option_group_name' => 'afform_placement'],
      'data_type' => 'Array',
    ],
    'summary_contact_type' => [
      'title' => E::ts('Summary Contact Type'),
      'data_type' => 'Array',
      'pseudoconstant' => [
        'table' => 'civicrm_contact_type',
        'key_column' => 'name',
        'label_column' => 'label',
        'icon_column' => 'icon',
      ],
    ],
    'summary_weight' => [
      'title' => E::ts('Order'),
      'data_type' => 'Integer',
    ],
    'icon' => [
      'title' => E::ts('Icon'),
      'data_type' => 'String',
      'description' => E::ts('Icon shown in the contact summary tab'),
    ],
    'server_route' => [
      'title' => E::ts('Page Route'),
      'data_type' => 'String',
    ],
    'is_public' => [
      'title' => E::ts('Is Public'),
      'data_type' => 'Boolean',
      'default_value' => FALSE,
    ],
    'permission' => [
      'title' => E::ts('Permission'),
      'data_type' => 'Array',
      'default_value' => ['access CiviCRM'],
    ],
    'permission_operator' => [
      'title' => E::ts('Permission Operator'),
      'data_type' => 'String',
      'default_value' => 'AND',
      'pseudoconstant' => [
        'callback' => ['CRM_Core_SelectValues', 'andOr'],
      ],
    ],
    'redirect' => [
      'title' => E::ts('Post-Submit Page'),
      'data_type' => 'String',
    ],
    'submit_enabled' => [
      'title' => E::ts('Allow Submissions'),
      'data_type' => 'Boolean',
      'default_value' => TRUE,
    ],
    'submit_limit' => [
      'title' => E::ts('Maximum Submissions'),
      'data_type' => 'Integer',
    ],
    'create_submission' => [
      'title' => E::ts('Log Submissions'),
      'data_type' => 'Boolean',
    ],
    'manual_processing' => [
      'title' => E::ts('Manual Processing'),
      'data_type' => 'Boolean',
    ],
    'allow_verification_by_email' => [
      'title' => E::ts('Allow Verification by Email'),
      'data_type' => 'Boolean',
    ],
    'email_confirmation_template_id' => [
      'title' => E::ts('Email Confirmation Template'),
      'data_type' => 'Integer',
    ],
    'navigation' => [
      'title' => E::ts('Navigation Menu'),
      'data_type' => 'Array',
      'description' => E::ts('Insert into navigation menu {parent: string, label: string, weight: int}'),
    ],
    'layout' => [
      'title' => E::ts('Layout'),
      'data_type' => 'Array',
      'description' => E::ts('HTML form layout; format is controlled by layoutFormat param'),
    ],
    // Calculated readonly fields
    'modified_date' => [
      'title' => E::ts('Date Modified'),
      'data_type' => 'Timestamp',
      'readonly' => TRUE,
    ],
    'module_name' => [
      'title' => E::ts('Module Name'),
      'data_type' => 'String',
      'description' => E::ts('Name of generated Angular module (CamelCase)'),
      'readonly' => TRUE,
    ],
    'directive_name' => [
      'title' => E::ts('Directive Name'),
      'data_type' => 'String',
      'description' => E::ts('Html tag name to invoke this form (dash-case)'),
      'readonly' => TRUE,
    ],
    'submission_count' => [
      'title' => E::ts('Submission Count'),
      'data_type' => 'Integer',
      'input_type' => 'Number',
      'description' => E::ts('Number of submission records for this form'),
      'readonly' => TRUE,
    ],
    'submission_date' => [
      'title' => E::ts('Submission Date'),
      'data_type' => 'Timestamp',
      'input_type' => 'Date',
      'description' => E::ts('Date & time of last form submission'),
      'readonly' => TRUE,
    ],
    'submit_currently_open' => [
      'title' => E::ts('Submit Currently Open'),
      'data_type' => 'Boolean',
      'input_type' => 'Select',
      'description' => E::ts('Based on settings and current submission count, is the form open for submissions'),
      'readonly' => TRUE,
    ],
    'has_local' => [
      'title' => E::ts('Saved Locally'),
      'data_type' => 'Boolean',
      'description' => E::ts('Whether a local copy is saved on site'),
      'readonly' => TRUE,
    ],
    'has_base' => [
      'title' => E::ts('Packaged'),
      'data_type' => 'Boolean',
      'description' => E::ts('Is provided by an extension'),
      'readonly' => TRUE,
    ],
    'base_module' => [
      'title' => E::ts('Extension'),
      'data_type' => 'String',
      'description' => E::ts('Name of extension which provides this form'),
      'readonly' => TRUE,
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_Managed', 'getBaseModules'],
      ],
    ],
    'search_displays' => [
      'title' => E::ts('Search Displays'),
      'data_type' => 'Array',
      'readonly' => TRUE,
      'description' => E::ts('Embedded search displays, formatted like ["search-name.display-name"]'),
    ],
  ],
];
