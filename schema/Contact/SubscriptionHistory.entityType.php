<?php

return [
  'name' => 'SubscriptionHistory',
  'table' => 'civicrm_subscription_history',
  'class' => 'CRM_Contact_DAO_SubscriptionHistory',
  'getInfo' => fn() => [
    'title' => ts('Subscription History'),
    'title_plural' => ts('Subscription Histories'),
    'description' => ts('History information of subscribe/unsubscribe actions'),
    'log' => TRUE,
    'add' => '1.1',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Group Membership History ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Internal ID'),
      'add' => '1.1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'contact_id' => [
      'title' => ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Contact ID'),
      'add' => '1.1',
      'input_attrs' => [
        'label' => ts('Contact'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'group_id' => [
      'title' => ts('Group ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Group ID'),
      'add' => '1.1',
      'input_attrs' => [
        'label' => ts('Group'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_group',
        'key_column' => 'id',
        'label_column' => 'title',
      ],
      'entity_reference' => [
        'entity' => 'Group',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'date' => [
      'title' => ts('Group Membership Action Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'required' => TRUE,
      'description' => ts('Date of the (un)subscription'),
      'add' => '1.1',
      'default' => 'CURRENT_TIMESTAMP',
      'input_attrs' => [
        'label' => ts('Group Membership Action Date'),
        'format_type' => 'activityDateTime',
      ],
    ],
    'method' => [
      'title' => ts('Group Membership Action'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Select',
      'description' => ts('How the (un)subscription was triggered'),
      'add' => '1.1',
      'pseudoconstant' => [
        'callback' => ['CRM_Core_SelectValues', 'getSubscriptionHistoryMethods'],
      ],
    ],
    'status' => [
      'title' => ts('Group Membership Status'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Select',
      'description' => ts('The state of the contact within the group'),
      'add' => '1.1',
      'pseudoconstant' => [
        'callback' => ['CRM_Core_SelectValues', 'groupContactStatus'],
      ],
    ],
    'tracking' => [
      'title' => ts('Group Membership Tracking'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('IP address or other tracking info'),
      'add' => '1.1',
    ],
  ],
];
