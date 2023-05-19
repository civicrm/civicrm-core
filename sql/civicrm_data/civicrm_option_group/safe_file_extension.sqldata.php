<?php
return CRM_Core_CodeGen_OptionGroup::create('safe_file_extension', 'a/0028')
  ->addMetadata([
    'title' => ts('Safe File Extension'),
  ])
  ->addValueTable(['name', 'value'], [
    ['jpg', 1],
    ['jpeg', 2],
    ['png', 3],
    ['gif', 4],
    ['txt', 5],
    ['pdf', 6],
    ['doc', 7],
    ['xls', 8],
    ['rtf', 9],
    ['csv', 10],
    ['ppt', 11],
    ['docx', 12],
    ['xlsx', 13],
    ['odt', 14],
    ['ics', 15],
    ['pptx', 16],
  ])
  ->syncColumns('fill', ['name' => 'label']);
