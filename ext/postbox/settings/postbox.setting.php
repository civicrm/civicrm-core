<?php

use CRM_Postbox_ExtensionUtil as E;

return [
  'postbox_shutdown_dispatcher' => [
    'name' => 'postbox_shutdown_dispatcher',
    'type' => 'Boolean',
    'html_type' => 'Toggle',
    'default' => TRUE,
    'is_domain' => 1,
    'is_contact' => 0,
    'title' => E::ts('Enable Shutdown Dispatcher'),
    'description' => E::ts('
    By default postbox will use a post shutdown function to dispatch messages from the message queue. You can disable if you want
      to dispatch messages differently - e.g. using coworker'),
  ],
];
