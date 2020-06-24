<?php
CRM_Core_Resources::singleton()->addSetting(array(
  'strings' => array(
    'One, two, three' => 'Un, deux, trois',
    'I know' => 'Je sais',
  ),
  'strings::org.example.foo' => array(
    'I know' => 'Je connais',
  ),
));
// CRM_Core_Resources::singleton()->addScriptFile(...);
// CRM_Core_Resources::singleton()->addStyleFile(...);
// CRM_Core_Resources::singleton()->addSetting(...);
