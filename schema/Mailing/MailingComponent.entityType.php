<?php

return [
  'name' => 'MailingComponent',
  'table' => 'civicrm_mailing_component',
  'class' => 'CRM_Mailing_DAO_MailingComponent',
  'getInfo' => fn() => [
    'title' => ts('Mailing Component'),
    'title_plural' => ts('Mailing Components'),
    'description' => ts('Stores information about the mailing components (header/footer).'),
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/admin/component/edit?action=add&reset=1',
    'update' => 'civicrm/admin/component/edit?action=update&id=[id]&reset=1',
    'browse' => 'civicrm/admin/component?action=browse&id=[id]&reset=1',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Mailing Component ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('Component Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('The name of this component'),
    ],
    'component_type' => [
      'title' => ts('Mailing Component Type'),
      'sql_type' => 'varchar(12)',
      'input_type' => 'Select',
      'description' => ts('Type of Component.'),
      'pseudoconstant' => [
        'callback' => ['CRM_Core_SelectValues', 'mailingComponents'],
      ],
    ],
    'subject' => [
      'title' => ts('Subject'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'input_attrs' => [
        'label' => ts('Subject'),
      ],
    ],
    'body_html' => [
      'title' => ts('Mailing Component Body HTML'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Body of the component in html format.'),
      'input_attrs' => [
        'rows' => 8,
        'cols' => 80,
      ],
    ],
    'body_text' => [
      'title' => ts('Body Text'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Body of the component in text format.'),
      'input_attrs' => [
        'rows' => 8,
        'cols' => 80,
        'label' => ts('Body in Text Format'),
      ],
    ],
    'is_default' => [
      'title' => ts('Mailing Component is Default?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this the default component for this component_type?'),
      'default' => FALSE,
      'input_attrs' => [
        'label' => ts('Default'),
      ],
    ],
    'is_active' => [
      'title' => ts('Mailing Component Is Active?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this property active?'),
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
  ],
];
