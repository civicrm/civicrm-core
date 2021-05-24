<?php
return [
  'defaults' => "{
    data: {
      contact_type: 'Household',
      source: afform.title
    }
  }",
  'icon' => 'fa-home',
  'boilerplate' => [
    ['#tag' => 'afblock-name-household'],
  ],
  'admin_tpl' => '~/afGuiEditor/entityConfig/Contact.html',
];
