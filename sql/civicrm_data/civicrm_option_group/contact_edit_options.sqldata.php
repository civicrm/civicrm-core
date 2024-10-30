<?php
return CRM_Core_CodeGen_OptionGroup::create('contact_edit_options', 'a/0018')
  ->addMetadata([
    'title' => ts('Contact Edit Options'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Custom Data'), 'CustomData', 1],
    [ts('Address'), 'Address', 2],
    [ts('Communication Preferences'), 'CommunicationPreferences', 3],
    [ts('Notes'), 'Notes', 4],
    [ts('Demographics'), 'Demographics', 5],
    [ts('Tags and Groups'), 'TagsAndGroups', 6],
    [ts('Email'), 'Email', 7, 'filter' => 1],
    [ts('Phone'), 'Phone', 8, 'filter' => 1],
    [ts('Instant Messenger'), 'IM', 9, 'filter' => 1],
    [ts('Open ID'), 'OpenID', 10, 'filter' => 1],
    [ts('Website'), 'Website', 11, 'filter' => 1],
    [ts('Prefix'), 'Prefix', 12, 'filter' => 2],
    [ts('Formal Title'), 'Formal Title', 13, 'filter' => 2],
    [ts('First Name'), 'First Name', 14, 'filter' => 2],
    [ts('Middle Name'), 'Middle Name', 15, 'filter' => 2],
    [ts('Last Name'), 'Last Name', 16, 'filter' => 2],
    [ts('Suffix'), 'Suffix', 17, 'filter' => 2],
  ]);
