<?php
return CRM_Core_CodeGen_OptionGroup::create('individual_suffix', 'a/0007')
  ->addMetadata([
    'title' => ts('Individual contact suffixes'),
    'description' => ts('CiviCRM is pre-configured with standard options for individual contact name suffixes (Jr., Sr., II etc.). Customize these options and add new ones as needed for your installation.'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Jr.'), 'Jr.', 1],
    [ts('Sr.'), 'Sr.', 2],
    ['II', 'II', 3],
    ['III', 'III', 4],
    ['IV', 'IV', 5],
    ['V', 'V', 6],
    ['VI', 'VI', 7],
    ['VII', 'VII', 8],
  ]);
