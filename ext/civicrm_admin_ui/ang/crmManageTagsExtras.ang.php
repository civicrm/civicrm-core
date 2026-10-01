<?php
return [
  'js' => [
    'ang/crmManageTagsExtras.js',
  ],
  'requires' => ['crmUi'],
  'exports' => [
    'crm-permission-popup-link' => 'E',
    'crm-manage-tagset-list' => 'E',
  ],
  'permissions' => ['administer Tagsets'],
];
