<?php
return CRM_Core_CodeGen_OptionGroup::create('msg_mode', 'a/0075')
  ->addMetadata([
    'title' => ts('Message Mode'),
  ])
  ->addValues([
    [
      'label' => ts('Email'),
      'value' => 'Email',
      'name' => 'Email',
      'is_default' => 1,
      'is_reserved' => 1,
    ],
    [
      'label' => ts('SMS'),
      'value' => 'SMS',
      'name' => 'SMS',
      'is_reserved' => 1,
    ],
    [
      'label' => ts('User Preference'),
      'value' => 'User_Preference',
      'name' => 'User Preference',
      'is_reserved' => 1,
    ],
  ]);
