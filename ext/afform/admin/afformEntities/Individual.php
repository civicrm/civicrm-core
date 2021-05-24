<?php
return [
  'defaults' => "{
    data: {
      contact_type: 'Individual',
      source: afform.title
    }
  }",
  'icon' => 'fa-user',
  'boilerplate' => [
    ['#tag' => 'afblock-name-individual'],
  ],
  'admin_tpl' => '~/afGuiEditor/entityConfig/Contact.html',
];
