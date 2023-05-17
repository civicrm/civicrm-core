<?php
return CRM_Core_CodeGen_OptionGroup::create('advanced_search_options', 'a/0019')
  ->addMetadata([
    'title' => ts('Advanced Search Options'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value', 'weight'], [
    [ts('Address Fields'), 'location', 1, 1],
    [ts('Custom Fields'), 'custom', 2, 2],
    [ts('Activities'), 'activity', 3, 4],
    [ts('Relationships'), 'relationship', 4, 5],
    [ts('Notes'), 'notes', 5, 6],
    [ts('Change Log'), 'changeLog', 6, 7],
    [ts('Contributions'), 'CiviContribute', 7, 8],
    [ts('Memberships'), 'CiviMember', 8, 9],
    [ts('Events'), 'CiviEvent', 9, 10],
    [ts('Cases'), 'CiviCase', 10, 11],
    [ts('Demographics'), 'demographics', 13, 15],
    [ts('Pledges'), 'CiviPledge', 15, 17],
    [ts('Contact Type'), 'contactType', 16, 18],
    [ts('Groups'), 'groups', 17, 19],
    [ts('Tags'), 'tags', 18, 20],
    [ts('Mailing'), 'CiviMail', 19, 21],
  ]);
