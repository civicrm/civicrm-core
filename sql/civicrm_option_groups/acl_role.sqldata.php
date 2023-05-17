<?php
return CRM_Core_CodeGen_OptionGroup::create('acl_role', 'a/0008')
  ->addMetadata([
    'title' => ts('ACL Role'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Administrator'), 'Admin', 1],
    [ts('Authenticated'), 'Auth', 2, 'is_reserved' => 1],
  ]);
