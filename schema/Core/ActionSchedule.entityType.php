<?php

return [
  'name' => 'ActionSchedule',
  'table' => 'civicrm_action_schedule',
  'class' => 'CRM_Core_DAO_ActionSchedule',
  'getInfo' => fn() => [
    'title' => ts('Scheduled Reminder'),
    'title_plural' => ts('Scheduled Reminders'),
    'description' => ts('Table to store the reminders.'),
    'add' => '3.4',
    'label_field' => 'title',
  ],
  'getPaths' => fn() => [
    'browse' => 'civicrm/admin/scheduleReminders',
    'add' => 'civicrm/admin/scheduleReminders/edit?reset=1&action=add',
    'update' => 'civicrm/admin/scheduleReminders/edit?reset=1&action=update&id=[id]',
    'delete' => 'civicrm/admin/scheduleReminders/edit?reset=1&action=delete&id=[id]',
  ],
  'getIndices' => fn() => [
    'UI_name' => [
      'fields' => [
        'name' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '5.65',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Action Schedule ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'add' => '3.4',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('Name'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Name of the scheduled action'),
      'add' => '3.4',
    ],
    'title' => [
      'title' => ts('Title'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Title of the action(reminder)'),
      'add' => '3.4',
    ],
    'recipient' => [
      'title' => ts('Recipient'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'description' => ts('Recipient'),
      'add' => '3.4',
      'input_attrs' => [
        'label' => ts('Limit or Add Recipients'),
        'control_field' => 'mapping_id',
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_ActionSchedule', 'getRecipientOptions'],
      ],
    ],
    'limit_to' => [
      'title' => ts('Limit To'),
      'sql_type' => 'int',
      'input_type' => 'Select',
      'description' => ts('Is this the recipient criteria limited to OR in addition to?'),
      'add' => '4.4',
      'input_attrs' => [
        'label' => ts('Limit/Add'),
        'control_field' => 'mapping_id',
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_ActionSchedule', 'getLimitToOptions'],
      ],
    ],
    'entity_value' => [
      'title' => ts('Entity Value'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'description' => ts('Entity value'),
      'add' => '3.4',
      'serialize' => CRM_Core_DAO::SERIALIZE_SEPARATOR_TRIMMED,
      'input_attrs' => [
        'label' => ts('Entity Value'),
        'multiple' => '1',
        'control_field' => 'mapping_id',
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_ActionSchedule', 'getEntityValueOptions'],
      ],
    ],
    'entity_status' => [
      'title' => ts('Entity Status'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'description' => ts('Entity status'),
      'add' => '3.4',
      'serialize' => CRM_Core_DAO::SERIALIZE_SEPARATOR_TRIMMED,
      'input_attrs' => [
        'label' => ts('Entity Status'),
        'multiple' => '1',
        'control_field' => 'entity_value',
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_ActionSchedule', 'getEntityStatusOptions'],
      ],
    ],
    'start_action_offset' => [
      'title' => ts('Start Action Offset'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => ts('Reminder Interval.'),
      'add' => '3.4',
      'default' => 0,
      'input_attrs' => [
        'min' => '0',
        'label' => ts('Start Action Offset'),
      ],
    ],
    'start_action_unit' => [
      'title' => ts('Start Action Unit'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Select',
      'description' => ts('Time units for reminder.'),
      'add' => '3.4',
      'input_attrs' => [
        'label' => ts('Start Action Unit'),
        'control_field' => 'start_action_offset',
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_ActionSchedule', 'getDateUnits'],
      ],
    ],
    'start_action_condition' => [
      'title' => ts('Start Action Condition'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'description' => ts('Reminder Action'),
      'add' => '3.4',
      'input_attrs' => [
        'label' => ts('Start Condition'),
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_SelectValues', 'beforeAfter'],
      ],
    ],
    'start_action_date' => [
      'title' => ts('Start Action Date'),
      'sql_type' => 'varchar(2048)',
      'input_type' => 'Select',
      'description' => ts('Entity date'),
      'add' => '3.4',
      'input_attrs' => [
        'label' => ts('Start Date'),
        'control_field' => 'entity_value',
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_ActionSchedule', 'getActionDateOptions'],
      ],
    ],
    'is_repeat' => [
      'title' => ts('Repeat'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'add' => '3.4',
      'default' => FALSE,
    ],
    'repetition_frequency_unit' => [
      'title' => ts('Repetition Frequency Unit'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Select',
      'description' => ts('Time units for repetition of reminder.'),
      'add' => '3.4',
      'input_attrs' => [
        'label' => ts('Repetition Frequency Unit'),
        'control_field' => 'repetition_frequency_interval',
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_ActionSchedule', 'getDateUnits'],
      ],
    ],
    'repetition_frequency_interval' => [
      'title' => ts('Repetition Frequency Interval'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => ts('Time interval for repeating the reminder.'),
      'add' => '3.4',
      'default' => 0,
      'input_attrs' => [
        'min' => '0',
        'label' => ts('Repetition Frequency Interval'),
      ],
    ],
    'end_frequency_unit' => [
      'title' => ts('End Frequency Unit'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Select',
      'description' => ts('Time units till repetition of reminder.'),
      'add' => '3.4',
      'input_attrs' => [
        'label' => ts('End Frequency Unit'),
        'control_field' => 'end_frequency_interval',
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_ActionSchedule', 'getDateUnits'],
      ],
    ],
    'end_frequency_interval' => [
      'title' => ts('End Frequency Interval'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => ts('Time interval till repeating the reminder.'),
      'add' => '3.4',
      'default' => 0,
      'input_attrs' => [
        'min' => '0',
        'label' => ts('End Frequency Interval'),
      ],
    ],
    'end_action' => [
      'title' => ts('End Action'),
      'sql_type' => 'varchar(32)',
      'input_type' => 'Select',
      'description' => ts('Reminder Action till repeating the reminder.'),
      'add' => '3.4',
      'input_attrs' => [
        'label' => ts('End Condition'),
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_SelectValues', 'beforeAfter'],
      ],
    ],
    'end_date' => [
      'title' => ts('End Date'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'description' => ts('Entity end date'),
      'add' => '3.4',
      'input_attrs' => [
        'label' => ts('End Date'),
        'control_field' => 'entity_value',
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_ActionSchedule', 'getActionDateOptions'],
      ],
    ],
    'is_active' => [
      'title' => ts('Schedule is Active?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this option active?'),
      'add' => '3.4',
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
    'recipient_manual' => [
      'title' => ts('Recipient Manual'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'EntityRef',
      'description' => ts('Contact IDs to which reminder should be sent.'),
      'add' => '3.4',
      'serialize' => CRM_Core_DAO::SERIALIZE_COMMA,
      'input_attrs' => [
        'label' => ts('Manual Recipients'),
        'multiple' => '1',
      ],
    ],
    'recipient_listing' => [
      'title' => ts('Recipient Listing'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Select',
      'description' => ts('listing based on recipient field.'),
      'add' => '4.1',
      'serialize' => CRM_Core_DAO::SERIALIZE_SEPARATOR_TRIMMED,
      'input_attrs' => [
        'label' => ts('Recipient Roles'),
        'multiple' => '1',
        'control_field' => 'recipient',
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_ActionSchedule', 'getRecipientListingOptions'],
      ],
    ],
    'body_text' => [
      'title' => ts('Reminder Text'),
      'sql_type' => 'longtext',
      'input_type' => 'TextArea',
      'description' => ts('Body of the mailing in text format.'),
      'add' => '3.4',
    ],
    'body_html' => [
      'title' => ts('Reminder HTML'),
      'sql_type' => 'longtext',
      'input_type' => 'RichTextEditor',
      'description' => ts('Body of the mailing in html format.'),
      'add' => '3.4',
    ],
    'sms_body_text' => [
      'title' => ts('SMS Reminder Text'),
      'sql_type' => 'longtext',
      'input_type' => 'TextArea',
      'description' => ts('Content of the SMS text.'),
      'add' => '4.5',
    ],
    'subject' => [
      'title' => ts('Reminder Subject'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => ts('Subject of mailing'),
      'add' => '3.4',
    ],
    'record_activity' => [
      'title' => ts('Record Activity'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Record Activity for this reminder?'),
      'add' => '3.4',
      'default' => FALSE,
    ],
    'mapping_id' => [
      'title' => ts('Reminder For'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'description' => ts('Name/ID of the mapping to use on this table'),
      'add' => '3.4',
      'input_attrs' => [
        'label' => ts('Used For'),
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_ActionSchedule', 'getMappingOptions'],
        'suffixes' => [
          'name',
          'label',
          'icon',
        ],
      ],
    ],
    'group_id' => [
      'title' => ts('Group ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Group'),
      'add' => '3.4',
      'input_attrs' => [
        'label' => ts('Group'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_group',
        'key_column' => 'id',
        'label_column' => 'title',
        'prefetch' => 'disabled',
      ],
      'entity_reference' => [
        'entity' => 'Group',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'msg_template_id' => [
      'title' => ts('Message Template ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to the message template.'),
      'input_attrs' => [
        'label' => ts('Message Template'),
      ],
      'entity_reference' => [
        'entity' => 'MessageTemplate',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'sms_template_id' => [
      'title' => ts('SMS Template ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to the message template.'),
      'input_attrs' => [
        'label' => ts('SMS Template'),
      ],
      'entity_reference' => [
        'entity' => 'MessageTemplate',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'absolute_date' => [
      'title' => ts('Fixed Date for Reminder'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'description' => ts('Date on which the reminder be sent.'),
      'add' => '4.1',
      'input_attrs' => [
        'format_type' => 'activityDate',
      ],
    ],
    'from_name' => [
      'title' => ts('Reminder from Name'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Name in "from" field'),
      'add' => '4.5',
      'input_attrs' => [
        'label' => ts('From Name'),
      ],
    ],
    'from_email' => [
      'title' => ts('Reminder From Email'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Email',
      'description' => ts('Email address in "from" field'),
      'add' => '4.5',
      'input_attrs' => [
        'label' => ts('From Email'),
      ],
    ],
    'mode' => [
      'title' => ts('Message Mode'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Select',
      'description' => ts('Send the message as email or sms or both.'),
      'add' => '4.5',
      'default' => 'Email',
      'pseudoconstant' => [
        'option_group_name' => 'msg_mode',
      ],
    ],
    'sms_provider_id' => [
      'title' => ts('SMS Provider ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'add' => '4.5',
      'input_attrs' => [
        'label' => ts('SMS Provider'),
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_SelectValues', 'smsProvider'],
      ],
      'entity_reference' => [
        'entity' => 'SmsProvider',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'used_for' => [
      'title' => ts('Used For'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Used for repeating entity'),
      'add' => '4.6',
      'input_attrs' => [
        'label' => ts('Used For'),
      ],
    ],
    'filter_contact_language' => [
      'title' => ts('Filter Contact Language'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Select',
      'description' => ts('Used for multilingual installation'),
      'add' => '4.7',
      'serialize' => CRM_Core_DAO::SERIALIZE_SEPARATOR_TRIMMED,
      'input_attrs' => [
        'multiple' => TRUE,
        'label' => ts('Recipients Language'),
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_ActionSchedule', 'getFilterContactLanguageOptions'],
      ],
    ],
    'communication_language' => [
      'title' => ts('Communication Language'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Select',
      'description' => ts('Used for multilingual installation'),
      'add' => '4.7',
      'input_attrs' => [
        'label' => ts('Communication Language'),
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_ActionSchedule', 'getCommunicationLanguageOptions'],
      ],
    ],
    'created_date' => [
      'title' => ts('Created Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'description' => ts('When was the scheduled reminder created.'),
      'add' => '5.34',
      'unique_name' => 'action_schedule_created_date',
      'default' => 'CURRENT_TIMESTAMP',
      'usage' => [
        'export',
      ],
    ],
    'modified_date' => [
      'title' => ts('Modified Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => ts('When the reminder was created or modified.'),
      'add' => '5.34',
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
      'input_attrs' => [
        'label' => ts('Modified Date'),
      ],
    ],
    'effective_start_date' => [
      'title' => ts('Effective start date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'description' => ts('Earliest date to consider start events from.'),
      'add' => '5.34',
      'unique_name' => 'action_schedule_effective_start_date',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'format_type' => 'activityDate',
      ],
    ],
    'effective_end_date' => [
      'title' => ts('Effective end date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'description' => ts('Latest date to consider end events from.'),
      'add' => '5.34',
      'unique_name' => 'action_schedule_effective_end_date',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'format_type' => 'activityDate',
      ],
    ],
  ],
];
