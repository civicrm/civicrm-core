<?php
use CRM_riverlea_ExtensionUtil as E;

return [
  'name' => 'RiverleaStream',
  'table' => 'civicrm_riverlea_stream',
  'class' => 'CRM_riverlea_DAO_RiverleaStream',
  'getInfo' => fn() => [
    'title' => E::ts('RiverleaStream'),
    'title_plural' => E::ts('RiverleaStreams'),
    'description' => E::ts('Streams are configurable themes in the Riverlea Theme Framework'),
    'log' => TRUE,
  ],
  'getIndices' => fn() => [
    'index_stream_name' => [
      'fields' => [
        'name' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '6.1',
    ],
  ],
  'getPaths' => fn() => [
    // 'add' => 'civicrm/admin/riverlea/stream/create',
    // 'update' => 'civicrm/admin/riverlea/stream/update#?RiverleaStream=[id]',
    // 'delete' => 'civicrm/contact/view/delete?reset=1&delete=1&cid=[id]',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique RiverleaStream ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('Machine-Name'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Machine-name for this stream.'),
    ],
    'label' => [
      'title' => ts('Label'),
      'sql_type' => 'varchar(512)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('User-facing name for this stream'),
    ],
    'description' => [
      'title' => E::ts('Description'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('Description of this stream'),
      'default' => NULL,
    ],
    'is_reserved' => [
      'title' => E::ts('Is Reserved?'),
      'description' => E::ts('Reserved streams are not editable through the UI'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'default' => FALSE,
    ],
    'extension' => [
      'title' => ts('Extension'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'description' => ts('Extension that provides this stream.'),
      'default' => NULL,
      // @todo why not work?
      // 'entity_reference' => [
      //   'entity' => 'Extension',
      //   'key' => 'file',
      //   'on_delete' => 'CASCADE',
      // ],
    ],
    'file_prefix' => [
      'title' => ts('Extension File Prefix'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('File prefix to stream files within extension'),
      'default' => NULL,
    ],
    'css_file' => [
      'title' => ts('CSS File'),
      'description' => ts('A file containing stream css - path should be relative to the extension and file_prefix'),
      'sql_type' => 'varchar(512)',
      'input_type' => 'Text',
      'default' => NULL,
    ],
    'css_file_dark' => [
      'title' => ts('Dark-mode CSS File'),
      'description' => ts('A file containing stream css for darkmode - path should be relative to the extension and file_prefix'),
      'sql_type' => 'varchar(512)',
      'input_type' => 'Text',
      'default' => NULL,
    ],
    'vars' => [
      'title' => E::ts('Variable Settings'),
      'sql_type' => 'text',
      'description' => E::ts('Variable declarations for this stream'),
      'default' => NULL,
      'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
    ],
    'vars_dark' => [
      'title' => E::ts('Dark-mode Variable Settings'),
      'sql_type' => 'text',
      'description' => E::ts('Variable declaration overrides for the dark mode of this stream'),
      'default' => NULL,
      'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
    ],
    'custom_css' => [
      'title' => E::ts('Custom CSS'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('Custom css for this stream'),
      'default' => NULL,
    ],
    'custom_css_dark' => [
      'title' => E::ts('Dark-mode Custom CSS'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('Custom css for the darkmode of this stream'),
      'default' => NULL,
    ],
    'modified_date' => [
      'title' => ts('Modified Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'description' => ts('When the stream was last modified - helps with cache busting.'),
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ],
  ],
];
