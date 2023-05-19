<?php
return CRM_Core_CodeGen_OptionGroup::create('acl_role', 'a/0008')
  ->addMetadata([
    'title' => ts('ACL Role'),
  ])
  ->addValues([
    [
      'label' => ts('Administrator'),
      'value' => 1,
      'name' => 'Admin',
    ],
    [
      'label' => ts('Authenticated'),
      'value' => 2,
      'name' => 'Auth',
      'is_reserved' => 1,
    ],
  ]);
