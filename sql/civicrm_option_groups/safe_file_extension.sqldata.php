<?php
return CRM_Core_CodeGen_OptionGroup::create('safe_file_extension', 'a/0028')
  ->addMetadata([
    'title' => ts('Safe File Extension'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    ['jpg', 'jpg', 1],
    ['jpeg', 'jpeg', 2],
    ['png', 'png', 3],
    ['gif', 'gif', 4],
    ['txt', 'txt', 5],
    ['pdf', 'pdf', 6],
    ['doc', 'doc', 7],
    ['xls', 'xls', 8],
    ['rtf', 'rtf', 9],
    ['csv', 'csv', 10],
    ['ppt', 'ppt', 11],
    ['docx', 'docx', 12],
    ['xlsx', 'xlsx', 13],
    ['odt', 'odt', 14],
    ['ics', 'ics', 15],
    ['pptx', 'pptx', 16],
  ]);
