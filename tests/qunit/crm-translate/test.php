<?php
CRM_Core_Resources::singleton()->addSetting([
  'strings' => [
    'One, two, three' => 'Un, deux, trois',
    'I know' => 'Je sais',
  ],
  'strings::org.example.foo' => [
    'I know' => 'Je connais',
  ],
]);
// CRM_Core_Resources::singleton()->addScriptFile(...);
// CRM_Core_Resources::singleton()->addStyleFile(...);
// CRM_Core_Resources::singleton()->addSetting(...);
