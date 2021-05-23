<?php
return [
  'defaults' => "{
    data: {
      contact_type: 'Organization',
      source: afform.title
    }
  }",
  'icon' => 'fa-building',
  'boilerplate' => [
    ['#tag' => 'afblock-name-organization'],
  ],
  'admin_tpl' => '~/afGuiEditor/entityConfig/Contact.html',
];
