<?php
return CRM_Core_CodeGen_OptionGroup::create('msg_mode', 'a/0075')
  ->addMetadata([
    'title' => ts('Message Mode'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Email'), 'Email', 'Email', 'is_default' => 1, 'is_reserved' => 1],
    [ts('SMS'), 'SMS', 'SMS', 'is_reserved' => 1],
    [ts('User Preference'), 'User Preference', 'User_Preference', 'is_reserved' => 1],
  ]);
