<<<<<<< HEAD
<?php
CRM_Core_Resources::singleton()->addSetting(array(
    'strings' => array('One, two, three' => 'Un, deux, trois')
  )
);
// CRM_Core_Resources::singleton()->addScriptFile(...);
// CRM_Core_Resources::singleton()->addStyleFile(...);
// CRM_Core_Resources::singleton()->addSetting(...);
=======
<?php
CRM_Core_Resources::singleton()->addSetting(array(
    'strings' => array(
      'One, two, three' => 'Un, deux, trois',
      'I know' => 'Je sais',
    ),
    'strings::org.example.foo' => array(
      'I know' => 'Je connais',
    ),
  )
);
// CRM_Core_Resources::singleton()->addScriptFile(...);
// CRM_Core_Resources::singleton()->addStyleFile(...);
// CRM_Core_Resources::singleton()->addSetting(...);
>>>>>>> 650ff6351383992ec77abface9b7f121f16ae07e
