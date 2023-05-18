<?php
return CRM_Core_CodeGen_OptionGroup::create('wysiwyg_editor', 'a/0031')
  ->addMetadata([
    'title' => ts('WYSIWYG Editor'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Textarea'), 'Textarea', 1],
    [ts('CKEditor 4'), 'CKEditor', 2],
  ])
  ->addDefaults([
    'is_reserved' => 1,
  ]);
