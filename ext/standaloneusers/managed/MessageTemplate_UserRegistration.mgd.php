<?php
use CRM_Standaloneusers_ExtensionUtil as E;

$userRegistrationSubject = '{ts}Account details for{/ts} {$usernameHtml} {ts}at{/ts} {domain.name}';

$userRegistrationHtml = <<<HTML
  <p>{ts}An account has been created for you at {domain.name}.{/ts}</p>

  <p>{ts}Your username:{/ts} <strong>{\$usernameHtml}</strong></p>

  <p>{ts}To set your password and log in, click the link below:{/ts}</p>

  <p><a href="{\$resetUrlHtml}">{\$resetUrlHtml}</a></p>

  <p><strong>{\$tokenTimeoutHtml}</strong></p>

  <p>{domain.name}</p>
HTML;

return [
  [
    'name' => 'MessageTemplate_UserRegistrationReserved',
    'entity' => 'MessageTemplate',
    'cleanup' => 'unused',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'match' => [
        'workflow_name',
        'is_reserved',
      ],
      'values' => [
        'workflow_name' => 'user_registration',
        'msg_title' => E::ts('User registration'),
        'msg_subject' => $userRegistrationSubject,
        'msg_text' => '',
        'msg_html' => $userRegistrationHtml,
        'is_default' => FALSE,
        'is_reserved' => TRUE,
      ],
    ],
  ],
  [
    'name' => 'MessageTemplate_UserRegistrationEditable',
    'entity' => 'MessageTemplate',
    'cleanup' => 'unused',
    'update' => 'never',
    'params' => [
      'version' => 4,
      'match' => [
        'workflow_name',
        'is_reserved',
      ],
      'values' => [
        'workflow_name' => 'user_registration',
        'msg_title' => E::ts('User registration'),
        'msg_subject' => $userRegistrationSubject,
        'msg_text' => '',
        'msg_html' => $userRegistrationHtml,
        'is_default' => TRUE,
        'is_reserved' => FALSE,
      ],
    ],
  ],
];
