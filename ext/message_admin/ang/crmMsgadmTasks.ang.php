<?php
// Angular module providing the "Add Translation" SearchKit task.
// See also: \https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules/
return [
  'js' => [
    'ang/crmMsgadmTasks.js',
    'ang/crmMsgadmTasks/*.js',
  ],
  'partials' => [
    'ang/crmMsgadmTasks',
  ],
  'requires' => [
    'crmUi',
    'api4',
  ],
  'settingsFactory' => ['CRM_MessageAdmin_Settings', 'getTaskSettings'],
];
