<?php
return CRM_Core_CodeGen_OptionGroup::create('sms_api_type', 'a/0067')
  ->addMetadata([
    'title' => ts('Api Type'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    ['http', 'http', 1, 'is_reserved' => 1],
    ['xml', 'xml', 2, 'is_reserved' => 1],
    ['smtp', 'smtp', 3, 'is_reserved' => 1],
  ])
  ->addDefaults([
    'filter' => NULL,
  ]);
