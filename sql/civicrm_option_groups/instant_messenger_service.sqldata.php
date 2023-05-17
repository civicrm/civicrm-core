<?php
return CRM_Core_CodeGen_OptionGroup::create('instant_messenger_service', 'a/0004')
  ->addMetadata([
    'title' => ts('Instant Messenger (IM) screen-names'),
    'description' => ts('Commonly-used messaging apps are listed here. Administrators may define as many additional providers as needed.'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    ['Yahoo', 'Yahoo', 1],
    ['MSN', 'Msn', 2],
    ['AIM', 'Aim', 3],
    ['GTalk', 'Gtalk', 4],
    ['Jabber', 'Jabber', 5],
    ['Skype', 'Skype', 6],
  ])
  ->addDefaults([
    'is_default' => NULL,
  ]);
