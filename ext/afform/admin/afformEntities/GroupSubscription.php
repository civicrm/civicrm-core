<?php
return [
  'type' => 'primary',
  'defaults' => "{
    data: {
      contact_id: 'Individual1',
    },
    actions: {create: true, update: false},
    security: 'FBAC'
  }",
  'boilerplate' => [],
  'options_tpl' => '~/afGuiEditor/entityConfig/GroupSubscriptionOptions.html',
  'icon' => 'fa-users',
  'label' => 'Group Subscription',
];
