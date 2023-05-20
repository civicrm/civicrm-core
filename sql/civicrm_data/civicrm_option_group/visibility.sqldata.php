<?php
return CRM_Core_CodeGen_OptionGroup::create('visibility', 'a/0035')
  ->addMetadata([
    'title' => ts('Visibility'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    ['Public', 'public', 1],
    ['Admin', 'admin', 2],
  ]);
