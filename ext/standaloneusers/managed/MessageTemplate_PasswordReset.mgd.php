<?php
use CRM_Standaloneusers_ExtensionUtil as E;

$passwordResetSubject = '{ts}Password reset link for{/ts} {domain.name}';

$passwordResetText = <<<TXT
  {ts}A password reset link was requested for this account.  If this wasn't you (and nobody else can access this email account) you can safely ignore this email.{/ts}

  {\$resetUrlPlaintext}

  {\$tokenTimeoutPlaintext}

  {domain.name}
TXT;

$passwordResetHtml = <<<HTML
  <p>{ts}A password reset link was requested for this account.&nbsp; If this wasn't you (and nobody else can access this email account) you can safely ignore this email.{/ts}</p>

  <p><a href="{\$resetUrlHtml}">{\$resetUrlHtml}</a></p>

  <p>{domain.name}</p>
HTML;

return [
  [
    'name' => 'MessageTemplate_PasswordResetReserved',
    'entity' => 'MessageTemplate',
    'cleanup' => 'unused',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'checkPermissions' => FALSE,
      'match' => [
        'workflow_name',
        'is_reserved',
      ],
      'values' => [
        'workflow_name' => 'password_reset',
        'msg_title' => E::ts('Password reset'),
        'msg_subject' => $passwordResetSubject,
        'msg_text' => $passwordResetText,
        'msg_html' => $passwordResetHtml,
        'is_default' => FALSE,
        'is_reserved' => TRUE,
      ],
    ],
  ],
  [
    'name' => 'MessageTemplate_PasswordResetEditable',
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
        'workflow_name' => 'password_reset',
        'msg_title' => E::ts('Password reset'),
        'msg_subject' => $passwordResetSubject,
        'msg_text' => $passwordResetText,
        'msg_html' => $passwordResetHtml,
        'is_default' => TRUE,
        'is_reserved' => FALSE,
      ],
    ],
  ],
];
