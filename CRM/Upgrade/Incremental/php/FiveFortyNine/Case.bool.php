<?php
return [
  'civicrm_case' => [
    'is_deleted' => "DEFAULT 0",
  ],
  'civicrm_case_type' => [
    'is_active' => "DEFAULT 1 COMMENT 'Is this case type enabled?'",
    'is_reserved' => "DEFAULT 0 COMMENT 'Is this case type a predefined system type?'",
  ],
];
