<?php
Civi::resources()->addSetting(array(
    'strings' => array(
      'One, two, three' => 'Un, deux, trois',
      'I know' => 'Je sais',
    ),
    'strings::org.example.foo' => array(
      'I know' => 'Je connais',
    ),
  )
);
// Civi::resources()->addScriptFile(...);
// Civi::resources()->addStyleFile(...);
// Civi::resources()->addSetting(...);
