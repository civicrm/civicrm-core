<?php
use CRM_Event_Cart_ExtensionUtil as E;

$htmlText = file_get_contents(__DIR__ . '/event_registration_receipt_html.tpl');
$subject = file_get_contents(__DIR__ . '/event_registration_receipt_subject.tpl');

/**
 * Add event cart template.
 */
return [
  [
    'name' => 'event_registration_receipt',
    'entity' => 'MessageTemplate',
    'cleanup' => 'unused',
    'update' => 'never',
    'params' => [
      'version' => 4,
      'checkPermissions' => FALSE,
      'match' => [
        'workflow_name',
        'is_reserved',
      ],
      'values' => [
        'msg_title' => E::ts('Events - Receipt only'),
        'msg_text' => '',
        'msg_html' => $htmlText,
        'msg_subject' => $subject,
        'is_default' => TRUE,
        'is_active' => TRUE,
        'is_reserved' => FALSE,
        'workflow_name' => 'event_registration_receipt',
      ],
    ],
  ],
  [
    'name' => 'event_registration_receipt_reserved',
    'entity' => 'MessageTemplate',
    'cleanup' => 'unused',
    'update' => 'never',
    'params' => [
      'version' => 4,
      'checkPermissions' => FALSE,
      'match' => [
        'workflow_name',
        'is_reserved',
      ],
      'values' => [
        'msg_title' => E::ts('Events - Receipt only'),
        'msg_text' => '',
        'msg_html' => $htmlText,
        'msg_subject' => $subject,
        'is_default' => FALSE,
        'is_active' => TRUE,
        'is_reserved' => TRUE,
        'workflow_name' => 'event_registration_receipt',
      ],
    ],
  ],
];
